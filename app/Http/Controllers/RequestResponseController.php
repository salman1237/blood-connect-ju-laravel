<?php

namespace App\Http\Controllers;

use App\Models\BloodRequest;
use App\Models\RequestResponse;
use App\Notifications\DonorSelected;
use App\Notifications\RequestResponded;
use Illuminate\Http\RedirectResponse;

class RequestResponseController extends Controller
{
    public function store(BloodRequest $request): RedirectResponse
    {
        $this->authorize('respond', $request);

        $response = $request->responses()->create([
            'donor_id' => auth()->id(),
            'status' => 'responded',
        ]);

        $request->requester->notify(new RequestResponded($response));

        return redirect()->route('requests.show', $request)->with('status', 'response-recorded');
    }

    public function confirm(BloodRequest $request, RequestResponse $response): RedirectResponse
    {
        $this->authorize('confirm', $response);

        $response->update(['status' => 'confirmed']);

        $response->donor->notify(new DonorSelected($response));

        return redirect()->route('requests.show', $request)->with('status', 'donor-confirmed');
    }

    /**
     * Either side of a fulfilled request confirming the donation actually
     * happened. Writing donation_history and bumping trust_score is left to
     * RequestResponseObserver — it fires once both sides are in, and this
     * controller doesn't need to know which confirmation was the second one.
     */
    public function confirmDonation(BloodRequest $request, RequestResponse $response): RedirectResponse
    {
        $this->authorize('confirmDonation', $response);

        $field = auth()->id() === $request->requester_id ? 'requester_confirmed_at' : 'donor_confirmed_at';
        $response->update([$field => now()]);

        return redirect()->route('requests.show', $request)->with('status', 'donation-confirmed');
    }
}
