<?php

namespace App\Http\Controllers;

use App\Models\BloodRequest;
use App\Models\DonorProfile;
use App\Models\Report;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $donorsByBloodGroup = DonorProfile::query()
            ->selectRaw('blood_group, COUNT(*) as total')
            ->groupBy('blood_group')
            ->orderBy('blood_group')
            ->pluck('total', 'blood_group');

        // Computed in PHP rather than a raw TIMESTAMPDIFF query — that
        // function isn't portable to sqlite, which the test suite runs on.
        $avgResponseMinutes = BloodRequest::query()
            ->has('firstResponse')
            ->with('firstResponse')
            ->get()
            ->avg(fn (BloodRequest $request) => $request->created_at->diffInMinutes($request->firstResponse->created_at));

        return view('admin.dashboard', [
            'donorsByBloodGroup' => $donorsByBloodGroup,
            'fulfilledCount' => BloodRequest::where('status', 'fulfilled')->count(),
            'expiredCount' => BloodRequest::where('status', 'expired')->count(),
            'avgResponseMinutes' => $avgResponseMinutes,
            'pendingReportsCount' => Report::where('status', 'pending')->count(),
            'totalUsers' => User::count(),
            'activeUsers' => User::where('is_active', true)->count(),
        ]);
    }
}
