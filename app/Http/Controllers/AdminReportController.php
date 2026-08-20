<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function index(): View
    {
        $reports = Report::query()
            ->with(['bloodRequest.requester', 'reporter'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        return view('admin.reports.index', [
            'reports' => $reports,
            'reportReasons' => Report::REASONS,
        ]);
    }

    /**
     * Acknowledges the report itself. If the underlying request also needs
     * action, that's the existing verifier approve/reject flow (admins
     * already have access to it) — not duplicated here.
     */
    public function review(Report $report): RedirectResponse
    {
        $report->update([
            'status' => 'reviewed',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.reports.index')->with('status', 'report-reviewed');
    }

    public function dismiss(Report $report): RedirectResponse
    {
        $report->update([
            'status' => 'dismissed',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.reports.index')->with('status', 'report-dismissed');
    }
}
