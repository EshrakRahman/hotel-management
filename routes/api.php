<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DestinationController;
use App\Http\Controllers\Api\HotelController;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::prefix('v1')->group(function () {
    Route::get('/destinations', [DestinationController::class, 'index']);
    Route::get('/hotels', [HotelController::class, 'index']);
    Route::get('/hotels/{hotel}', [HotelController::class, 'show']);
});
