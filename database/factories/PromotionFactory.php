<?php

namespace Database\Factories;

use App\Enums\PromotionsDiscountType;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word().' Sale',
            'promo_code' => strtoupper(fake()->unique()->lexify('PROMO-???')),
            'discount_type' => PromotionsDiscountType::PERCENTAGE,
            'discount_value' => fake()->randomFloat(2, 5, 20),
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'is_active' => true,
        ];
    }
}
