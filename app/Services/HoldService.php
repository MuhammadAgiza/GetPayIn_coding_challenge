<?php

namespace App\Services;

use App\DTOs\HoldDTO;
use App\Enums\HoldStatusEnum;
use App\Models\Hold;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class HoldService
{
    public function createHold(HoldDTO $data)
    {
        $attempts = 0;
        retry(3, function () use ($data, &$attempts) {
            $attempts++;
            try {
                $hold = null;
                DB::transaction(function () use ($data, &$hold) {
                    $product = Product::lockForUpdate()->findOrFail($data->product_id);
                    if ($product->stock < $data->qty) {
                        abort(422, 'Not enough stock');
                    }
                    $product->decrement('stock', $data->qty);

                    $hold = Hold::create([
                        'product_id' => $product->id,
                        'qty' => $data->qty,
                        'expires_at' => now()->addMinutes(2),
                        'status' => HoldStatusEnum::Active,
                    ]);
                });
                return $hold;
            } catch (QueryException $e) {
                if ($e->getCode() === '40001') { // MySQL deadlock
                    Log::warning('DB deadlock on hold creation', [
                        'product_id' => $$data->product_id,
                        'qty' => $data->qty,
                        'attempt' => $attempts,
                        'error' => $e->getMessage(),
                    ]);
                }
                throw $e;
            }
        }, 50);
    }

    public function releaseExpiredHolds(int $chunk)
    {
        Hold::where('status', HoldStatusEnum::Active)
            ->where('expires_at', '<', now())
            ->chunkById($chunk, function ($holds) {
                foreach ($holds as $hold) {
                    DB::transaction(function () use ($hold) {
                        $active = Hold::lockForUpdate()->find($hold->id);
                        if (!$active || $active->status !== HoldStatusEnum::Active) {
                            return;
                        }

                        Product::find($active->product_id)
                            ->increment('stock', $active->qty);

                        $active->update(['status' => HoldStatusEnum::Expired]);

                        Log::info('Hold expired and released', [
                            'hold_id' => $active->id,
                            'product_id' => $active->product_id,
                            'qty' => $active->qty,
                        ]);
                    });
                }
            });
    }

    public function cancelHold(Hold $_hold)
    {
        DB::transaction(function () use ($_hold) {
            $hold = Hold::lockForUpdate()->find($_hold->id);

            if ($hold->status == HoldStatusEnum::Expired) {
                return;
            }

            Product::find($hold->product_id)
                ->increment('stock', $hold->qty);

            $hold->update(['status' => HoldStatusEnum::Cancelled]);
        });
    }


}
