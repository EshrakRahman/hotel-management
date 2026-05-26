<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingGuest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingGuest>
 */
class BookingGuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'is_primary' => false,
        ];
    }
}
