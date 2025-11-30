<?php

namespace Tests\Api\Products;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;

class ShowProductTest extends TestCase
{
    use RefreshDatabase;

    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create();
    }

    protected function getShowProductUrl($id)
    {
        return route('api.product.show', ['id' => $id], false);
    }

    public function test_it_shows_a_product_successfully()
    {
        $response = $this->getJson($this->getShowProductUrl($this->product->id));

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'product' => [
                    'id',
                    'price',
                    'name',
                    'stock',
                ]
            ],
        ]);

        $response->assertJson([
            'success' => true,
            'status_code' => 200,
            'data' => [
                'product' => [
                    'id' => $this->product->id,
                    'price' => $this->product->price,
                    'name' => $this->product->name,
                    'stock' => $this->product->stock,
                ]
            ]
        ]);
    }

    public function test_it_returns_correct_data_types_in_response()
    {
        $response = $this->getJson($this->getShowProductUrl($this->product->id));
        $response->assertStatus(200);

        $responseData = $response->json('data.product');

        $this->assertIsInt($responseData['id']);
        $this->assertIsString($responseData['name']);
        $this->assertIsNumeric($responseData['price']);
        $this->assertIsInt($responseData['stock']);
    }

    public function test_it_returns_404_if_product_not_found()
    {
        $response = $this->getJson($this->getShowProductUrl(999999));
        $response->assertStatus(404);
    }

}
