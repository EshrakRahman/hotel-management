<?php

use App\Enums\PromotionsDiscountType;
use App\Models\Promotion;
use Tests\TestCase;

beforeEach(function () {
    // Clean up or seed if necessary
});

test('returns valid=true and correct calculations for active percentage promotion', function () {
    /** @var TestCase $this */
    $promo = Promotion::factory()->create([
        'promo_code' => 'PERCENT10',
        'discount_type' => PromotionsDiscountType::PERCENTAGE,
        'discount_value' => 10.00, // 10%
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/promotions/verify', [
        'promo_code' => 'PERCENT10',
        'room_subtotal' => 250.00,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'valid' => true,
            'promotion' => [
                'id' => $promo->id,
                'name' => $promo->name,
                'promo_code' => 'PERCENT10',
                'discount_type' => 'percentage',
                'discount_value' => '10.00',
                'discount_amount' => '25.00',
            ],
        ]);
});

test('returns valid=true and correct calculations for active flat promotion', function () {
    /** @var TestCase $this */
    $promo = Promotion::factory()->create([
        'promo_code' => 'FLAT50',
        'discount_type' => PromotionsDiscountType::FLAT,
        'discount_value' => 50.00,
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/promotions/verify', [
        'promo_code' => 'FLAT50',
        'room_subtotal' => 250.00,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'valid' => true,
            'promotion' => [
                'id' => $promo->id,
                'name' => $promo->name,
                'promo_code' => 'FLAT50',
                'discount_type' => 'flat',
                'discount_value' => '50.00',
                'discount_amount' => '50.00',
            ],
        ]);
});

test('limits flat promotion discount to room subtotal', function () {
    /** @var TestCase $this */
    $promo = Promotion::factory()->create([
        'promo_code' => 'FLAT100',
        'discount_type' => PromotionsDiscountType::FLAT,
        'discount_value' => 100.00,
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/promotions/verify', [
        'promo_code' => 'FLAT100',
        'room_subtotal' => 45.00, // room subtotal is less than flat discount
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'valid' => true,
            'promotion' => [
                'id' => $promo->id,
                'discount_amount' => '45.00',
            ],
        ]);
});

test('returns valid=false for expired promotion', function () {
    /** @var TestCase $this */
    Promotion::factory()->create([
        'promo_code' => 'EXPIRED',
        'discount_type' => PromotionsDiscountType::PERCENTAGE,
        'discount_value' => 10.00,
        'start_date' => now()->subDays(5),
        'end_date' => now()->subDays(2),
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/promotions/verify', [
        'promo_code' => 'EXPIRED',
        'room_subtotal' => 200.00,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'valid' => false,
            'message' => 'The promotion code is invalid or has expired.',
        ]);
});

test('returns valid=false for inactive promotion', function () {
    /** @var TestCase $this */
    Promotion::factory()->create([
        'promo_code' => 'INACTIVE',
        'discount_type' => PromotionsDiscountType::PERCENTAGE,
        'discount_value' => 10.00,
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
        'is_active' => false,
    ]);

    $response = $this->postJson('/api/v1/promotions/verify', [
        'promo_code' => 'INACTIVE',
        'room_subtotal' => 200.00,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'valid' => false,
            'message' => 'The promotion code is invalid or has expired.',
        ]);
});

test('validates required inputs for promotion verification', function () {
    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/promotions/verify', [
        'promo_code' => '',
        'room_subtotal' => 'invalid_number',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['promo_code', 'room_subtotal']);
});

test('lists active promotions and filters out inactive, expired, or pending ones', function () {
    /** @var TestCase $this */

    // Active promotion 1
    $promo1 = Promotion::factory()->create([
        'name' => 'Active Promotion 1',
        'promo_code' => 'ACTIVE1',
        'is_active' => true,
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
    ]);

    // Active promotion 2
    $promo2 = Promotion::factory()->create([
        'name' => 'Active Promotion 2',
        'promo_code' => 'ACTIVE2',
        'is_active' => true,
        'start_date' => now()->subDays(5),
        'end_date' => now()->addDays(5),
    ]);

    // Inactive promotion
    Promotion::factory()->create([
        'name' => 'Inactive Promotion',
        'promo_code' => 'INACTIVE_LIST',
        'is_active' => false,
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
    ]);

    // Expired promotion
    Promotion::factory()->create([
        'name' => 'Expired Promotion',
        'promo_code' => 'EXPIRED_LIST',
        'is_active' => true,
        'start_date' => now()->subDays(10),
        'end_date' => now()->subDays(5),
    ]);

    // Pending/Not-started promotion
    Promotion::factory()->create([
        'name' => 'Pending Promotion',
        'promo_code' => 'PENDING_LIST',
        'is_active' => true,
        'start_date' => now()->addDays(5),
        'end_date' => now()->addDays(10),
    ]);

    $response = $this->getJson('/api/v1/promotions');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'promo_code', 'discount_type', 'discount_value', 'start_date', 'end_date'],
            ],
        ]);

    $promoIds = collect($response['data'])->pluck('id')->all();
    expect($promoIds)->toContain($promo1->id)
        ->toContain($promo2->id)
        ->not->toContain('INACTIVE_LIST')
        ->not->toContain('EXPIRED_LIST')
        ->not->toContain('PENDING_LIST');
});
