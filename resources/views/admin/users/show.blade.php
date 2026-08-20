<x-app-layout title="{{ $user->name }}" subtitle="Admin view">
    <div class="mx-auto max-w-2xl space-y-5">
        <div class="surface-panel p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">{{ $user->name }}</h2>
                    <p class="text-sm text-muted-foreground">{{ $user->email }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium capitalize {{ $user->is_active ? 'bg-success/15 text-success' : 'bg-muted text-muted-foreground' }}">
                        {{ $user->role }}{{ $user->is_active ? '' : ' · deactivated' }}
                    </span>
                    <x-button :href="route('admin.users.edit', $user)" variant="outline" size="sm">Edit</x-button>
                </div>
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                <div><dt class="text-xs text-muted-foreground">Hall / Department</dt><dd class="mt-0.5">{{ $user->hall ?? $user->department ?? '—' }}</dd></div>
                <div><dt class="text-xs text-muted-foreground">Phone</dt><dd class="mt-0.5">{{ $user->phone ?? '—' }}</dd></div>
                <div><dt class="text-xs text-muted-foreground">Joined</dt><dd class="mt-0.5">{{ $user->created_at->format('M j, Y') }}</dd></div>
            </dl>
        </div>

        @if ($user->donorProfile)
            <div class="surface-panel p-5 sm:p-6">
                <h3 class="text-sm font-semibold">Donor profile</h3>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <x-blood-drop :group="$user->donorProfile->blood_group" size="sm" />
                    <span class="text-sm text-muted-foreground">Trust score: {{ $user->donorProfile->trust_score }}</span>
                    <span class="text-sm text-muted-foreground">{{ $user->donorProfile->is_available ? 'Available' : 'Unavailable' }}</span>
                </div>
                @if ($user->badges->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($user->badges as $badge)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-accent px-3 py-1 text-xs font-medium text-accent-foreground">
                                <x-icon name="award" class="size-3.5" /> {{ $badge->name }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <div class="surface-panel p-5 sm:p-6">
            <h3 class="text-sm font-semibold">Recent requests ({{ $user->bloodRequests->count() }})</h3>
            @if ($user->bloodRequests->isEmpty())
                <p class="mt-2 text-sm text-muted-foreground">No requests posted.</p>
            @else
                <ul class="mt-3 space-y-2">
                    @foreach ($user->bloodRequests as $request)
                        <li class="flex items-center justify-between gap-3 rounded-xl border border-border p-3 text-sm">
                            <a href="{{ route('requests.show', $request) }}" class="min-w-0 truncate font-medium hover:underline">{{ $request->hospital_name }}</a>
                            <x-status-pill :status="$request->status" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>
