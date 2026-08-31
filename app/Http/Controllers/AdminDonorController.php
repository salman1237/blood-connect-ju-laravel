<?php

namespace App\Http\Controllers;

use App\Exports\DonorImportTemplateExport;
use App\Http\Requests\AdminCreateDonorRequest;
use App\Imports\DonorsImport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Separate from AdminUserController (list/edit/deactivate on accounts that
 * already exist) — this one is for registering donors who signed up offline
 * (a recruitment drive, a paper form), either one at a time or in bulk via
 * an Excel upload.
 */
class AdminDonorController extends Controller
{
    public function create(): View
    {
        return view('admin.donors.create', [
            'halls' => config('juniv.halls'),
            'departments' => config('juniv.departments'),
            'bloodGroups' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            'batches' => User::batchOptions(),
        ]);
    }

    /**
     * The generated password is never shown or emailed — the donor sets
     * their own the first time they want to log in, via the existing
     * forgot-password flow.
     */
    public function store(AdminCreateDonorRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::forceCreate([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Str::password(32),
            'role' => $validated['role'],
            'email_verified_at' => now(),
            'is_active' => true,
            'email_notifications_enabled' => true,
        ]);

        $user->updateDonorProfile($validated);

        return redirect()->route('admin.users.show', $user);
    }

    public function import(): View
    {
        return view('admin.donors.import');
    }

    public function processImport(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new DonorsImport;
        Excel::import($import, $request->file('file'));

        return redirect()->route('admin.donors.import')
            ->with('status', 'donors-imported')
            ->with('importedCount', $import->importedCount())
            ->with('failures', $import->failureSummaries());
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(new DonorImportTemplateExport, 'donor-import-template.xlsx');
    }
}
