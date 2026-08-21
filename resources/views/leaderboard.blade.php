<x-app-layout title="Leaderboard" subtitle="Confirmed donations by hall & department">
    @if ($rankings->isEmpty())
        <div class="surface-panel flex flex-col items-center px-6 py-14 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-surface text-muted-foreground">
                <x-icon name="trophy" class="size-6" />
            </span>
            <h3 class="mt-4 text-base font-semibold">No confirmed donations yet</h3>
            <p class="mt-1.5 max-w-sm text-sm text-muted-foreground">Once donations start getting mutually confirmed, rankings will show up here.</p>
        </div>
    @else
        <ul class="space-y-2">
            @foreach ($rankings as $rank)
                <li class="surface-panel flex items-center gap-4 p-4">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold {{ $loop->index < 3 ? 'bg-primary text-primary-foreground' : 'bg-secondary text-muted-foreground' }}">
                        {{ $loop->iteration }}
                    </span>
                    <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $rank->group_name }}</span>
                    <span class="shrink-0 text-sm font-semibold text-primary">{{ $rank->donations }} {{ Str::plural('donation', $rank->donations) }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</x-app-layout>
