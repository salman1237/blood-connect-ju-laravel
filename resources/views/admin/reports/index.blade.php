<x-app-layout title="Moderation queue" subtitle="Pending reports on live requests">
    @if (session('status') === 'report-reviewed')
        <x-auth-session-status status="Report marked reviewed." class="mb-5" />
    @elseif (session('status') === 'report-dismissed')
        <x-auth-session-status status="Report dismissed." class="mb-5" />
    @endif

    @if ($reports->isEmpty())
        <div class="surface-panel flex flex-col items-center px-6 py-14 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-surface text-muted-foreground">
                <x-icon name="flag" class="size-6" />
            </span>
            <h3 class="mt-4 text-base font-semibold">Nothing to review</h3>
            <p class="mt-1.5 max-w-sm text-sm text-muted-foreground">No pending reports right now.</p>
        </div>
    @else
        <ul class="space-y-3">
            @foreach ($reports as $report)
                <li class="surface-panel p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium">{{ $reportReasons[$report->reason] }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                Reported by {{ $report->reporter->name }} · {{ $report->created_at->diffForHumans() }}
                            </p>
                            @if ($report->details)
                                <p class="mt-2 text-sm text-muted-foreground">{{ $report->details }}</p>
                            @endif
                        </div>
                        <a href="{{ route('requests.show', $report->bloodRequest) }}" class="shrink-0 text-xs font-medium hover:underline">View request</a>
                    </div>

                    <div class="mt-3 border-t border-border pt-3 text-xs text-muted-foreground">
                        {{ $report->bloodRequest->hospital_name }} · {{ $report->bloodRequest->blood_group }} · requested by {{ $report->bloodRequest->requester->name }}
                    </div>

                    <div class="mt-3 flex gap-2">
                        <form method="POST" action="{{ route('admin.reports.review', $report) }}">
                            @csrf
                            <x-button type="submit" variant="outline" size="sm">Mark reviewed</x-button>
                        </form>
                        <form method="POST" action="{{ route('admin.reports.dismiss', $report) }}">
                            @csrf
                            <x-button type="submit" variant="ghost" size="sm">Dismiss</x-button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">
            {{ $reports->links() }}
        </div>
    @endif
</x-app-layout>
