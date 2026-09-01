<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\BloodRequest;
use App\Models\DonorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        // Nothing for a logged-in user to do on the marketing page — send
        // them straight to the app (the 'onboarded' middleware takes over
        // from there if their profile isn't complete yet).
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        $liveRequests = BloodRequest::query()
            ->open()
            ->urgencyThenRecency()
            ->limit(3)
            ->get();

        $avgResponseMinutes = BloodRequest::averageResponseMinutes();

        return view('landing', [
            'liveRequests' => $liveRequests,
            'donorCount' => DonorProfile::count(),
            'fulfilledCount' => BloodRequest::where('status', 'fulfilled')->count(),
            'avgResponseMinutes' => $avgResponseMinutes,
            'hallsAndDepartmentsCount' => count(config('juniv.halls')) + collect(config('juniv.departments'))->flatten()->count(),
            'orgSetting' => AppSetting::current(),
        ]);
    }
}
