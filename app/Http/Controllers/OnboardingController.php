<?php

namespace App\Http\Controllers;

use App\Models\DonorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $allDepartments = collect(config('juniv.departments'))->flatten()->all();
        $isStudent = $user->role === 'student';

        $validated = $request->validate([
            'blood_group' => ['required', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'department' => ['required', Rule::in($allDepartments)],
            'hall' => [$isStudent ? 'required' : 'nullable', Rule::in(config('juniv.halls'))],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_available' => ['boolean'],
            'last_donation_date' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $user->update([
            'department' => $validated['department'],
            'hall' => $validated['hall'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        DonorProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'blood_group' => $validated['blood_group'],
                'is_available' => $request->boolean('is_available', true),
                'last_donation_date' => $validated['last_donation_date'] ?? null,
            ]
        );

        return redirect()->route('dashboard');
    }
}
