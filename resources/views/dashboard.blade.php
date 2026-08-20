<x-app-layout title="Live requests" subtitle="Updated a moment ago">
    @if (session('status') === 'request-created')
        <x-auth-session-status status="Request posted. It'll expire automatically in 72 hours if not resolved." class="mb-5" />
    @elseif (session('status') === 'request-updated')
        <x-auth-session-status status="Request status updated." class="mb-5" />
    @endif

    <div class="mb-5 grid gap-3 sm:grid-cols-3">
        <div class="surface-panel p-4">
            <p class="text-xs text-muted-foreground">Active now</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $stats['active'] }}</p>
            <p class="text-xs text-muted-foreground">{{ $stats['critical'] }} critical</p>
        </div>
        <div class="surface-panel p-4">
            <p class="text-xs text-muted-foreground">Fulfilled today</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $stats['fulfilledToday'] }}</p>
            <p class="text-xs text-muted-foreground">Confirmed donations</p>
        </div>
        <div class="surface-panel p-4">
            <p class="text-xs text-muted-foreground">Registered donors</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $stats['registeredDonors'] }}</p>
            <p class="text-xs text-muted-foreground">Across campus</p>
        </div>
    </div>

    <x-button :href="route('requests.create')" size="lg" class="mb-5 w-full sm:w-auto">
        <x-icon name="plus" class="size-4" /> Post emergency request
    </x-button>

    <div class="mb-4 space-y-3">
        <div class="flex gap-1.5 overflow-x-auto pb-1">
            <a href="{{ route('dashboard', ['hall' => $selectedHall]) }}"
               class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium transition-colors {{ $selectedBloodGroup === '' ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card text-muted-foreground' }}">
                All
            </a>
            @foreach ($bloodGroups as $group)
                <a href="{{ route('dashboard', ['blood_group' => $group, 'hall' => $selectedHall]) }}"
                   class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium transition-colors {{ $selectedBloodGroup === $group ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card text-muted-foreground' }}">
                    {{ $group }}
                </a>
            @endforeach
        </div>
        <div class="flex gap-1.5 overflow-x-auto pb-1">
            <a href="{{ route('dashboard', ['blood_group' => $selectedBloodGroup]) }}"
               class="shrink-0 rounded-full border px-3 py-1.5 text-xs transition-colors {{ $selectedHall === '' ? 'border-foreground bg-secondary font-medium' : 'border-border bg-card text-muted-foreground' }}">
                All halls
            </a>
            @foreach ($halls as $hall)
                <a href="{{ route('dashboard', ['blood_group' => $selectedBloodGroup, 'hall' => $hall]) }}"
                   class="shrink-0 rounded-full border px-3 py-1.5 text-xs transition-colors {{ $selectedHall === $hall ? 'border-foreground bg-secondary font-medium' : 'border-border bg-card text-muted-foreground' }}">
                    {{ $hall }}
                </a>
            @endforeach
        </div>
    </div>

    @if ($requests->isEmpty())
        <div class="surface-panel flex flex-col items-center px-6 py-14 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-surface text-muted-foreground">
                <x-icon name="inbox" class="size-6" />
            </span>
            <h3 class="mt-4 text-base font-semibold">No active requests right now</h3>
            <p class="mt-1.5 max-w-sm text-sm text-muted-foreground">
                That's good news. We'll notify you the moment someone near your hall needs blood.
            </p>
        </div>
    @else
        <ul class="space-y-3">
            @foreach ($requests as $request)
                <li>
                    @include('partials.request-card', ['request' => $request])
                </li>
            @endforeach
        </ul>
    @endif
</x-app-layout>
