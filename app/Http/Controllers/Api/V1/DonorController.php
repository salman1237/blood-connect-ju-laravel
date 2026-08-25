<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\DonorDetailResource;
use App\Http\Resources\Api\DonorSummaryResource;
use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Mirrors web's DonorSearchController — same query, same eligible-only
 * scope, same name/blood_group/hall filters. Not paginated like the web
 * page (matches how requests.index mirrors the web dashboard's live feed
 * rather than the full paginated archive) — capped at 50 results instead,
 * newest-available-first, since a mobile search screen is realistically
 * refining the filters rather than paging through hundreds of donors.
 */
class DonorController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
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
            ->limit(50)
            ->get();

        return DonorSummaryResource::collection($donors);
    }

    public function show(User $donor): DonorDetailResource
    {
        abort_unless($donor->donorProfile, 404);

        $donor->load(['donorProfile', 'badges']);
        $donationHistory = $donor->donationHistory()->with('bloodRequest')->latest('confirmed_at')->get();

        return new DonorDetailResource($donor, $donationHistory);
    }
}
