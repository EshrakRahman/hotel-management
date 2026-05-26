<?php

use App\Enums\BookingStatus;
use App\Enums\PromotionsDiscountType;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Hotel;
use App\Models\Promotion;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

beforeEach(function () {
    // 1. Set up the client role required by AuthController
    Role::firstOrCreate(['name' => 'customer']);

    // 2. Set up a default hotel with destination, room types, physical rooms, and cancellation policy
    $this->hotel = Hotel::factory()->create();
    $this->roomType = RoomType::factory()->create([
        'hotel_id' => $this->hotel->id,
        'base_price' => 100.00,
    ]);

    // Create 2 physical rooms for this room type
    $this->room1 = Room::factory()->create([
        'room_type_id' => $this->roomType->id,
        'status' => RoomStatus::AVAILABLE,
    ]);
    $this->room2 = Room::factory()->create([
        'room_type_id' => $this->roomType->id,
        'status' => RoomStatus::AVAILABLE,
    ]);

    // 3. Set up the customer model
    $this->user = User::factory()->create();
    $this->user->assignRole('customer');
});

test('guest (unauthenticated) cannot place a booking', function () {
    /** @var TestCase $this */
    // No Sanctum::actingAs() called

    $response = $this->postJson('/api/v1/bookings', [
        'hotel_id' => $this->hotel->id,
        'items' => [
            [
                'room_type_id' => $this->roomType->id,
                'check_in' => now()->addDays(1)->format('Y-m-d'),
                'check_out' => now()->addDays(3)->format('Y-m-d'),
            ],
        ],
        'guests' => [
            ['name' => 'Primary Guest', 'email' => 'guest@test.com', 'is_primary' => true],
        ],
    ]);

    $response->assertStatus(401);
});

test('customer can successfully book a room with correct calculations', function () {
    /** @var TestCase $this */
    Sanctum::actingAs($this->user);

    // Create an active service for this hotel
    $service = Service::factory()->create([
        'hotel_id' => $this->hotel->id,
        'base_price' => 20.00,
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/bookings', [
        'hotel_id' => $this->hotel->id,
        'items' => [
            [
                'room_type_id' => $this->roomType->id,
                'check_in' => now()->addDays(1)->format('Y-m-d'),
                'check_out' => now()->addDays(3)->format('Y-m-d'), // 2 nights
            ],
        ],
        'guests' => [
            ['name' => 'Primary Guest', 'email' => 'guest@test.com', 'is_primary' => true],
        ],
        'services' => [
            [
                'service_id' => $service->id,
                'quantity' => 2, // $40.00
            ],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'booking_ref',
                'status',
                'pricing' => ['room_subtotal', 'service_subtotal', 'tax_amount', 'platform_fee', 'total_amount'],
                'items',
                'guests',
                'services',
            ],
        ]);

    // Assert base calculations:
    // Room subtotal: 2 nights * $100 = $200.00
    // Service subtotal: 2 * $20 = $40.00
    // Discount: 0.00
    // Net: $240.00
    // Platform fee (10%): $24.00
    // Tax amount (10%): $24.00
    // Total amount: $240 + $24 + $24 = $288.00
    expect($response['data']['pricing']['room_subtotal'])->toBe('200.00');
    expect($response['data']['pricing']['service_subtotal'])->toBe('40.00');
    expect($response['data']['pricing']['platform_fee'])->toBe('24.00');
    expect($response['data']['pricing']['tax_amount'])->toBe('24.00');
    expect($response['data']['pricing']['total_amount'])->toBe('288.00');

    // Assert physical room allocated
    expect($response['data']['items'][0]['room_number'])->not->toBeNull();

    // Assert database records exist
    $this->assertDatabaseHas('bookings', [
        'user_id' => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status' => 'pending',
        'total_amount' => 288.00,
    ]);
});

test('fails to book when no rooms of that type are available', function () {
    /** @var TestCase $this */
    Sanctum::actingAs($this->user);

    $checkIn = now()->addDays(1)->format('Y-m-d');
    $checkOut = now()->addDays(3)->format('Y-m-d');

    // Occupy both rooms for these dates
    createBookingForRoom($this->room1, $checkIn, $checkOut);
    createBookingForRoom($this->room2, $checkIn, $checkOut);

    // Attempt a third booking on the same dates (only 2 rooms exist in hotel)
    $response = $this->postJson('/api/v1/bookings', [
        'hotel_id' => $this->hotel->id,
        'items' => [
            [
                'room_type_id' => $this->roomType->id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
            ],
        ],
        'guests' => [
            ['name' => 'Failed Guest', 'is_primary' => true],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.room_type_id']);
});

test('applies percentage promo code correctly to the total', function () {
    /** @var TestCase $this */
    Sanctum::actingAs($this->user);

    $promo = Promotion::factory()->create([
        'discount_type' => PromotionsDiscountType::PERCENTAGE,
        'discount_value' => 10.00, // 10%
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/bookings', [
        'hotel_id' => $this->hotel->id,
        'promotion_id' => $promo->id,
        'items' => [
            [
                'room_type_id' => $this->roomType->id,
                'check_in' => now()->addDays(1)->format('Y-m-d'),
                'check_out' => now()->addDays(2)->format('Y-m-d'), // 1 night = $100
            ],
        ],
        'guests' => [
            ['name' => 'Promo Guest', 'is_primary' => true],
        ],
    ]);

    $response->assertStatus(201);

    // Room Subtotal: $100.00
    // Discount: 10% of $100.00 = $10.00
    // Net: $90.00
    // Platform fee (10%): $9.00
    // Tax (10%): $9.00
    // Total: $90 + $9 + $9 = $108.00
    expect($response['data']['pricing']['discount_amount'])->toBe('10.00');
    expect($response['data']['pricing']['total_amount'])->toBe('108.00');
});

test('calculates cancellation penalty correctly when outside free cancellation days', function () {
    /** @var TestCase $this */
    Sanctum::actingAs($this->user);

    // Policy requires 3 days advance notice for free cancel; otherwise 50% penalty
    $policy = $this->hotel->cancellationPolicy;
    $policy->update([
        'free_cancellation_days' => 3,
        'cancellation_fee' => 50.00,
    ]);

    // Create a confirmed booking checking in tomorrow (1 day notice)
    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status' => BookingStatus::CONFIRMED,
        'total_amount' => 200.00,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'room_type_id' => $this->roomType->id,
        'room_id' => $this->room1->id,
        'check_in' => now()->addDay()->format('Y-m-d'),
        'check_out' => now()->addDays(3)->format('Y-m-d'),
        'subtotal' => 200.00,
    ]);

    $response = $this->postJson("/api/v1/bookings/{$booking->booking_ref}/cancel");

    $response->assertStatus(200);

    // Assert status updated and 50% penalty applied ($100.00)
    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status' => 'cancelled',
        'cancellation_penalty' => 100.00,
    ]);
});

// Helper function to create overlapping bookings
function createBookingForRoom(Room $room, string $checkIn, string $checkOut): void
{
    $booking = Booking::factory()->create([
        'status' => BookingStatus::CONFIRMED,
        'created_at' => now(),
    ]);

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'room_type_id' => $room->room_type_id,
        'room_id' => $room->id,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'price_at_booking' => 100.00,
        'nights' => 2,
        'subtotal' => 200.00,
    ]);
}
