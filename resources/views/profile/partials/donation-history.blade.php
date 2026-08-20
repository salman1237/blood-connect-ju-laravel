<section>
    <x-section-title title="Donation history & badges" subtitle="Confirmed donations and what you've earned." />

    @if ($badges->isNotEmpty())
        <div class="mb-5 flex flex-wrap gap-2">
            @foreach ($badges as $badge)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-accent px-3 py-1 text-xs font-medium text-accent-foreground" title="{{ $badge->description }}">
                    <x-icon name="award" class="size-3.5" /> {{ $badge->name }}
                </span>
            @endforeach
        </div>
    @endif

    @if ($donationHistory->isEmpty())
        <p class="text-sm text-muted-foreground">No confirmed donations yet — once a request you helped with is mutually confirmed, it'll show up here.</p>
    @else
        <ul class="space-y-2">
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
</section>
