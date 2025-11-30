<?php

namespace App\Services;

use App\DTOs\PaymentDTO;
use App\Enums\PaymentStatusEnum;
use App\Models\Order;
use App\Models\PaymentLog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(protected OrderService $orderService)
    {
    }

    public function webhook(PaymentDTO $data)
    {
        if (PaymentLog::where('payment_reference', $data->reference)->exists()) {
            Log::info('Payment webhook deduplicated', [
                'reference' => $data->reference,
                'order_id' => $data->order_id,
            ]);
            return;
        }
        try {
            DB::transaction(function () use ($data) {
                $order = Order::lockForUpdate()->findOrFail($data->order_id);

                PaymentLog::create([
                    'order_id' => $data->order_id,
                    'payment_reference' => $data->reference,
                    'status' => $data->status,
                    'gateway_payload' => $data->payload,
                ]);

                if ($data->status === PaymentStatusEnum::Success) {
                    $this->orderService->markOrderPaid($order);
                }

                if ($data->status === PaymentStatusEnum::Failure) {
                    $this->orderService->cancelOrder($order);
                }

            });
        } catch (ModelNotFoundException $e) {
            Log::warning('Payment webhook received before order exists', [
                'reference' => $data->reference,
                'order_id' => $data->order_id,
            ]);
            abort(422, 'Order does not exist');
        } catch (\Throwable $e) {
            Log::error('Payment webhook processing error', [
                'reference' => $data->reference,
                'order_id' => $data->order_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
