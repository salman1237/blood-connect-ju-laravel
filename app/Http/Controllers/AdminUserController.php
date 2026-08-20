<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->withCount(['bloodRequests', 'responses'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $request->string('search')->toString(),
            'selectedRole' => $request->string('role')->toString(),
        ]);
    }

    public function show(User $user): View
    {
        $user->load([
            'donorProfile',
            'badges',
            'bloodRequests' => fn ($q) => $q->latest()->limit(10),
        ]);

        return view('admin.users.show', ['user' => $user]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()->route('admin.users.index')->with('status', 'user-updated');
    }

    /**
     * "Deactivate", not delete — an admin dealing with a problem account
     * shouldn't also wipe their donation history, requests, and badges.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 403, "You can't deactivate your own account.");

        $user->update(['is_active' => false]);

        return redirect()->route('admin.users.index')->with('status', 'user-deactivated');
    }
}
