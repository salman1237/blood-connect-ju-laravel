<?php

namespace App\Http\Controllers;

use App\Models\BloodRequest;
use App\Models\RequestResponse;
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

        return redirect()->route('requests.show', $request)->with('status', 'donor-confirmed');
    }
}
