<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_ref' => strtoupper(fake()->unique()->lexify('BK-????-????')),
            'user_id' => User::factory(),
            'hotel_id' => Hotel::factory(),
            'promotion_id' => null,
            'total_amount' => fake()->randomFloat(2, 100, 1000),
            'tax_amount' => fake()->randomFloat(2, 10, 100),
            'platform_fee' => fake()->randomFloat(2, 10, 100),
            'total_service_amount' => 0.00,
            'status' => BookingStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'special_request' => fake()->sentence(),
        ];
    }
}
