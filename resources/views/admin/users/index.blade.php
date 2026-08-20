<x-app-layout title="Manage users" subtitle="Search, change roles, deactivate accounts">
    @if (session('status') === 'user-updated')
        <x-auth-session-status status="User updated." class="mb-5" />
    @elseif (session('status') === 'user-deactivated')
        <x-auth-session-status status="User deactivated." class="mb-5" />
    @endif

    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <x-text-input type="search" name="search" placeholder="Search by name or email" :value="$search" class="sm:max-w-xs" />
        <div class="flex gap-1.5 overflow-x-auto pb-1">
            <a href="{{ route('admin.users.index', ['search' => $search]) }}"
               class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium transition-colors {{ $selectedRole === '' ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card text-muted-foreground' }}">
                All roles
            </a>
            @foreach (['student', 'staff', 'faculty', 'verifier', 'admin'] as $role)
                <a href="{{ route('admin.users.index', ['search' => $search, 'role' => $role]) }}"
                   class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium capitalize transition-colors {{ $selectedRole === $role ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card text-muted-foreground' }}">
                    {{ $role }}
                </a>
            @endforeach
        </div>
        <x-button type="submit" size="sm" variant="outline">Search</x-button>
    </form>

    @if ($users->isEmpty())
        <div class="surface-panel flex flex-col items-center px-6 py-14 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-surface text-muted-foreground">
                <x-icon name="search" class="size-6" />
            </span>
            <h3 class="mt-4 text-base font-semibold">No users match</h3>
            <p class="mt-1.5 max-w-sm text-sm text-muted-foreground">Try a different search or role filter.</p>
        </div>
    @else
        {{-- Table on md+, stacked cards below — every admin screen must stay usable on a phone. --}}
        <div class="hidden overflow-x-auto rounded-xl border border-border md:block">
            <table class="w-full text-sm">
                <thead class="bg-secondary/50 text-left text-xs text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Name</th>
                        <th class="px-4 py-2.5 font-medium">Role</th>
                        <th class="px-4 py-2.5 font-medium">Status</th>
                        <th class="px-4 py-2.5 font-medium">Requests</th>
                        <th class="px-4 py-2.5 font-medium">Responses</th>
                        <th class="px-4 py-2.5 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($users as $user)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ $user->name }}</p>
                                <p class="text-xs text-muted-foreground">{{ $user->email }}</p>
                            </td>
                            <td class="px-4 py-3 capitalize">{{ $user->role }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $user->is_active ? 'bg-success/15 text-success' : 'bg-muted text-muted-foreground' }}">
                                    {{ $user->is_active ? 'Active' : 'Deactivated' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $user->blood_requests_count }}</td>
                            <td class="px-4 py-3">{{ $user->responses_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3 text-xs">
                                    <a href="{{ route('admin.users.show', $user) }}" class="font-medium hover:underline">View</a>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="font-medium hover:underline">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <ul class="space-y-2 md:hidden">
            @foreach ($users as $user)
                <li class="surface-panel p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                            <p class="truncate text-xs text-muted-foreground">{{ $user->email }}</p>
                        </div>
                        <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $user->is_active ? 'bg-success/15 text-success' : 'bg-muted text-muted-foreground' }}">
                            {{ $user->is_active ? 'Active' : 'Deactivated' }}
                        </span>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">
                        <span class="capitalize">{{ $user->role }}</span> · {{ $user->blood_requests_count }} requests · {{ $user->responses_count }} responses
                    </p>
                    <div class="mt-3 flex gap-3 text-xs">
                        <a href="{{ route('admin.users.show', $user) }}" class="font-medium hover:underline">View</a>
                        <a href="{{ route('admin.users.edit', $user) }}" class="font-medium hover:underline">Edit</a>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">
            {{ $users->links() }}
        </div>
    @endif
</x-app-layout>
