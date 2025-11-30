<?php

namespace Tests\Api\Products;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;

class ListProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getListProductUrl($params = [])
    {
        return route('api.product.index', $params, false);
    }

    public function test_it_lists_products_successfully()
    {
        $count = 3;
        $products = Product::factory()->count($count)->create();

        $response = $this->getJson($this->getListProductUrl());

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'list' => [
                    '*' => [
                        'id',
                        'price',
                        'name',
                        'stock',
                    ]
                ],
                'pagination' => [
                    'current_page',
                    'total',
                    'per_page'
                ],
            ],
        ]);

        $responseData = $response->json('data.list');
        $this->assertCount($count, $responseData);
        $response->assertJsonPath('data.pagination.total', $count);

    }

    public function test_it_returns_correct_data_types_in_list_response()
    {
        Product::factory()->count(2)->create();

        $response = $this->getJson($this->getListProductUrl());
        $response->assertStatus(200);

        foreach ($response->json('data.list') as $product) {
            $this->assertIsInt($product['id']);
            $this->assertIsString($product['name']);
            $this->assertIsNumeric($product['price']);
            $this->assertIsInt($product['stock']);
        }
    }

    public function test_it_returns_empty_list_when_no_products_exist()
    {
        $response = $this->getJson($this->getListProductUrl());
        $response->assertStatus(200);
        $this->assertEquals([], $response->json('data.list'));
    }

    public function test_it_supports_pagination_if_applicable()
    {
        Product::factory()->count(15)->create();

        $response = $this->getJson($this->getListProductUrl(['page' => 1, 'per_page' => 10]));
        $response->assertStatus(200);

        $products = $response->json('data.list');
        $this->assertLessThanOrEqual(10, count($products));
    }
}
