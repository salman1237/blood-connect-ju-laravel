<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBloodRequestRequest;
use App\Http\Resources\Api\BloodRequestResource;
use App\Models\BloodRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RequestController extends Controller
{
    /**
     * The live triage feed — same query as the web dashboard
     * (DashboardController): open requests, urgency-then-recency,
     * filterable by blood group / hall. The browsable full-status archive
     * (web's /requests) isn't ported here yet — this is the screen that
     * matters for a donor deciding whether to respond right now.
     */
    public function index(Request $request): AnonymousResourceCollection
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

        return BloodRequestResource::collection($requests);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'active' => BloodRequest::open()->count(),
            'critical' => BloodRequest::open()->where('urgency', 'critical')->count(),
            'fulfilled_today' => BloodRequest::where('status', 'fulfilled')->whereDate('updated_at', today())->count(),
            'registered_donors' => User::has('donorProfile')->count(),
        ]);
    }

    public function store(StoreBloodRequestRequest $request): BloodRequestResource
    {
        // status/is_verified are DB-level defaults (migration), not part of
        // $request->validated() — Eloquent doesn't pull column defaults
        // back into the in-memory model after create(), so without setting
        // them explicitly here this response would serialize them as null
        // even though the row itself is correct (a subsequent GET would
        // show the right value; only this immediate response was stale).
        $bloodRequest = BloodRequest::create([
            ...$request->validated(),
            'requester_id' => $request->user()->id,
            'status' => 'open',
            'is_verified' => false,
            'expires_at' => now()->addHours(BloodRequest::EXPIRES_AFTER_HOURS),
        ]);

        return new BloodRequestResource($bloodRequest->load(['requester', 'responses.donor']));
    }

    public function show(BloodRequest $bloodRequest): BloodRequestResource
    {
        $this->authorize('view', $bloodRequest);

        $bloodRequest->load(['requester', 'responses.donor']);

        return new BloodRequestResource($bloodRequest);
    }

    /**
     * Advance the overall status one step: open -> donor_found -> fulfilled.
     * Same as web's BloodRequestController::fulfill() — separate from
     * confirming *which* donor helped (RequestResponseController::confirm).
     */
    public function fulfill(BloodRequest $bloodRequest): BloodRequestResource
    {
        $this->authorize('fulfill', $bloodRequest);

        $bloodRequest->update([
            'status' => $bloodRequest->status === 'open' ? 'donor_found' : 'fulfilled',
        ]);

        return new BloodRequestResource($bloodRequest->fresh()->load(['requester', 'responses.donor']));
    }
}
