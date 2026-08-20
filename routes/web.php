<?php

use App\Http\Controllers\BloodRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonorSearchController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestResponseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('landing');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');

    // Account settings stay reachable even with an incomplete profile —
    // only the donor-facing app (dashboard, requests, ...) is gated below.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/donor', [ProfileController::class, 'updateDonorProfile'])->name('profile.donor.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'onboarded'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('requests', BloodRequestController::class)->except(['edit', 'update', 'destroy']);
    Route::post('/requests/{request}/fulfill', [BloodRequestController::class, 'fulfill'])->name('requests.fulfill');
    Route::post('/requests/{request}/respond', [RequestResponseController::class, 'store'])->name('requests.respond');
    Route::patch('/requests/{request}/responses/{response}/confirm', [RequestResponseController::class, 'confirm'])->name('requests.responses.confirm');

    Route::get('/donors', DonorSearchController::class)->name('donors.index');
});

require __DIR__.'/auth.php';
