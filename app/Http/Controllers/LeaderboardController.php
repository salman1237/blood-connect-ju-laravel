<?php

namespace App\Http\Controllers;

use App\Models\DonationHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function __invoke(): View
    {
        // Aggregate query over confirmed donations only, briefly cached —
        // it doesn't need to be real-time and every profile view of every
        // donor would otherwise recompute it.
        $rankings = Cache::remember('leaderboard.hall-department', now()->addMinutes(5), function () {
            return DonationHistory::query()
                ->join('users', 'users.id', '=', 'donation_history.donor_id')
                ->selectRaw('COALESCE(users.hall, users.department) as group_name, COUNT(*) as donations')
                ->whereRaw('COALESCE(users.hall, users.department) IS NOT NULL')
                ->groupBy('group_name')
                ->orderByDesc('donations')
                ->get();
        });

        return view('leaderboard', ['rankings' => $rankings]);
    }
}
