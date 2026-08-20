<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBloodRequestRequest;
use App\Models\BloodRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BloodRequestController extends Controller
{
    /**
     * Browsable archive of every request, any status — the urgent live
     * triage view lives at /dashboard instead.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', BloodRequest::class);

        $requests = BloodRequest::query()
            ->with('requester')
            ->latest()
            ->paginate(15);

        return view('requests.index', ['requests' => $requests]);
    }

    public function create(): View
    {
        $this->authorize('create', BloodRequest::class);

        return view('requests.create', [
            'bloodGroups' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
        ]);
    }

    public function store(StoreBloodRequestRequest $request): RedirectResponse
    {
        $bloodRequest = BloodRequest::create([
            ...$request->validated(),
            'requester_id' => $request->user()->id,
            'expires_at' => now()->addHours(BloodRequest::EXPIRES_AFTER_HOURS),
        ]);

        return redirect()->route('requests.show', $bloodRequest)
            ->with('status', 'request-created');
    }

    public function show(BloodRequest $request): View
    {
        $this->authorize('view', $request);

        $request->load(['requester', 'verifier', 'responses.donor']);

        return view('requests.show', ['bloodRequest' => $request]);
    }

    /**
     * Advance the overall status one step: open -> donor_found -> fulfilled.
     * Separate from confirming which specific donor helped — see
     * RequestResponseController::confirm.
     */
    public function fulfill(BloodRequest $request): RedirectResponse
    {
        $this->authorize('fulfill', $request);

        $request->update([
            'status' => $request->status === 'open' ? 'donor_found' : 'fulfilled',
        ]);

        return redirect()->route('requests.show', $request)
            ->with('status', 'request-updated');
    }
}
