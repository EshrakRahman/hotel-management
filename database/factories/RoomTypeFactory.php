<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomType>
 */
class RoomTypeFactory extends Factory
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
            'name' => fake()->word().' Suite',
            'slug' => fake()->unique()->slug(),
            'base_price' => fake()->randomFloat(2, 50, 300),
            'max_occupancy' => fake()->numberBetween(1, 4),
            'description' => fake()->sentence(),
        ];
    }
}
