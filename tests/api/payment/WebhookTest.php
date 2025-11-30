<?php

namespace Tests\Api\Payment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Order;
use App\Models\PaymentLog;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function getWebhookUrl()
    {
        return route('api.payment.webhook', [], false);
    }

    public function test_webhook_creates_payment_log_and_marks_order_paid()
    {
        $order = Order::factory()->create(['status' => OrderStatusEnum::Pending]);
        $payload = [
            'order_id' => $order->id,
            'reference' => 'ref-123',
            'status' => PaymentStatusEnum::Success,
        ];

        $response = $this->postJson($this->getWebhookUrl(), $payload);
        $response->assertStatus(200);

        $this->assertDatabaseHas('payment_logs', [
            'order_id' => $order->id,
            'payment_reference' => 'ref-123',
            'status' => PaymentStatusEnum::Success,
        ]);

        $order->refresh();
        $this->assertEquals(OrderStatusEnum::Paid, $order->status);
    }

    public function test_webhook_idempotency_same_reference()
    {
        $order = Order::factory()->create(['status' => OrderStatusEnum::Pending]);
        $payload = [
            'order_id' => $order->id,
            'reference' => 'ref-dup',
            'status' => PaymentStatusEnum::Success,
        ];

        $this->postJson($this->getWebhookUrl(), $payload)->assertStatus(200);
        $this->postJson($this->getWebhookUrl(), $payload)->assertStatus(200);

        $this->assertEquals(1, PaymentLog::where('payment_reference', 'ref-dup')->count());
    }

    public function test_webhook_failure_marks_order_failed()
    {
        $order = Order::factory()->create(['status' => OrderStatusEnum::Pending]);
        $payload = [
            'order_id' => $order->id,
            'reference' => 'ref-fail',
            'status' => PaymentStatusEnum::Failure,
        ];

        $this->postJson($this->getWebhookUrl(), $payload)->assertStatus(200);

        $order->refresh();
        $this->assertEquals(OrderStatusEnum::Failed, $order->status);
    }

    public function test_webhook_before_order_creation_logs_and_skips()
    {
        // Simulate webhook before order exists
        $payload = [
            'order_id' => 999999,
            'reference' => 'ref-missing',
            'status' => PaymentStatusEnum::Success,
        ];

        $response = $this->postJson($this->getWebhookUrl(), $payload);
        $response->assertStatus(422);

        $this->assertDatabaseMissing('payment_logs', [
            'payment_reference' => 'ref-missing',
        ]);
    }

    public function test_webhook_same_order_at_the_same_time()
    {
        $order = Order::factory()->create(['status' => OrderStatusEnum::Pending]);
        $payload = [
            'order_id' => $order->id,
            'reference' => 'ref-concurrent',
            'status' => PaymentStatusEnum::Success,
        ];

        $this->postJson($this->getWebhookUrl(), $payload)->assertStatus(200);
        $this->postJson($this->getWebhookUrl(), $payload)->assertStatus(200);

        $this->assertEquals(1, PaymentLog::where('payment_reference', 'ref-concurrent')->count());
    }
}
