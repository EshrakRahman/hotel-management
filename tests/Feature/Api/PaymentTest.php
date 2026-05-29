<?php

use App\Enums\BookingStatus;
use App\Enums\paymentStatus;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

beforeEach(function () {
    // Set up client role
    Role::firstOrCreate(['name' => 'customer']);

    // Configure payment driver config override for tests to use the 'mock' driver
    config(['payment.default' => 'mock']);

    // Set up default hotel, room types, and physical rooms
    $this->hotel = Hotel::factory()->create();
    $this->roomType = RoomType::factory()->create([
        'hotel_id' => $this->hotel->id,
        'base_price' => 100.00,
    ]);
    $this->room = Room::factory()->create([
        'room_type_id' => $this->roomType->id,
        'status' => RoomStatus::AVAILABLE,
    ]);

    // Set up default customer
    $this->user = User::factory()->create();
    $this->user->assignRole('customer');
});

test('customer can successfully create checkout session with mock driver', function () {
    /** @var TestCase $this */
    Sanctum::actingAs($this->user);

    // Create a pending booking
    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status' => BookingStatus::PENDING,
        'payment_status' => paymentStatus::PENDING,
        'total_amount' => 200.00,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'room_type_id' => $this->roomType->id,
        'room_id' => $this->room->id,
        'subtotal' => 200.00,
    ]);

    $response = $this->postJson("/api/v1/bookings/{$booking->booking_ref}/checkout-session", [
        'success_url' => 'https://frontend.test/payments/success',
        'cancel_url' => 'https://frontend.test/payments/cancel',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['session_id', 'checkout_url']);

    expect($response['checkout_url'])->toContain('mock-payment-gateway.test');
});

test('stripe webhook checkout completed event confirms booking', function () {
    /** @var TestCase $this */
    // Create a pending booking
    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status' => BookingStatus::PENDING,
        'payment_status' => paymentStatus::PENDING,
        'total_amount' => 200.00,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'room_type_id' => $this->roomType->id,
        'room_id' => $this->room->id,
        'subtotal' => 200.00,
    ]);

    // Send mock stripe completed checkout session event
    $response = $this->postJson('/api/v1/payments/webhook/stripe', [
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'sess_test_998877',
                'metadata' => [
                    'booking_ref' => $booking->booking_ref,
                ],
            ],
        ],
    ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Booking confirmed and payment processed']);

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status' => 'confirmed',
        'payment_status' => 'paid',
    ]);

    $this->assertDatabaseHas('payments', [
        'booking_id' => $booking->id,
        'transaction_id' => 'sess_test_998877',
        'gateway' => 'stripe',
        'status' => 'paid',
    ]);
});

test('expired holds are automatically cancelled by release holds command', function () {
    /** @var TestCase $this */
    // Create an expired pending booking (20 minutes ago)
    $expiredBooking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status' => BookingStatus::PENDING,
        'payment_status' => paymentStatus::PENDING,
        'total_amount' => 100.00,
        'created_at' => now()->subMinutes(20),
    ]);

    // Create a current pending booking (2 minutes ago - should NOT be cancelled)
    $currentBooking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status' => BookingStatus::PENDING,
        'payment_status' => paymentStatus::PENDING,
        'total_amount' => 100.00,
        'created_at' => now()->subMinutes(2),
    ]);

    // Trigger expired holds release
    Artisan::call('bookings:release-expired-holds');

    $this->assertDatabaseHas('bookings', [
        'id' => $expiredBooking->id,
        'status' => 'cancelled',
        'payment_status' => 'failed',
    ]);

    $this->assertDatabaseHas('bookings', [
        'id' => $currentBooking->id,
        'status' => 'pending',
        'payment_status' => 'pending',
    ]);
});
