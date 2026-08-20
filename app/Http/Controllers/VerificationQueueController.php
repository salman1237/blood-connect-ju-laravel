<?php

namespace App\Http\Controllers;

use App\Models\BloodRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerificationQueueController extends Controller
{
    public function __invoke(Request $request): View
    {
        $requests = BloodRequest::query()
            ->with('requester')
            ->awaitingVerification()
            ->urgencyThenRecency()
            ->paginate(15);

        return view('verify.queue', ['requests' => $requests]);
    }

    public function approve(BloodRequest $request): RedirectResponse
    {
        $this->authorize('verify', $request);

        $request->update([
            'is_verified' => true,
            'verified_by' => auth()->id(),
        ]);

        return redirect()->route('verify.queue')->with('status', 'request-approved');
    }

    public function reject(BloodRequest $request): RedirectResponse
    {
        $this->authorize('verify', $request);

        $request->update([
            'status' => 'expired',
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
        ]);

        return redirect()->route('verify.queue')->with('status', 'request-rejected');
    }
}
