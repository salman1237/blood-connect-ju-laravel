<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DonorProfileController;
use App\Http\Controllers\Api\V1\MatchingDonorsController;
use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\RequestController;
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

    Route::middleware(['auth:sanctum', 'active.api'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/user', [AuthController::class, 'me'])->name('user');

        Route::get('/meta', MetaController::class)->name('meta');
        Route::patch('/donor-profile', [DonorProfileController::class, 'update'])->name('donor-profile.update');

        // Gated on a completed donor profile, same as the web app's
        // dashboard/requests routes — NOT applied above, since
        // donor-profile.update is how onboarding gets completed in the
        // first place and gating it would be a lockout.
        Route::middleware('onboarded.api')->group(function () {
            Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
            Route::get('/requests/stats', [RequestController::class, 'stats'])->name('requests.stats');
            Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
            Route::get('/requests/{bloodRequest}', [RequestController::class, 'show'])->name('requests.show');
            Route::get('/requests/{bloodRequest}/donors', MatchingDonorsController::class)->name('requests.donors');
        });
    });
});
