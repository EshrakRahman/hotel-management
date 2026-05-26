<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hotel_id' => Hotel::factory(),
            'name' => fake()->word().' Service',
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'base_price' => fake()->randomFloat(2, 5, 50),
            'is_active' => true,
            'is_per_person' => false,
        ];
    }
}
