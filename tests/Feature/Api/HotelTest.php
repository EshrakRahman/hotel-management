<?php

use App\Enums\BookingStatus;
use App\Enums\HotelStatus;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Destination;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use Tests\TestCase;

test('list all active hotels', function () {
    Hotel::factory()->count(10)->create();
    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/hotels');

    $response->assertStatus(200)
        ->assertJsonCount(10, 'data');
});

test('filters out non-active hotels', function () {
    Hotel::factory()->count(1)->create(['status' => HotelStatus::PENDING]);
    Hotel::factory()->count(9)->create();
    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/hotels');

    $response->assertStatus(200)
        ->assertJsonCount(9, 'data');
});

test('returns empty array when no active hotels exist', function () {
    Hotel::factory()->count(1)->create(['status' => HotelStatus::PENDING]);
    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/hotels');

    $response->assertStatus(200)
        ->assertJsonCount(0, 'data');
});

describe('show hotel', function () {

    test('returns a single active hotel by slug', function () {
        $hotel = Hotel::factory()->create();
        /** @var TestCase $this */
        $response = $this->getJson("/api/v1/hotels/{$hotel->slug}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['name' => $hotel->name]]);

        $response->assertJsonStructure([
            'data' => ['id', 'name', 'slug', 'status', 'destination', 'cancellation_policy'],
        ]);
    });

    test('returns 404 for non-existent slug', function () {
        /** @var TestCase $this */
        $hotel = Hotel::factory()->create();
        $response = $this->getJson("/api/v1/hotels/{$hotel->slug}-non-existent");

        $response->assertStatus(404);
    });

    test('returns 404 for pending hotel', function () {
        /** @var TestCase $this */
        $hotel = Hotel::factory()->create(['status' => HotelStatus::PENDING]);
        $response = $this->getJson("/api/v1/hotels/{$hotel->slug}");

        $response->assertStatus(404);
    });
});

describe('search hotels', function () {

    test('can search hotesl by name', function () {
        $hotel = Hotel::factory()->create(['name' => 'Mountain Lodge']);
        $hotel = Hotel::factory()->create(['name' => 'Sea Pearl Resort']);
        /** @var TestCase $this */
        $response = $this->getJson('/api/v1/hotels?q=mountain');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    test('can search hotels by destination', function () {
        Destination::factory()->create(['slug' => 'dhaka']);
        Destination::factory()->create(['slug' => 'rangpur']);
        $hotel = Hotel::factory()->create(['destination_id' => 1]);
        $hotel = Hotel::factory()->create(['destination_id' => 2]);

        /** @var TestCase $this */
        $response = $this->getJson('/api/v1/hotels?destination=dhaka');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    test('filters out fully booked hotels when search dates are provided', function () {
        /** @var TestCase $this */
        $hotel1 = Hotel::factory()->create(); // Hotel with available room
        $roomType1 = RoomType::factory()->create(['hotel_id' => $hotel1->id]);
        $room1 = Room::factory()->create([
            'room_type_id' => $roomType1->id,
            'status' => RoomStatus::AVAILABLE,
        ]);

        $hotel2 = Hotel::factory()->create(); // Hotel with fully booked room
        $roomType2 = RoomType::factory()->create(['hotel_id' => $hotel2->id]);
        $room2 = Room::factory()->create([
            'room_type_id' => $roomType2->id,
            'status' => RoomStatus::AVAILABLE,
        ]);

        // Create a confirmed booking for hotel2 room on those dates
        $booking = Booking::factory()->create(['status' => BookingStatus::CONFIRMED]);
        BookingItem::factory()->create([
            'booking_id' => $booking->id,
            'room_type_id' => $roomType2->id,
            'room_id' => $room2->id,
            'check_in' => '2026-06-01',
            'check_out' => '2026-06-03',
        ]);

        // Request list with dates
        $response = $this->getJson('/api/v1/hotels?check_in=2026-06-01&check_out=2026-06-03');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $hotel1->id);
    });

    test('returns correct available room count per room type when search dates are provided', function () {
        /** @var TestCase $this */
        $hotel = Hotel::factory()->create();
        $roomType = RoomType::factory()->create(['hotel_id' => $hotel->id]);

        // 2 physical rooms
        $room1 = Room::factory()->create([
            'room_type_id' => $roomType->id,
            'status' => RoomStatus::AVAILABLE,
        ]);
        $room2 = Room::factory()->create([
            'room_type_id' => $roomType->id,
            'status' => RoomStatus::AVAILABLE,
        ]);

        // 1 confirmed booking for room1 on these dates
        $booking = Booking::factory()->create(['status' => BookingStatus::CONFIRMED]);
        BookingItem::factory()->create([
            'booking_id' => $booking->id,
            'room_type_id' => $roomType->id,
            'room_id' => $room1->id,
            'check_in' => '2026-06-01',
            'check_out' => '2026-06-03',
        ]);

        // Show hotel with date params
        $response = $this->getJson("/api/v1/hotels/{$hotel->slug}?check_in=2026-06-01&check_out=2026-06-03");

        $response->assertStatus(200)
            ->assertJsonPath('data.room_types.0.available_rooms_count', 1);
    });
});
