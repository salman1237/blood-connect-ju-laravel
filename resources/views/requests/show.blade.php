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
    @elseif (session('status') === 'donation-confirmed')
        <x-auth-session-status status="Thanks for confirming." class="mb-5" />
    @elseif (session('status') === 'request-reported')
        <x-auth-session-status status="Report submitted — an admin will review it." class="mb-5" />
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
                        <li class="rounded-xl border border-border p-3">
                            <div class="flex items-center justify-between gap-3">
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
                            </div>

                            {{-- Post-fulfillment mutual confirmation: both sides independently
                                 attest the donation actually happened before it counts toward
                                 donation_history / trust_score. --}}
                            @if ($response->status === 'confirmed' && $bloodRequest->status === 'fulfilled')
                                @php
                                    $viewerIsRequester = auth()->id() === $bloodRequest->requester_id;
                                    $viewerIsThisDonor = auth()->id() === $response->donor_id;
                                    $viewerHasConfirmed = $viewerIsRequester ? $response->requester_confirmed_at : $response->donor_confirmed_at;
                                @endphp
                                <div class="mt-2.5 flex items-center justify-between gap-3 border-t border-border pt-2.5">
                                    @if ($response->isMutuallyConfirmed())
                                        <p class="flex items-center gap-1 text-xs font-medium text-success">
                                            <x-icon name="check" class="size-3.5" /> Donation confirmed by both sides
                                        </p>
                                    @elseif ($viewerIsRequester || $viewerIsThisDonor)
                                        <p class="text-xs text-muted-foreground">
                                            @if ($viewerHasConfirmed)
                                                You confirmed — waiting on the {{ $viewerIsRequester ? 'donor' : 'requester' }}.
                                            @else
                                                Did this donation happen?
                                            @endif
                                        </p>
                                        @can('confirmDonation', $response)
                                            <form method="POST" action="{{ route('requests.responses.confirm-donation', [$bloodRequest, $response]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <x-button type="submit" size="sm">Confirm donation</x-button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            @endif
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

            @can('report', $bloodRequest)
                <div class="mt-4 border-t border-border pt-4" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-destructive">
                        <x-icon name="flag" class="size-3.5" /> Report this request
                    </button>

                    <form method="POST" action="{{ route('requests.report', $bloodRequest) }}" x-show="open" x-cloak class="mt-3 space-y-3">
                        @csrf
                        <div class="space-y-1.5">
                            <x-input-label value="Reason" />
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach ($reportReasons as $value => $label)
                                    <label class="flex cursor-pointer items-center justify-center rounded-lg border border-border px-2 py-2 text-xs has-[:checked]:border-primary has-[:checked]:bg-accent has-[:checked]:font-medium has-[:checked]:text-accent-foreground">
                                        <input type="radio" name="reason" value="{{ $value }}" class="sr-only" required>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('reason')" />
                        </div>
                        <div class="space-y-1.5">
                            <x-input-label for="details" value="Details (optional)" />
                            <textarea id="details" name="details" rows="2" class="block w-full rounded-lg border border-border bg-card px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"></textarea>
                            <x-input-error :messages="$errors->get('details')" />
                        </div>
                        <x-button type="submit" variant="outline" size="sm">Submit report</x-button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
