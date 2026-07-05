<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

test('confirmed bookings with past checkout dates are completed', function () {
    /** @var TestCase $this */

    // Booking 1: Confirmed booking checking out in the past (yesterday)
    $bookingPast = Booking::factory()->create(['status' => BookingStatus::CONFIRMED]);
    BookingItem::factory()->create([
        'booking_id' => $bookingPast->id,
        'check_in' => now()->subDays(5)->format('Y-m-d'),
        'check_out' => now()->subDay()->format('Y-m-d'),
    ]);

    // Booking 2: Confirmed booking checking out today (should not complete yet)
    $bookingToday = Booking::factory()->create(['status' => BookingStatus::CONFIRMED]);
    BookingItem::factory()->create([
        'booking_id' => $bookingToday->id,
        'check_in' => now()->subDays(2)->format('Y-m-d'),
        'check_out' => now()->format('Y-m-d'),
    ]);

    // Booking 3: Confirmed booking checking out in the future (tomorrow)
    $bookingFuture = Booking::factory()->create(['status' => BookingStatus::CONFIRMED]);
    BookingItem::factory()->create([
        'booking_id' => $bookingFuture->id,
        'check_in' => now()->format('Y-m-d'),
        'check_out' => now()->addDay()->format('Y-m-d'),
    ]);

    // Booking 4: Pending booking checking out in the past (should not transition to completed)
    $bookingPending = Booking::factory()->create(['status' => BookingStatus::PENDING]);
    BookingItem::factory()->create([
        'booking_id' => $bookingPending->id,
        'check_in' => now()->subDays(5)->format('Y-m-d'),
        'check_out' => now()->subDay()->format('Y-m-d'),
    ]);

    // Execute the command
    $exitCode = Artisan::call('bookings:complete-past-stays');

    expect($exitCode)->toBe(0);

    // Assert Booking 1 is completed
    $this->assertDatabaseHas('bookings', [
        'id' => $bookingPast->id,
        'status' => BookingStatus::COMPLETED->value,
    ]);

    // Assert Booking 2 is still confirmed
    $this->assertDatabaseHas('bookings', [
        'id' => $bookingToday->id,
        'status' => BookingStatus::CONFIRMED->value,
    ]);

    // Assert Booking 3 is still confirmed
    $this->assertDatabaseHas('bookings', [
        'id' => $bookingFuture->id,
        'status' => BookingStatus::CONFIRMED->value,
    ]);

    // Assert Booking 4 is still pending
    $this->assertDatabaseHas('bookings', [
        'id' => $bookingPending->id,
        'status' => BookingStatus::PENDING->value,
    ]);
});
