<?php

namespace App\Services;

use App\DTOs\OrderDTO;
use App\Enums\HoldStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Models\Hold;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OrderService
{

    public function __construct(protected HoldService $holdService)
    {
    }

    public function createOrder(OrderDTO $data)
    {
        $order = null;
        DB::transaction(function () use ($data, &$order) {
            $hold = Hold::lockForUpdate()->findOrFail($data->hold_id);
            if ($hold->status !== HoldStatusEnum::Active || $hold->expires_at->isPast()) {
                if ($hold->status === HoldStatusEnum::Active) {
                    Product::where('id', $hold->product_id)->increment('stock', $hold->qty);
                    $hold->update(['status' => HoldStatusEnum::Expired]);
                }
                abort(422, 'Hold is invalid or expired');
            }
            $hold->update(['status' => HoldStatusEnum::Used]);
            $order = Order::create([
                'hold_id' => $hold->id,
                'status' => OrderStatusEnum::Pending,
                'expires_at' => now()->addMinutes(5),
            ]);
        });
        return $order;
    }

    public function markOrderPaid(Order $order)
    {
        if ($order->status === OrderStatusEnum::Paid)
            return;

        if (!in_array($order->status, [OrderStatusEnum::Pending]))
            return;

        $order->update([
            'status' => OrderStatusEnum::Paid,
            'paid_at' => now(),
        ]);
    }

    public function cancelOrder(Order $order)
    {
        if (in_array($order->status, [OrderStatusEnum::Failed, OrderStatusEnum::Paid]))
            return;

        $order->update([
            'status' => OrderStatusEnum::Failed,
        ]);

        $this->holdService->cancelHold($order->hold);
    }


    public function CancelExpiredOrders(int $chunk)
    {
        Order::where('status', OrderStatusEnum::Pending)
            ->where('expires_at', '<', now())
            ->chunkById($chunk, function ($orders) {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($order) {
                        $pending = Order::lockForUpdate()->find($order->id);
                        if (!$pending || $pending->status !== OrderStatusEnum::Pending) {
                            return;
                        }

                        Product::where('id', $pending->hold->product_id)
                            ->increment('stock', $pending->hold->qty);

                        Cache::forget("product:{$pending->hold->product_id}");

                        $pending->hold->update(['status' => HoldStatusEnum::Expired]);

                        $pending->update(['status' => OrderStatusEnum::Expired]);
                    });
                }
            });
    }
}
