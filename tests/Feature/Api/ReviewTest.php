<?php

use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Hotel;
use App\Models\Review;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'customer']);

    $this->hotel = Hotel::factory()->create();
    $this->roomType = RoomType::factory()->create([
        'hotel_id' => $this->hotel->id,
        'base_price' => 100.00,
    ]);

    $this->room = Room::factory()->create([
        'room_type_id' => $this->roomType->id,
        'status' => RoomStatus::AVAILABLE,
    ]);

    $this->user = User::factory()->create();
    $this->user->assignRole('customer');

    $this->otherUser = User::factory()->create();
    $this->otherUser->assignRole('customer');
});

test('customer can submit a review for a completed/past booking', function () {
    /** @var TestCase $this */
    Sanctum::actingAs($this->user);

    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'room_type_id' => $this->roomType->id,
        'room_id' => $this->room->id,
        'check_in' => now()->subDays(5)->format('Y-m-d'),
        'check_out' => now()->subDays(3)->format('Y-m-d'),
    ]);

    $response = $this->postJson("/api/v1/bookings/{$booking->booking_ref}/reviews", [
        'rating' => 5,
        'comment' => 'Outstanding experience!',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.rating', 5)
        ->assertJsonPath('data.comment', 'Outstanding experience!')
        ->assertJsonPath('data.user.name', $this->user->name);

    $this->assertDatabaseHas('reviews', [
        'booking_id' => $booking->id,
        'user_id' => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'rating' => 5,
        'comment' => 'Outstanding experience!',
    ]);

    // Check that average rating is updated on the hotel detail
    $hotelResponse = $this->getJson("/api/v1/hotels/{$this->hotel->slug}");
    $hotelResponse->assertStatus(200)
        ->assertJsonPath('data.average_rating', '5.0')
        ->assertJsonPath('data.reviews_count', 1);
});

test('customer cannot review other users booking', function () {
    /** @var TestCase $this */
    Sanctum::actingAs($this->user);

    $booking = Booking::factory()->create([
        'user_id' => $this->otherUser->id,
        'hotel_id' => $this->hotel->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'room_type_id' => $this->roomType->id,
        'room_id' => $this->room->id,
        'check_in' => now()->subDays(5)->format('Y-m-d'),
        'check_out' => now()->subDays(3)->format('Y-m-d'),
    ]);

    $response = $this->postJson("/api/v1/bookings/{$booking->booking_ref}/reviews", [
        'rating' => 4,
    ]);

    $response->assertStatus(403);
});

test('customer cannot review a future booking', function () {
    /** @var TestCase $this */
    Sanctum::actingAs($this->user);

    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'room_type_id' => $this->roomType->id,
        'room_id' => $this->room->id,
        'check_in' => now()->addDays(2)->format('Y-m-d'),
        'check_out' => now()->addDays(4)->format('Y-m-d'),
    ]);

    $response = $this->postJson("/api/v1/bookings/{$booking->booking_ref}/reviews", [
        'rating' => 4,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['booking']);
});

test('customer cannot review a cancelled booking', function () {
    /** @var TestCase $this */
    Sanctum::actingAs($this->user);

    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status' => BookingStatus::CANCELLED,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'room_type_id' => $this->roomType->id,
        'room_id' => $this->room->id,
        'check_in' => now()->subDays(5)->format('Y-m-d'),
        'check_out' => now()->subDays(3)->format('Y-m-d'),
    ]);

    $response = $this->postJson("/api/v1/bookings/{$booking->booking_ref}/reviews", [
        'rating' => 4,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['booking']);
});

test('customer cannot submit multiple reviews for the same booking', function () {
    /** @var TestCase $this */
    Sanctum::actingAs($this->user);

    $booking = Booking::factory()->create([
        'user_id' => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    BookingItem::factory()->create([
        'booking_id' => $booking->id,
        'room_type_id' => $this->roomType->id,
        'room_id' => $this->room->id,
        'check_in' => now()->subDays(5)->format('Y-m-d'),
        'check_out' => now()->subDays(3)->format('Y-m-d'),
    ]);

    // First review
    Review::create([
        'booking_id' => $booking->id,
        'user_id' => $this->user->id,
        'hotel_id' => $this->hotel->id,
        'rating' => 5,
        'comment' => 'First comment',
    ]);

    // Second review attempt
    $response = $this->postJson("/api/v1/bookings/{$booking->booking_ref}/reviews", [
        'rating' => 3,
        'comment' => 'Second comment',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['booking']);
});

test('public can view reviews list for a hotel', function () {
    /** @var TestCase $this */
    $reviewsCount = 3;
    for ($i = 0; $i < $reviewsCount; $i++) {
        $u = User::factory()->create();
        $b = Booking::factory()->create(['hotel_id' => $this->hotel->id, 'user_id' => $u->id]);
        Review::create([
            'booking_id' => $b->id,
            'user_id' => $u->id,
            'hotel_id' => $this->hotel->id,
            'rating' => 4,
            'comment' => "Review comment {$i}",
        ]);
    }

    $response = $this->getJson("/api/v1/hotels/{$this->hotel->slug}/reviews");

    $response->assertStatus(200)
        ->assertJsonCount($reviewsCount, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'rating', 'comment', 'created_at', 'user' => ['id', 'name']],
            ],
        ]);
});
