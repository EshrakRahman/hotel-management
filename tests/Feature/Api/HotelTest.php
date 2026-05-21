<?php

use App\Enums\HotelStatus;
use App\Models\Destination;
use App\Models\Hotel;

test('list all active hotels', function () {
    Hotel::factory()->count(10)->create();
    /** @var Tests\TestCase $this */
    $response = $this->getJson('/api/v1/hotels');

    $response->assertStatus(200)
        ->assertJsonCount(10, 'data');
});

test('filters out non-active hotels', function () {
    Hotel::factory()->count(1)->create(['status' => HotelStatus::PENDING]);
    Hotel::factory()->count(9)->create();
    /** @var Tests\TestCase $this */
    $response = $this->getJson('/api/v1/hotels');

    $response->assertStatus(200)
        ->assertJsonCount(9, 'data');
});

test('returns empty array when no active hotels exist', function () {
    Hotel::factory()->count(1)->create(['status' => HotelStatus::PENDING]);
    /** @var Tests\TestCase $this */
    $response = $this->getJson('/api/v1/hotels');

    $response->assertStatus(200)
        ->assertJsonCount(0, 'data');
});



describe('show hotel', function () {

    test('returns a single active hotel by slug', function () {
        $hotel = Hotel::factory()->create();
        /** @var Tests\TestCase $this */
        $response = $this->getJson("/api/v1/hotels/{$hotel->slug}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['name' => $hotel->name]]);

        $response->assertJsonStructure([
            'data' => ['id', 'name', 'slug', 'status', 'destination', 'cancellation_policy']
        ]);
    });

    test('returns 404 for non-existent slug', function () {
        /** @var Tests\TestCase $this */
        $hotel = Hotel::factory()->create();
        $response = $this->getJson("/api/v1/hotels/{$hotel->slug}-non-existent");

        $response->assertStatus(404);
    });

    test('returns 404 for pending hotel', function () {
        /** @var Tests\TestCase $this */
        $hotel = Hotel::factory()->create(['status' => HotelStatus::PENDING]);
        $response = $this->getJson("/api/v1/hotels/{$hotel->slug}");

        $response->assertStatus(404);
    });
});

describe('search hotels', function () {

    test('can search hotesl by name', function () {
        $hotel = Hotel::factory()->create(['name' => 'Mountain Lodge']);
        $hotel = Hotel::factory()->create(['name' => 'Sea Pearl Resort']);
        /** @var Tests\TestCase $this */
        $response = $this->getJson('/api/v1/hotels?q=mountain');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    test('can search hotels by destination', function () {
        Destination::factory()->create(['slug' => 'dhaka']);
        Destination::factory()->create(['slug' => 'rangpur']);
        $hotel = Hotel::factory()->create(['destination_id' => 1]);
        $hotel = Hotel::factory()->create(['destination_id' => 2]);

        /** @var Tests\TestCase $this */
        $response = $this->getJson('/api/v1/hotels?destination=dhaka');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });
});
