<?php

namespace Database\Factories;

use App\Models\CancellationPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CancellationPolicy>
 */
class CancellationPolicyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'description' => fake()->sentence(),
            'free_cancellation_days' => fake()->numberBetween(0, 30),
            'cancellation_fee' => fake()->randomFloat(2, 0, 500),
        ];
    }
}
