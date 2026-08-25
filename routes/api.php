<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DonationsController;
use App\Http\Controllers\Api\V1\DonorController;
use App\Http\Controllers\Api\V1\DonorProfileController;
use App\Http\Controllers\Api\V1\LeaderboardController;
use App\Http\Controllers\Api\V1\MatchingDonorsController;
use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\PushTokenController;
use App\Http\Controllers\Api\V1\RequestController;
use App\Http\Controllers\Api\V1\RequestResponseController;
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

        // Account-level fields — reachable pre-onboarding too, same as the
        // web app's /profile route (not gated by 'onboarded').
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
        Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');

        // Not gated behind onboarded.api — a device should be able to
        // register its push token as soon as it's logged in, well before
        // onboarding is complete.
        Route::post('/push-tokens', [PushTokenController::class, 'store'])->name('push-tokens.store');
        Route::delete('/push-tokens', [PushTokenController::class, 'destroy'])->name('push-tokens.destroy');

        // Gated on a completed donor profile, same as the web app's
        // dashboard/requests routes — NOT applied above, since
        // donor-profile.update is how onboarding gets completed in the
        // first place and gating it would be a lockout.
        Route::middleware('onboarded.api')->group(function () {
            Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
            Route::get('/requests/stats', [RequestController::class, 'stats'])->name('requests.stats');
            // Must be registered before the {bloodRequest} wildcard below —
            // otherwise "mine" is swallowed by implicit route-model binding
            // (tried as a BloodRequest id, 404s instead of matching this).
            Route::get('/requests/mine', [RequestController::class, 'mine'])->name('requests.mine');
            Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
            Route::get('/requests/{bloodRequest}', [RequestController::class, 'show'])->name('requests.show');
            Route::get('/requests/{bloodRequest}/donors', MatchingDonorsController::class)->name('requests.donors');
            Route::post('/requests/{bloodRequest}/fulfill', [RequestController::class, 'fulfill'])->name('requests.fulfill');
            Route::post('/requests/{bloodRequest}/respond', [RequestResponseController::class, 'store'])->name('requests.respond');
            Route::patch('/requests/{bloodRequest}/responses/{response}/confirm', [RequestResponseController::class, 'confirm'])->name('requests.responses.confirm');
            Route::patch('/requests/{bloodRequest}/responses/{response}/confirm-donation', [RequestResponseController::class, 'confirmDonation'])->name('requests.responses.confirm-donation');

            Route::get('/donors', [DonorController::class, 'index'])->name('donors.index');
            Route::get('/donors/{donor}', [DonorController::class, 'show'])->name('donors.show');

            Route::get('/leaderboard', LeaderboardController::class)->name('leaderboard');

            Route::get('/donations', DonationsController::class)->name('donations.index');
        });
    });
});
