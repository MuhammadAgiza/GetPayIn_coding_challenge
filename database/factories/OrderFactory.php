<?php

namespace Database\Factories;

use App\Enums\OrderStatusEnum;
use App\Models\Hold;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hold = Hold::factory()->used()->create();
        return [
            'hold_id' => $hold->id,
            'status' => OrderStatusEnum::Pending,
            'paid_at' => null,
            'expires_at' => now()->addMinutes(5),
        ];
    }
}
