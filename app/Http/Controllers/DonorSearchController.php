<?php

namespace App\Http\Controllers;

use App\Models\DonorProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DonorSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $donors = DonorProfile::query()
            ->eligible()
            ->with('user')
            ->when($request->filled('blood_group'), fn ($q) => $q->where('donor_profiles.blood_group', $request->string('blood_group')))
            ->when($request->filled('hall'), fn ($q) => $q->whereHas(
                'user',
                fn ($q) => $q->where('hall', $request->string('hall'))
            ))
            ->orderByDesc('is_available')
            ->paginate(20)
            ->withQueryString();

        return view('donors.index', [
            'donors' => $donors,
            'bloodGroups' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            'halls' => config('juniv.halls'),
            'selectedBloodGroup' => $request->string('blood_group')->toString(),
            'selectedHall' => $request->string('hall')->toString(),
        ]);
    }
}
