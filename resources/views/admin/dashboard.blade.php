<x-app-layout title="Admin dashboard" subtitle="Platform-wide stats and moderation">
    <div class="mb-6 flex flex-wrap gap-2">
        <x-button :href="route('admin.users.index')" variant="outline" size="sm">Manage users</x-button>
        <x-button :href="route('admin.reports.index')" variant="outline" size="sm">
            Moderation queue
            @if ($pendingReportsCount > 0)
                <span class="ml-1 inline-flex size-5 items-center justify-center rounded-full bg-primary text-[10px] font-semibold text-primary-foreground">{{ min($pendingReportsCount, 9) }}{{ $pendingReportsCount > 9 ? '+' : '' }}</span>
            @endif
        </x-button>
        <x-button :href="route('verify.queue')" variant="outline" size="sm">Verifier queue</x-button>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="surface-panel p-4">
            <p class="text-xs text-muted-foreground">Fulfilled requests</p>
            <p class="mt-1 text-2xl font-semibold text-success">{{ $fulfilledCount }}</p>
        </div>
        <div class="surface-panel p-4">
            <p class="text-xs text-muted-foreground">Expired requests</p>
            <p class="mt-1 text-2xl font-semibold text-muted-foreground">{{ $expiredCount }}</p>
        </div>
        <div class="surface-panel p-4">
            <p class="text-xs text-muted-foreground">Avg. response time</p>
            <p class="mt-1 text-2xl font-semibold">
                @if ($avgResponseMinutes === null)
                    —
                @elseif ($avgResponseMinutes < 60)
                    {{ round($avgResponseMinutes) }}m
                @else
                    {{ round($avgResponseMinutes / 60, 1) }}h
                @endif
            </p>
        </div>
        <div class="surface-panel p-4">
            <p class="text-xs text-muted-foreground">Total users</p>
            <p class="mt-1 text-2xl font-semibold">{{ $totalUsers }}</p>
        </div>
        <div class="surface-panel p-4">
            <p class="text-xs text-muted-foreground">Active users</p>
            <p class="mt-1 text-2xl font-semibold">{{ $activeUsers }}</p>
        </div>
        <div class="surface-panel p-4">
            <p class="text-xs text-muted-foreground">Pending reports</p>
            <p class="mt-1 text-2xl font-semibold {{ $pendingReportsCount > 0 ? 'text-primary' : '' }}">{{ $pendingReportsCount }}</p>
        </div>
    </div>

    <div class="mt-6 surface-panel p-5 sm:p-6">
        <h3 class="text-sm font-semibold">Donors by blood group</h3>
        @if ($donorsByBloodGroup->isEmpty())
            <p class="mt-2 text-sm text-muted-foreground">No donor profiles yet.</p>
        @else
            <div class="mt-3 grid grid-cols-4 gap-3 sm:grid-cols-8">
                @foreach ($donorsByBloodGroup as $group => $total)
                    <div class="flex flex-col items-center rounded-xl border border-border p-3">
                        <x-blood-drop :group="$group" size="sm" />
                        <span class="mt-1.5 text-sm font-semibold">{{ $total }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
