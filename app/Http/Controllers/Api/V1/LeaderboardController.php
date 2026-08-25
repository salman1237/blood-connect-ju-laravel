<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DonationHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/** Same cached aggregate query as web's LeaderboardController — one cache key shared by both. */
class LeaderboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $rankings = Cache::remember('leaderboard.hall-department', now()->addMinutes(5), function () {
            return DonationHistory::query()
                ->join('users', 'users.id', '=', 'donation_history.donor_id')
                ->selectRaw('COALESCE(users.hall, users.department) as group_name, COUNT(*) as donations')
                ->whereRaw('COALESCE(users.hall, users.department) IS NOT NULL')
                ->groupBy('group_name')
                ->orderByDesc('donations')
                ->get();
        });

        // COUNT(*) comes back from selectRaw() as whatever type the DB
        // driver's PDO fetch mode happens to produce (a numeric string on
        // some MySQL configs) — cast explicitly so the JSON response is
        // always a real number, not driver-dependent.
        return response()->json($rankings->values()->map(fn ($row) => [
            'group_name' => $row->group_name,
            'donations' => (int) $row->donations,
        ]));
    }
}
