<?php

use App\Enums\HotelStatus;
use App\Models\Hotel;

describe('feature hotels', function () {
    test('returns featured hotels', function () {
        $hotels = Hotel::factory()->count(3)->create(['status' => HotelStatus::ACTIVE]);
        /** @var Tests\TestCase $this */
        $response = $this->getJson('api/v1/hotels/featured');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    test('returns at most 5 featured hotels', function () {
        $hotels = Hotel::factory()->count(8)->create();

        /** @var Tests\TestCase $this */
        $response = $this->getJson('api/v1/hotels/featured');

        $response->assertStatus(200);
        expect(count($response['data']))->toBeLessThanOrEqual(5);
    });

    test('return correct hotel structure', function () {
        $hotel = Hotel::factory()->create();
        /** @var Tests\TestCase $this */
        $response = $this->getJson('api/v1/hotels/featured');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'address',
                    'status',
                    'destination',
                    'cancellation_policy'
                ]
            ]
        ]);
    });
});
