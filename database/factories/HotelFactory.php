<?php

namespace Database\Factories;

use App\Enums\HotelStatus;
use App\Models\CancellationPolicy;
use App\Models\Destination;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hotel>
 */
class HotelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'destination_id' => Destination::factory(),
            'cancellation_policy_id' => CancellationPolicy::factory(),
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'status' => HotelStatus::ACTIVE,

        ];
    }
}
