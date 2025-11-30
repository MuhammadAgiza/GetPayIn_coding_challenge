<?php

namespace Tests\Api\Products;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductCacheTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Product::factory()->create([
            'id' => 1,
            'stock' => 100,
        ]);
    }

    protected function getShowProductUrl($id)
    {
        return route('api.product.show', ['id' => $id], false);
    }

    protected function getListProductUrl($params = [])
    {
        return route('api.product.index', $params, false);
    }

    protected function getCreateHoldUrl()
    {
        return route('api.hold.create', [], false);
    }

    public function test_parallel_product_listing_remains_fast_and_correct()
    {
        $start = microtime(true);

        $responses = [];
        $urls = [];
        foreach (range(1, 20) as $i) {
            $urls[] = $this->getListProductUrl();
        }

        $responses = collect($urls)->map(function ($url) {
            return $this->getJson($url);
        });

        foreach ($responses as $response) {
            $response->assertStatus(200);
            $response->assertJsonStructure(['data']);
            $this->assertEquals(1, $response->json('data.pagination.total'));
        }

        $duration = microtime(true) - $start;
        $this->assertLessThan(2, $duration, 'Parallel listing requests should be fast');
    }

    public function test_parallel_product_show_remains_fast_and_correct()
    {
        $start = microtime(true);

        $responses = [];
        $urls = [];
        foreach (range(1, 20) as $i) {
            $urls[] = $this->getShowProductUrl(1);
        }
        $responses = collect($urls)->map(function ($url) {
            return $this->getJson($url);
        });

        foreach ($responses as $response) {
            $response->assertStatus(200);
            $response->assertJsonStructure(['data' => ['product']]);
            $this->assertEquals(1, $response->json('data.product.id'));
        }

        $duration = microtime(true) - $start;
        $this->assertLessThan(2, $duration, 'Parallel show requests should be fast');
    }

    public function test_cache_invalidation_on_stock_change()
    {
        $this->getJson($this->getShowProductUrl(1));
        $this->assertNotNull(Cache::get('product:1'));

        $product = Product::find(1);
        $product->stock = 90;
        $product->save();

        $this->assertNull(Cache::get('product:1'));

        $response = $this->getJson($this->getShowProductUrl(1));

        $response->assertStatus(200);

        $this->assertEquals(90, $response->json('data.product.stock'));
    }

    public function test_create_hold_endpoint_invalidates_cache_and_updates_stock()
    {
        $this->getJson($this->getShowProductUrl(1));

        $this->assertNotNull(Cache::get('product:1'));

        $response = $this->postJson($this->getCreateHoldUrl(), ['product_id' => 1, 'qty' => 5]);

        $response->assertStatus(201);

        $response->assertJsonStructure(['data' => ['hold' => ['id', 'product_id', 'qty']]]);

        $this->assertNull(Cache::get('product:1'), 'Cache should be invalidated after hold');

        $response = $this->getJson($this->getShowProductUrl(1));

        $response->assertStatus(200);

        $this->assertEquals(95, $response->json('data.product.stock'));
    }

    public function test_no_overselling_under_heavy_parallel_holds()
    {
        $availableStock = 218;
        Product::factory()->create(['id' => 2, 'stock' => $availableStock]);
        $holdRequests = [];
        $qtyPerReq = 3;
        $totalRequests = 500;

        foreach (range(1, $totalRequests) as $i) {
            $holdRequests[] = function () use ($qtyPerReq) {
                return $this->postJson($this->getCreateHoldUrl(), ['product_id' => 2, 'qty' => $qtyPerReq]);
            };
        }

        $responses = [];
        foreach ($holdRequests as $req) {
            $responses[] = $req();
        }

        $success = 0;
        $fail = 0;
        foreach ($responses as $response) {
            if ($response->status() === 201) {
                $success++;
            } else {
                $fail++;
            }
        }
        $shouldSuccess = floor($availableStock / $qtyPerReq);
        $shouldFail = $totalRequests - $shouldSuccess;
        $this->assertEquals($shouldSuccess, $success, 'Should not oversell stock');
        $this->assertEquals($shouldFail, $fail, 'Should reject requests beyond stock');

        $product = Product::find(2);
        $this->assertGreaterThanOrEqual(0, $product->stock, 'Stock should not go below zero');
    }

    public function test_deadlock_and_race_condition_handling()
    {
        Product::factory()->create(['id' => 3, 'stock' => 5]);
        $errors = 0;
        $success = 0;

        $requests = [];
        foreach (range(1, 10) as $i) {
            $requests[] = function () {
                return $this->postJson($this->getCreateHoldUrl(), ['product_id' => 3, 'qty' => 1]);
            };
        }

        foreach ($requests as $req) {
            try {
                $resp = $req();
                if ($resp->status() === 201) {
                    $success++;
                } else {
                    $errors++;
                }
            } catch (\Throwable $e) {
                $errors++;
            }
        }

        $this->assertEquals(5, $success, 'Should not allow more holds than stock');
        $this->assertEquals(5, $errors, 'Should handle race/deadlock without crashing');

        $product = Product::find(3);
        $this->assertEquals(0, $product->stock, 'Stock should be zero after all holds');
    }

    public function test_product_list_cache_invalidation_on_multiple_holds()
    {
        // Create multiple products
        $products = Product::factory()->count(3)->sequence(
            ['id' => 10, 'stock' => 50],
            ['id' => 11, 'stock' => 60],
            ['id' => 12, 'stock' => 70]
        )->create();

        $listUrl = $this->getListProductUrl(['per_page' => 10, 'page' => 1]);
        $response = $this->getJson($listUrl);
        $response->assertStatus(200);

        $this->assertCount(4, $response->json('data.list')); // 3 new + 1 from setUp

        $cachedBefore = Cache::tags(['products'])->get('products:perPage:10:page:1');

        $this->assertNotNull($cachedBefore, 'Product list should be cached');

        // Create holds on different products
        $this->postJson($this->getCreateHoldUrl(), ['product_id' => 10, 'qty' => 5])->assertStatus(201);
        $this->postJson($this->getCreateHoldUrl(), ['product_id' => 11, 'qty' => 7])->assertStatus(201);

        // After holds, cache should be invalidated and rebuilt
        $cachedAfter = Cache::tags(['products'])->get('products:perPage:10:page:1');

        $this->assertNull($cachedAfter, 'Product list cache should be invalidated after hold');

        $response = $this->getJson($listUrl);

        $response->assertStatus(200);

        $items = collect($response->json('data.list'))->keyBy('id');

        $this->assertEquals(45, $items[10]['stock']);

        $this->assertEquals(53, $items[11]['stock']);

        $this->assertEquals(70, $items[12]['stock']);
    }
}
