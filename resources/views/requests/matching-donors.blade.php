<x-app-layout title="Matching donors" subtitle="{{ $bloodRequest->blood_group }} · {{ $bloodRequest->hospital_name }}">
    @if (session('status') === 'request-created')
        <x-auth-session-status status="Request posted. It'll expire automatically in 72 hours if not resolved." class="mb-5" />
    @endif

    <div class="mx-auto max-w-2xl space-y-5">
        <div class="surface-panel p-5 sm:p-6">
            <div class="flex items-center gap-3">
                <x-blood-drop :group="$bloodRequest->blood_group" />
                <div class="min-w-0 flex-1">
                    <h2 class="text-base font-semibold">Available donors for this request</h2>
                    <p class="text-xs text-muted-foreground">
                        Ranked by best match first — same blood group, then same hall or department.
                    </p>
                </div>
            </div>
            <a href="{{ route('requests.show', $bloodRequest) }}" class="mt-4 inline-block text-xs font-medium text-primary hover:underline">
                View my request
            </a>
        </div>

        @if ($donors->isEmpty())
            <div class="surface-panel flex flex-col items-center px-6 py-14 text-center">
                <span class="flex size-14 items-center justify-center rounded-2xl bg-surface text-muted-foreground">
                    <x-icon name="search" class="size-6" />
                </span>
                <h3 class="mt-4 text-base font-semibold">No available donors right now</h3>
                <p class="mt-1.5 max-w-sm text-sm text-muted-foreground">
                    Everyone compatible has already been notified — they'll see your request the moment they're free to help.
                </p>
                <x-button :href="route('donors.index')" variant="outline" class="mt-4">Browse the donor directory</x-button>
            </div>
        @else
            <ul class="space-y-2">
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
                        @if ($donor->user->whatsapp_url)
                            <a href="{{ $donor->user->whatsapp_url }}" target="_blank" rel="noopener"
                               class="flex size-9 shrink-0 items-center justify-center rounded-full bg-[#25D366] text-white transition hover:opacity-90"
                               aria-label="Message {{ $donor->user->name }} on WhatsApp" title="Message on WhatsApp">
                                <x-whatsapp-icon class="size-4" />
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-app-layout>
