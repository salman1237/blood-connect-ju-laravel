@php
    $steps = ['open' => 'Open', 'donor_found' => 'Donor found', 'fulfilled' => 'Fulfilled'];
    $stepKeys = array_keys($steps);
    $currentIndex = $bloodRequest->status === 'expired' ? -1 : array_search($bloodRequest->status, $stepKeys, true);
@endphp

<x-app-layout title="Request details" subtitle="{{ $bloodRequest->hospital_name }}">
    @if (session('status') === 'request-updated')
        <x-auth-session-status status="Request status updated." class="mb-5" />
    @elseif (session('status') === 'response-recorded')
        <x-auth-session-status status="Thanks — the requester has been notified that you can donate." class="mb-5" />
    @elseif (session('status') === 'donor-confirmed')
        <x-auth-session-status status="Donor confirmed." class="mb-5" />
    @endif

    <div class="mx-auto max-w-2xl space-y-5">
        <div class="surface-panel p-5 sm:p-6">
            <div class="flex items-start gap-4">
                <x-blood-drop :group="$bloodRequest->blood_group" size="lg" />
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold">{{ $bloodRequest->units_needed }} {{ Str::plural('unit', $bloodRequest->units_needed) }} needed</h2>
                        <x-urgency-badge :urgency="$bloodRequest->urgency" />
                        @if ($bloodRequest->is_verified)
                            <x-verified-badge />
                        @endif
                    </div>
                    <p class="mt-1 flex items-center gap-1 text-sm text-muted-foreground">
                        <x-icon name="map-pin" class="size-3.5 shrink-0" />
                        {{ $bloodRequest->hospital_name }}{{ $bloodRequest->location ? ', '.$bloodRequest->location : '' }}
                    </p>
                    <p class="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground">
                        <x-icon name="clock" class="size-3" /> Posted {{ $bloodRequest->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>

            {{-- Status tracker --}}
            <div class="mt-6">
                @if ($bloodRequest->status === 'expired')
                    <x-status-pill status="expired" />
                @else
                    <div class="flex items-center">
                        @foreach ($steps as $key => $label)
                            @php $isDone = $loop->index <= $currentIndex; @endphp
                            <div class="flex flex-1 flex-col items-center">
                                <div class="flex w-full items-center">
                                    @if (! $loop->first)
                                        <span class="h-0.5 flex-1 {{ $loop->index <= $currentIndex ? 'bg-primary' : 'bg-border' }}"></span>
                                    @endif
                                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold {{ $isDone ? 'bg-primary text-primary-foreground' : 'bg-secondary text-muted-foreground' }}">
                                        {{ $loop->iteration }}
                                    </span>
                                    @if (! $loop->last)
                                        <span class="h-0.5 flex-1 {{ $loop->index < $currentIndex ? 'bg-primary' : 'bg-border' }}"></span>
                                    @endif
                                </div>
                                <span class="mt-1.5 text-[11px] {{ $isDone ? 'font-medium text-foreground' : 'text-muted-foreground' }}">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="mt-6 flex flex-col gap-2 sm:flex-row">
                @can('respond', $bloodRequest)
                    <form method="POST" action="{{ route('requests.respond', $bloodRequest) }}" class="flex-1">
                        @csrf
                        <x-button type="submit" class="w-full">I can donate</x-button>
                    </form>
                @endcan

                @can('fulfill', $bloodRequest)
                    <form method="POST" action="{{ route('requests.fulfill', $bloodRequest) }}" class="flex-1">
                        @csrf
                        <x-button type="submit" variant="outline" class="w-full">
                            {{ $bloodRequest->status === 'open' ? 'Mark donor found' : 'Mark fulfilled' }}
                        </x-button>
                    </form>
                @endcan
            </div>
        </div>

        @if ($bloodRequest->responses->isNotEmpty())
            <div class="surface-panel p-5 sm:p-6">
                <h3 class="text-sm font-semibold">Responders ({{ $bloodRequest->responses->count() }})</h3>
                <ul class="mt-3 space-y-2">
                    @foreach ($bloodRequest->responses as $response)
                        <li class="flex items-center justify-between gap-3 rounded-xl border border-border p-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ $response->donor->name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ $response->status === 'confirmed' ? 'Confirmed donor' : 'Responded '.$response->created_at->diffForHumans() }}
                                </p>
                            </div>
                            @can('confirm', $response)
                                <form method="POST" action="{{ route('requests.responses.confirm', [$bloodRequest, $response]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-button type="submit" variant="outline" size="sm">Confirm</x-button>
                                </form>
                            @else
                                @if ($response->status === 'confirmed')
                                    <span class="inline-flex items-center rounded-full bg-success/15 px-2.5 py-0.5 text-[11px] font-medium text-success">Confirmed</span>
                                @endif
                            @endcan
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($bloodRequest->patient_context)
            <div class="surface-panel p-5 sm:p-6">
                <h3 class="text-sm font-semibold">Patient context</h3>
                <p class="mt-1.5 text-sm text-muted-foreground">{{ $bloodRequest->patient_context }}</p>
            </div>
        @endif

        <div class="surface-panel p-5 sm:p-6">
            <h3 class="text-sm font-semibold">Contact</h3>
            <p class="mt-1.5 text-sm text-muted-foreground">{{ $bloodRequest->contact_method }}</p>
            <h3 class="mt-4 text-sm font-semibold">Requested by</h3>
            <p class="mt-1.5 text-sm text-muted-foreground">
                {{ $bloodRequest->requester->name }}
                @if ($bloodRequest->requester->hall || $bloodRequest->requester->department)
                    · {{ $bloodRequest->requester->hall ?? $bloodRequest->requester->department }}
                @endif
            </p>
        </div>
    </div>
</x-app-layout>
