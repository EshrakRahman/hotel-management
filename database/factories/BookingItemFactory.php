<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingItem>
 */
class BookingItemFactory extends Factory
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
            'room_type_id' => RoomType::factory(),
            'room_id' => Room::factory(),
            'check_in' => now()->addDay()->format('Y-m-d'),
            'check_out' => now()->addDays(3)->format('Y-m-d'),
            'price_at_booking' => fake()->randomFloat(2, 50, 300),
            'nights' => 2,
            'subtotal' => fake()->randomFloat(2, 100, 600),
        ];
    }
}
