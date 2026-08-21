<x-app-layout title="Donation history" subtitle="Confirmed donations and what you've earned">
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
        <div class="surface-panel flex flex-col items-center px-6 py-14 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-surface text-muted-foreground">
                <x-icon name="heart-handshake" class="size-6" />
            </span>
            <h3 class="mt-4 text-base font-semibold">No confirmed donations yet</h3>
            <p class="mt-1.5 max-w-sm text-sm text-muted-foreground">Once a request you helped with is mutually confirmed, it'll show up here.</p>
        </div>
    @else
        <ul class="space-y-2">
            @foreach ($donationHistory as $entry)
                <li class="surface-panel flex items-center justify-between gap-3 p-4">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium">{{ $entry->bloodRequest?->hospital_name ?? 'Off-platform donation' }}</p>
                        <p class="text-xs text-muted-foreground">{{ $entry->confirmed_at->format('M j, Y') }}</p>
                    </div>
                    <x-icon name="heart-handshake" class="size-4 shrink-0 text-primary" />
                </li>
            @endforeach
        </ul>
    @endif
</x-app-layout>
