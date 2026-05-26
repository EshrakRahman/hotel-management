<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingService;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingService>
 */
class BookingServiceFactory extends Factory
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
            'serviceable_id' => Service::factory(),
            'serviceable_type' => Service::class,
            'price_at_booking' => fake()->randomFloat(2, 10, 100),
            'quantity' => 1,
        ];
    }
}
