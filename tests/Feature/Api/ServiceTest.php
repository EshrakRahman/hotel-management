<?php

use App\Enums\HotelStatus;
use App\Models\Hotel;
use App\Models\Service;
use Tests\TestCase;

test('lists active services for an active hotel', function () {
    /** @var TestCase $this */
    $hotel = Hotel::factory()->create(['status' => HotelStatus::ACTIVE]);

    // Create 3 active services and 1 inactive service
    Service::factory()->count(3)->create([
        'hotel_id' => $hotel->id,
        'is_active' => true,
    ]);
    Service::factory()->create([
        'hotel_id' => $hotel->id,
        'is_active' => false,
    ]);

    $response = $this->getJson("/api/v1/hotels/{$hotel->slug}/services");

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['name', 'description', 'is_per_person', 'base_price'],
            ],
        ]);
});

test('returns 404 for non-existent hotel slug', function () {
    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/hotels/non-existent-slug/services');

    $response->assertStatus(404);
});

test('returns 404 for a hotel that is not active', function () {
    /** @var TestCase $this */
    $hotel = Hotel::factory()->create(['status' => HotelStatus::PENDING]);

    Service::factory()->create([
        'hotel_id' => $hotel->id,
        'is_active' => true,
    ]);

    $response = $this->getJson("/api/v1/hotels/{$hotel->slug}/services");

    $response->assertStatus(404);
});
