<?php

use App\Http\Controllers\Api\AmenityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DestinationController;
use App\Http\Controllers\Api\HotelController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'updatePassword']);
    });
});

Route::prefix('v1')->group(function () {
    Route::get('/destinations', [DestinationController::class, 'index']);
    Route::get('/hotels', [HotelController::class, 'index']);
    Route::get('/hotels/featured', [HotelController::class, 'featured']);
    Route::get('/hotels/{hotel}', [HotelController::class, 'show']);
    Route::get('/amenities', [AmenityController::class, 'index']);
    Route::get('/room-types/{roomType}/rooms', [RoomController::class, 'index']);
    Route::get('/rooms/{room}', [RoomController::class, 'show']);
    Route::post('/promotions/verify', [PromotionController::class, 'verify']);
    Route::post('/bookings/quote', [BookingController::class, 'quote']);
    Route::get('/hotels/{hotel}/reviews', [ReviewController::class, 'index']);

    Route::post('/payments/webhook/stripe', [StripeWebhookController::class, 'handle']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::get('/bookings/{booking_ref}', [BookingController::class, 'show']);
        Route::post('/bookings/{booking_ref}/cancel', [BookingController::class, 'cancel']);
        Route::post('/bookings/{booking_ref}/checkout-session', [PaymentController::class, 'checkoutSession']);
        Route::post('/bookings/{booking_ref}/reviews', [ReviewController::class, 'store']);
    });
});
