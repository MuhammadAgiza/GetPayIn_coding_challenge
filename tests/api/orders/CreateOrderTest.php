<?php

namespace Tests\Api\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;
use App\Models\Hold;
use App\Models\Order;
use App\Enums\HoldStatusEnum;
use App\Enums\OrderStatusEnum;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    protected $product;
    protected $hold;
    protected $originalStock = 10;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create(['stock' => $this->originalStock]);
        $this->hold = Hold::create([
            'product_id' => $this->product->id,
            'qty' => 3,
            'expires_at' => now()->addMinutes(2),
            'status' => HoldStatusEnum::Active,
        ]);
        $this->product->decrement('stock', $this->hold->qty);
    }

    protected function getCreateOrderUrl()
    {
        return route('api.order.create', [], false);
    }

    public function test_it_creates_an_order_and_updates_hold_status()
    {
        $response = $this->postJson($this->getCreateOrderUrl(), [
            'hold_id' => $this->hold->id,
        ]);
        $response->assertStatus(201);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals($this->hold->id, $order->hold_id);
        $this->hold->refresh();
        $this->assertEquals(HoldStatusEnum::Used, $this->hold->status);
        $this->assertEquals(OrderStatusEnum::Pending, $order->status);
    }

    public function test_it_returns_error_for_expired_hold()
    {
        $this->hold->update(['expires_at' => now()->subMinute()]);
        $response = $this->postJson($this->getCreateOrderUrl(), [
            'hold_id' => $this->hold->id,
        ]);
        $response->assertStatus(422);
    }

    public function test_it_returns_error_for_non_active_hold()
    {
        $this->hold->update(['status' => HoldStatusEnum::Expired]);
        $response = $this->postJson($this->getCreateOrderUrl(), [
            'hold_id' => $this->hold->id,
        ]);
        $response->assertStatus(422);
    }

    public function test_it_returns_404_if_hold_not_found()
    {
        $response = $this->postJson($this->getCreateOrderUrl(), [
            'hold_id' => 999999,
        ]);
        $response->assertStatus(422);
    }
}
