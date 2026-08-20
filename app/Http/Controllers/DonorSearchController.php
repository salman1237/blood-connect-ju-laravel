<?php

namespace App\Http\Controllers;

use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DonorSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $donors = DonorProfile::query()
            ->eligible()
            ->with('user')
            ->when($request->filled('search'), fn ($q) => $q->whereHas(
                'user',
                fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%')
            ))
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
            'search' => $request->string('search')->toString(),
            'selectedBloodGroup' => $request->string('blood_group')->toString(),
            'selectedHall' => $request->string('hall')->toString(),
        ]);
    }

    /**
     * Public-ish donor profile — visible to any authenticated user (browsing
     * the directory is the whole point), but deliberately doesn't expose
     * raw email/phone. Coordination happens through the app (a request's
     * contact_method, or responding/being selected), not by cold-contacting
     * a donor found by browsing.
     */
    public function show(User $donor): View
    {
        abort_unless($donor->donorProfile, 404);

        $donor->load(['donorProfile', 'badges']);
        $donationHistory = $donor->donationHistory()->with('bloodRequest')->latest('confirmed_at')->get();

        return view('donors.show', [
            'donor' => $donor,
            'donationHistory' => $donationHistory,
        ]);
    }
}
