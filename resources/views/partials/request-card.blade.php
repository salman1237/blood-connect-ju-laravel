@props(['request'])

<a href="{{ route('requests.show', $request) }}" class="surface-panel block p-4 transition-shadow hover:shadow-lift">
    <div class="flex gap-4">
        <x-blood-drop :group="$request->blood_group" />
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold">{{ $request->units_needed }} {{ Str::plural('unit', $request->units_needed) }} needed</span>
                <x-urgency-badge :urgency="$request->urgency" />
                @if ($request->is_verified)
                    <x-verified-badge />
                @endif
                <x-status-pill :status="$request->status" />
            </div>
            <p class="mt-1.5 flex items-center gap-1 text-sm text-muted-foreground">
                <x-icon name="map-pin" class="size-3.5 shrink-0" />
                {{ $request->hospital_name }}{{ $request->location ? ', '.$request->location : '' }}
            </p>
            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                <span class="flex items-center gap-1">
                    <x-icon name="clock" class="size-3" /> {{ $request->created_at->diffForHumans() }}
                </span>
                <span>{{ $request->requester->name }}{{ $request->requester->hall ? ' · '.$request->requester->hall : '' }}</span>
            </p>
        </div>
    </div>
</a>
