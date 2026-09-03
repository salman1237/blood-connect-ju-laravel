<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminDonorController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\BloodRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonationHistoryController;
use App\Http\Controllers\DonorSearchController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MyRequestsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RequestResponseController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\VerificationQueueController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('landing');
Route::view('/privacy', 'privacy')->name('privacy');
Route::view('/account-deletion', 'account-deletion')->name('account-deletion');

// Guest-reachable — the toggle lives in both the guest and app layouts.
Route::post('/locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');

    // Account settings stay reachable even with an incomplete profile —
    // only the donor-facing app (dashboard, requests, ...) is gated below.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/donor', [ProfileController::class, 'updateDonorProfile'])->name('profile.donor.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Their own history/requests, split out of the profile page — reachable
    // pre-onboarding too, same as profile/settings above.
    Route::get('/donations', DonationHistoryController::class)->name('donations.index');
    Route::get('/requests/mine', MyRequestsController::class)->name('requests.mine');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
});

Route::middleware(['auth', 'verified', 'onboarded'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('requests', BloodRequestController::class)->except(['edit', 'update', 'destroy']);
    Route::get('/requests/{request}/donors', [BloodRequestController::class, 'matchingDonors'])->name('requests.donors');
    Route::post('/requests/{request}/fulfill', [BloodRequestController::class, 'fulfill'])->name('requests.fulfill');
    Route::post('/requests/{request}/respond', [RequestResponseController::class, 'store'])->name('requests.respond');
    Route::post('/requests/{request}/report', [ReportController::class, 'store'])->name('requests.report');
    Route::patch('/requests/{request}/responses/{response}/confirm', [RequestResponseController::class, 'confirm'])->name('requests.responses.confirm');
    Route::patch('/requests/{request}/responses/{response}/confirm-donation', [RequestResponseController::class, 'confirmDonation'])->name('requests.responses.confirm-donation');

    Route::get('/donors', DonorSearchController::class)->name('donors.index');
    Route::get('/donors/{donor}', [DonorSearchController::class, 'show'])->name('donors.show');
    Route::get('/leaderboard', LeaderboardController::class)->name('leaderboard');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
});

// Verifier — CR / hall provost office / medical center staff. Not gated by
// 'onboarded': verifiers are assigned their role by an admin, not
// self-selected, and don't need a donor profile to review requests.
Route::middleware(['auth', 'verified', 'role:verifier,admin'])->prefix('verify')->group(function () {
    Route::get('/queue', VerificationQueueController::class)->name('verify.queue');
    Route::post('/requests/{request}/approve', [VerificationQueueController::class, 'approve'])->name('verify.approve');
    Route::post('/requests/{request}/reject', [VerificationQueueController::class, 'reject'])->name('verify.reject');
});

// Admin — not gated by 'onboarded' for the same reason as the verifier
// group above: admins are assigned the role directly, not self-selected.
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
    Route::resource('users', AdminUserController::class)->except(['create', 'store']);
    Route::get('/donors/create', [AdminDonorController::class, 'create'])->name('donors.create');
    Route::post('/donors', [AdminDonorController::class, 'store'])->name('donors.store');
    Route::get('/donors/import', [AdminDonorController::class, 'import'])->name('donors.import');
    Route::post('/donors/import', [AdminDonorController::class, 'processImport'])->name('donors.import.store');
    Route::get('/donors/import/template', [AdminDonorController::class, 'downloadTemplate'])->name('donors.import.template');
    Route::get('/settings', [AdminSettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings/{which}', [AdminSettingsController::class, 'update'])->whereIn('which', ['funded_by', 'maintained_by'])->name('settings.update');
    Route::post('/settings/logo/{which}', [AdminSettingsController::class, 'updateLogo'])->whereIn('which', ['funded_by', 'maintained_by'])->name('settings.logo.update');
    Route::delete('/settings/logo/{which}', [AdminSettingsController::class, 'destroyLogo'])->whereIn('which', ['funded_by', 'maintained_by'])->name('settings.logo.destroy');
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/{report}/review', [AdminReportController::class, 'review'])->name('reports.review');
    Route::post('/reports/{report}/dismiss', [AdminReportController::class, 'dismiss'])->name('reports.dismiss');
});

require __DIR__.'/auth.php';
