<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The current user's own confirmed-donation history and earned badges —
 * the API twin of web's donations/index.blade.php (DonationHistoryController).
 * Same field shapes DonorDetailResource already uses for a *donor's*
 * donation_history/badges (donors/{id}), reused here rather than
 * reinvented, so the Android client can share one decoder for both.
 */
class DonationsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $donationHistory = $user->donationHistory()->with('bloodRequest')->latest('confirmed_at')->get();
        $badges = $user->badges;

        return response()->json([
            'donation_history' => $donationHistory->map(fn ($entry) => [
                'hospital_name' => $entry->bloodRequest?->hospital_name,
                'confirmed_at' => $entry->confirmed_at->toIso8601String(),
            ]),
            'badges' => $badges->map(fn ($badge) => [
                'name' => $badge->name,
                'slug' => $badge->slug,
                'description' => $badge->description,
                'earned_at' => Carbon::parse($badge->pivot->earned_at)->toIso8601String(),
            ]),
        ]);
    }
}
