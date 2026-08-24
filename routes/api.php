<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DonorProfileController;
use App\Http\Controllers\Api\V1\MetaController;
use Illuminate\Support\Facades\Route;

// Versioned so the Android app can pin to a contract (v1) while the web
// app's own routes/controllers keep evolving independently underneath it.
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:6,1')
        ->name('register');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/user', [AuthController::class, 'me'])->name('user');

        Route::get('/meta', MetaController::class)->name('meta');
        Route::patch('/donor-profile', [DonorProfileController::class, 'update'])->name('donor-profile.update');
    });
});
