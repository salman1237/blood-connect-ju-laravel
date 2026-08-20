<x-app-layout title="Verifier queue" subtitle="Unverified requests awaiting review">
    @if (session('status') === 'request-approved')
        <x-auth-session-status status="Request approved and marked verified." class="mb-5" />
    @elseif (session('status') === 'request-rejected')
        <x-auth-session-status status="Request rejected and removed from the live feed." class="mb-5" />
    @endif

    @if ($requests->isEmpty())
        <div class="surface-panel flex flex-col items-center px-6 py-14 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-surface text-muted-foreground">
                <x-icon name="shield-check" class="size-6" />
            </span>
            <h3 class="mt-4 text-base font-semibold">Nothing to review</h3>
            <p class="mt-1.5 max-w-sm text-sm text-muted-foreground">Every open request has already been verified.</p>
        </div>
    @else
        <ul class="space-y-3">
            @foreach ($requests as $request)
                <li class="surface-panel p-4">
                    <div class="flex gap-4">
                        <x-blood-drop :group="$request->blood_group" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold">{{ $request->units_needed }} {{ Str::plural('unit', $request->units_needed) }} needed</span>
                                <x-urgency-badge :urgency="$request->urgency" />
                            </div>
                            <p class="mt-1.5 flex items-center gap-1 text-sm text-muted-foreground">
                                <x-icon name="map-pin" class="size-3.5 shrink-0" />
                                {{ $request->hospital_name }}{{ $request->location ? ', '.$request->location : '' }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ $request->requester->name }}{{ $request->requester->hall ? ' · '.$request->requester->hall : '' }}
                                · <a href="{{ route('requests.show', $request) }}" class="underline hover:no-underline">View details</a>
                            </p>
                            @if ($request->patient_context)
                                <p class="mt-2 text-sm text-muted-foreground">{{ $request->patient_context }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 flex gap-2 border-t border-border pt-4">
                        <form method="POST" action="{{ route('verify.approve', $request) }}" class="flex-1">
                            @csrf
                            <x-button type="submit" size="sm" class="w-full">
                                <x-icon name="check" class="size-3.5" /> Approve
                            </x-button>
                        </form>
                        <form method="POST" action="{{ route('verify.reject', $request) }}" class="flex-1">
                            @csrf
                            <x-button type="submit" variant="danger" size="sm" class="w-full">
                                <x-icon name="x" class="size-3.5" /> Reject
                            </x-button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">
            {{ $requests->links() }}
        </div>
    @endif
</x-app-layout>
