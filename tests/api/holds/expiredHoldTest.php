<?php

namespace Tests\Api\Holds;

use App\Enums\HoldStatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;
use App\Models\Hold;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class ExpiredHoldTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getShowProductUrl($id)
    {
        return route('api.product.show', ['id' => $id], false);
    }

    protected function getCreateHoldUrl()
    {
        return route('api.hold.create', [], false);
    }

    public function test_expired_hold_releases_stock()
    {
        $qty = 4;
        $stock = 10;
        $product = Product::factory()->create(['stock' => $stock]);

        $hold = Hold::create([
            'product_id' => $product->id,
            'qty' => $qty,
            'expires_at' => now()->subMinute(),
            'status' => HoldStatusEnum::Active,
        ]);

        $product->decrement('stock', $qty);

        $this->artisan('holds:release-expired');

        $product->refresh();
        $hold->refresh();

        $this->assertEquals($stock, $product->stock, 'Stock should be restored after hold expires');
        $this->assertEquals(HoldStatusEnum::Expired, $hold->status, 'Hold status should be expired');
    }

    public function test_only_expired_holds_are_released()
    {
        $qty_expired = 5;
        $qty_active = 3;
        $stock = 20;

        $product = Product::factory()->create(['stock' => $stock]);
        $expiredHold = Hold::create([
            'product_id' => $product->id,
            'qty' => $qty_expired,
            'expires_at' => now()->subMinutes(2),
            'status' => HoldStatusEnum::Active,
        ]);
        $activeHold = Hold::create([
            'product_id' => $product->id,
            'qty' => $qty_active,
            'expires_at' => now()->addMinutes(2),
            'status' => HoldStatusEnum::Active,
        ]);

        $product->decrement('stock', $qty_expired + $qty_active);

        $this->artisan('holds:release-expired');

        $product->refresh();
        $expiredHold->refresh();
        $activeHold->refresh();

        $this->assertEquals($stock - $qty_active, $product->stock, 'Only expired hold should restore stock');
        $this->assertEquals(HoldStatusEnum::Expired, $expiredHold->status);
        $this->assertEquals(HoldStatusEnum::Active, $activeHold->status);
    }

    public function test_expired_hold_not_released_twice()
    {
        $stock = 15;
        $qty = 7;
        $product = Product::factory()->create(['stock' => $stock]);
        $hold = Hold::create([
            'product_id' => $product->id,
            'qty' => $qty,
            'expires_at' => now()->subMinutes(5),
            'status' => HoldStatusEnum::Active,
        ]);
        $product->decrement('stock', $qty);

        $this->artisan('holds:release-expired');
        $this->artisan('holds:release-expired');

        $product->refresh();
        $hold->refresh();

        $this->assertEquals($stock, $product->stock, 'Stock should not be restored more than once');
        $this->assertEquals(HoldStatusEnum::Expired, $hold->status);
    }

    public function test_no_release_for_non_expired_holds()
    {
        $stock = 30;
        $qty = 10;
        $product = Product::factory()->create(['stock' => $stock]);
        $hold = Hold::create([
            'product_id' => $product->id,
            'qty' => $qty,
            'expires_at' => now()->addMinutes(10),
            'status' => HoldStatusEnum::Active,
        ]);
        $product->decrement('stock', $qty);

        $this->artisan('holds:release-expired');

        $product->refresh();
        $hold->refresh();

        $this->assertEquals($stock - $qty, $product->stock, 'Stock should not change for non-expired hold');
        $this->assertEquals(HoldStatusEnum::Active, $hold->status);
    }

    public function test_product_cache_is_updated_after_hold_expiry()
    {
        $stock = 12;
        $qty = 2;
        $product = Product::factory()->create(['stock' => $stock]);

        $hold = Hold::create([
            'product_id' => $product->id,
            'qty' => $qty,
            'expires_at' => now()->subMinute(),
            'status' => HoldStatusEnum::Active,
        ]);
        $product->decrement('stock', $qty);

        $this->getJson($this->getShowProductUrl($product->id));
        $this->assertNotNull(Cache::get("product:{$product->id}"));

        $this->artisan('holds:release-expired');

        $this->assertNull(Cache::get("product:{$product->id}"));

        $response = $this->getJson($this->getShowProductUrl($product->id));

        $response->assertStatus(200);

        $this->assertEquals($stock, $response->json('data.product.stock'));
    }
}
