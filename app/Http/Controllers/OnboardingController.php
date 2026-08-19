<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDonorProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(Request $request): View
    {
        return view('onboarding', [
            'halls' => config('juniv.halls'),
            'departments' => config('juniv.departments'),
            'bloodGroups' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
        ]);
    }

    public function store(UpdateDonorProfileRequest $request): RedirectResponse
    {
        $request->user()->updateDonorProfile($request->validated());

        return redirect()->route('dashboard');
    }
}
