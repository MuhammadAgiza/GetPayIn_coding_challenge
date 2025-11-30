<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;
use App\Enums\HoldStatusEnum;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hold>
 */
class HoldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = Product::factory()->create();
        return [
            'product_id' => $product->id,
            'qty' => $this->faker->numberBetween(1, max(1, $product->stock - 1)),
            'status' => HoldStatusEnum::Active,
            'expires_at' => now()->addMinutes(2),
        ];
    }

    public function expired()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => HoldStatusEnum::Expired,
                'expires_at' => now()->subMinutes(1),
            ];
        });
    }

    public function used()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => HoldStatusEnum::Used,
            ];
        });
    }
}
