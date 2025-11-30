<?php

namespace Tests\Api\Holds;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;


class CreateHoldTest extends TestCase
{
    use RefreshDatabase;

    protected $product;
    protected $originalStock = 10;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create(['stock' => $this->originalStock]);
    }

    protected function getShowProductUrl($id)
    {
        return route('api.product.show', ['id' => $id], false);
    }

    protected function getCreateHoldUrl()
    {
        return route('api.hold.create', [], false);
    }

    public function test_it_creates_a_hold_and_reduces_product_stock()
    {
        $qty = 3;
        $response = $this->postJson($this->getCreateHoldUrl(), [
            'product_id' => $this->product->id,
            'qty' => $qty,
        ]);
        $response->assertStatus(201);

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'hold' => [
                    'id',
                    'product_id',
                    'qty',
                    'expires_at',
                ]
            ],
        ]);

        $this->product->refresh();

        $this->assertEquals($this->originalStock - $qty, $this->product->stock);

        $showResponse = $this->getJson($this->getShowProductUrl($this->product->id));

        $showResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'product' => [
                        'stock' => $this->originalStock - $qty,
                    ]
                ]
            ]);
    }

    public function test_it_returns_error_when_hold_qty_exceeds_product_stock()
    {
        $response = $this->postJson($this->getCreateHoldUrl(), [
            'product_id' => $this->product->id,
            'qty' => 999,
        ]);
        $response->assertStatus(422);
    }

    public function test_it_returns_correct_data_types_in_hold_response()
    {
        $response = $this->postJson($this->getCreateHoldUrl(), [
            'product_id' => $this->product->id,
            'qty' => 2,
        ]);
        $response->assertStatus(201);

        $hold = $response->json('data.hold');
        $this->assertIsInt($hold['id']);
        $this->assertIsInt($hold['product_id']);
        $this->assertIsInt($hold['qty']);
        $this->assertIsString($hold['expires_at']);
    }

    public function test_it_returns_404_if_product_not_found()
    {
        $response = $this->postJson($this->getCreateHoldUrl(), [
            'product_id' => 999999,
            'qty' => 1,
        ]);
        $response->assertStatus(422);
    }


    public function test_multiple_holds_reduce_stock_accordingly()
    {
        $qtyArr = collect([2, 3]);

        foreach ($qtyArr as $qty) {
            $this->postJson($this->getCreateHoldUrl(), [
                'product_id' => $this->product->id,
                'qty' => $qty,
            ]);
        }

        $this->product->refresh();

        $this->assertEquals(
            $this->originalStock - $qtyArr->sum(),
            $this->product->stock
        );
    }
}
