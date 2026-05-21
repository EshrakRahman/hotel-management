<?php

use App\Enums\HotelStatus;
use App\Models\Hotel;

test('list all active hotels', function () {
    Hotel::factory()->count(10)->create();
    $response = $this->getJson('/api/v1/hotels');

    $response->assertStatus(200)
    ->assertJsonCount(10, 'data');
});

test('filters out non-active hotels', function () {
    Hotel::factory()->count(1)->create(['status' => HotelStatus::PENDING]);
    Hotel::factory()->count(9)->create();

    $response = $this->getJson('/api/v1/hotels');

    $response->assertStatus(200)
        ->assertJsonCount(9, 'data');
});

test('returns empty array when no active hotels exist', function () {
    Hotel::factory()->count(1)->create(['status' => HotelStatus::PENDING]);

    $response = $this->getJson('/api/v1/hotels');

    $response->assertStatus(200)
        ->assertJsonCount(0, 'data');
});

