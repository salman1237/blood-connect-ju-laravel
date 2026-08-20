<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\BloodRequest;
use Illuminate\Http\RedirectResponse;

class ReportController extends Controller
{
    public function store(StoreReportRequest $storeReportRequest, BloodRequest $request): RedirectResponse
    {
        $request->reports()->create([
            'reporter_id' => $storeReportRequest->user()->id,
            'reason' => $storeReportRequest->validated('reason'),
            'details' => $storeReportRequest->validated('details'),
        ]);

        return redirect()->route('requests.show', $request)->with('status', 'request-reported');
    }
}
