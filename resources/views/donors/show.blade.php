<x-app-layout title="{{ $donor->name }}" subtitle="Donor profile">
    <div class="mx-auto max-w-2xl space-y-5">
        <div class="surface-panel p-5 sm:p-6">
            <div class="flex items-center gap-4">
                <x-user-avatar :user="$donor" class="flex size-14 text-lg" />
                <div class="min-w-0 flex-1">
                    <h2 class="truncate text-lg font-semibold">{{ $donor->name }}</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ $donor->hall ?? $donor->department ?? 'Campus' }}
                    </p>
                </div>
                <x-blood-drop :group="$donor->donorProfile->blood_group" size="lg" />
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-border pt-4">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $donor->donorProfile->is_available ? 'bg-success/15 text-success' : 'bg-muted text-muted-foreground' }}">
                    {{ $donor->donorProfile->is_available ? 'Available' : 'Unavailable' }}
                </span>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $donor->donorProfile->is_eligible ? 'bg-success/15 text-success' : 'bg-warning/20 text-warning-foreground' }}">
                    {{ $donor->donorProfile->is_eligible ? 'Eligible to donate' : 'Eligible '.$donor->donorProfile->next_eligible_date->diffForHumans() }}
                </span>
                <span class="text-xs text-muted-foreground">Trust score: {{ $donor->donorProfile->trust_score }}</span>
            </div>
        </div>

        @if ($donor->badges->isNotEmpty())
            <div class="surface-panel p-5 sm:p-6">
                <h3 class="text-sm font-semibold">Badges</h3>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($donor->badges as $badge)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-accent px-3 py-1 text-xs font-medium text-accent-foreground" title="{{ $badge->description }}">
                            <x-icon name="award" class="size-3.5" /> {{ $badge->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="surface-panel p-5 sm:p-6">
            <h3 class="text-sm font-semibold">Donation history ({{ $donationHistory->count() }})</h3>
            @if ($donationHistory->isEmpty())
                <p class="mt-2 text-sm text-muted-foreground">No confirmed donations yet.</p>
            @else
                <ul class="mt-3 space-y-2">
                    @foreach ($donationHistory as $entry)
                        <li class="flex items-center justify-between gap-3 rounded-xl border border-border p-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ $entry->bloodRequest?->hospital_name ?? 'Off-platform donation' }}</p>
                                <p class="text-xs text-muted-foreground">{{ $entry->confirmed_at->format('M j, Y') }}</p>
                            </div>
                            <x-icon name="heart-handshake" class="size-4 shrink-0 text-primary" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>
