<?php

namespace App\Http\Controllers;

use App\Models\BloodRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $requests = BloodRequest::query()
            ->open()
            ->with('requester')
            ->when($request->filled('blood_group'), fn ($q) => $q->where('blood_group', $request->string('blood_group')))
            ->when($request->filled('hall'), fn ($q) => $q->whereHas(
                'requester',
                fn ($q) => $q->where('hall', $request->string('hall'))
            ))
            ->urgencyThenRecency()
            ->get();

        return view('dashboard', [
            'requests' => $requests,
            'bloodGroups' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            'halls' => config('juniv.halls'),
            'selectedBloodGroup' => $request->string('blood_group')->toString(),
            'selectedHall' => $request->string('hall')->toString(),
            'stats' => [
                'active' => BloodRequest::open()->count(),
                'critical' => BloodRequest::open()->where('urgency', 'critical')->count(),
                'fulfilledToday' => BloodRequest::where('status', 'fulfilled')->whereDate('updated_at', today())->count(),
                'registeredDonors' => User::has('donorProfile')->count(),
            ],
        ]);
    }
}
