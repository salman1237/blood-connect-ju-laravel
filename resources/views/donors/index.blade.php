<x-app-layout title="Donors" subtitle="Browse the campus donor directory">
    <div class="mb-4 space-y-3">
        <form method="GET" action="{{ route('donors.index') }}">
            <input type="hidden" name="blood_group" value="{{ $selectedBloodGroup }}">
            <input type="hidden" name="hall" value="{{ $selectedHall }}">
            <x-text-input type="search" name="search" placeholder="Search donors by name" :value="$search" />
        </form>

        <x-scroll-fade class="flex gap-1.5 overflow-x-auto pb-1">
            <a href="{{ route('donors.index', ['hall' => $selectedHall, 'search' => $search]) }}"
               class="shrink-0 rounded-full border px-3 py-2 text-xs font-medium transition-colors {{ $selectedBloodGroup === '' ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card text-muted-foreground' }}">
                All
            </a>
            @foreach ($bloodGroups as $group)
                <a href="{{ route('donors.index', ['blood_group' => $group, 'hall' => $selectedHall, 'search' => $search]) }}"
                   class="shrink-0 rounded-full border px-3 py-2 text-xs font-medium transition-colors {{ $selectedBloodGroup === $group ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card text-muted-foreground' }}">
                    {{ $group }}
                </a>
            @endforeach
        </x-scroll-fade>
        <x-scroll-fade class="flex gap-1.5 overflow-x-auto pb-1">
            <a href="{{ route('donors.index', ['blood_group' => $selectedBloodGroup, 'search' => $search]) }}"
               class="shrink-0 rounded-full border px-3 py-2 text-xs transition-colors {{ $selectedHall === '' ? 'border-foreground bg-secondary font-medium' : 'border-border bg-card text-muted-foreground' }}">
                All halls
            </a>
            @foreach ($halls as $hall)
                <a href="{{ route('donors.index', ['blood_group' => $selectedBloodGroup, 'hall' => $hall, 'search' => $search]) }}"
                   class="shrink-0 rounded-full border px-3 py-2 text-xs transition-colors {{ $selectedHall === $hall ? 'border-foreground bg-secondary font-medium' : 'border-border bg-card text-muted-foreground' }}">
                    {{ $hall }}
                </a>
            @endforeach
        </x-scroll-fade>
    </div>

    @if ($donors->isEmpty())
        <div class="surface-panel flex flex-col items-center px-6 py-14 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-surface text-muted-foreground">
                <x-icon name="search" class="size-6" />
            </span>
            <h3 class="mt-4 text-base font-semibold">No donors match these filters</h3>
            <p class="mt-1.5 max-w-sm text-sm text-muted-foreground">Try a different name, blood group, or hall.</p>
        </div>
    @else
        <ul class="grid gap-3 sm:grid-cols-2">
            @foreach ($donors as $donor)
                <li class="surface-panel flex items-center gap-3 p-4 transition-shadow hover:shadow-lift">
                    <a href="{{ route('donors.show', $donor->user) }}" class="flex min-w-0 flex-1 items-center gap-3">
                        <x-user-avatar :user="$donor->user" class="flex size-11 shrink-0 text-sm" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">{{ $donor->user->name }}</p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ $donor->user->hall ?? $donor->user->department ?? 'Campus' }}
                            </p>
                        </div>
                        <x-blood-drop :group="$donor->blood_group" size="sm" />
                    </a>
                    <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $donor->is_available ? 'bg-success/15 text-success' : 'bg-muted text-muted-foreground' }}">
                        {{ $donor->is_available ? 'Available' : 'Unavailable' }}
                    </span>
                    @if ($donor->user->phoneVisibleTo(auth()->user()) && $donor->user->whatsapp_url)
                        <a href="{{ $donor->user->whatsapp_url }}" target="_blank" rel="noopener"
                           class="flex size-9 shrink-0 items-center justify-center rounded-full bg-[#25D366] text-white transition hover:opacity-90"
                           aria-label="Message {{ $donor->user->name }} on WhatsApp" title="Message on WhatsApp">
                            <x-whatsapp-icon class="size-4" />
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>

        <div class="mt-6">
            {{ $donors->links() }}
        </div>
    @endif
</x-app-layout>
