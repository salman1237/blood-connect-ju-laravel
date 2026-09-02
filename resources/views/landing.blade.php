<x-guest-layout>
    <x-slot name="title">Blood Connect JU — Campus Blood Donation Network</x-slot>

    <div class="min-h-screen bg-background">
        <header class="sticky top-0 z-20 border-b border-border bg-background/85 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-2 px-4 py-3">
                <x-logo compact />
                <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                    <x-language-toggle />
                    <x-button :href="route('login')" variant="ghost" size="sm" class="hidden sm:inline-flex">{{ __('common.login') }}</x-button>
                    <x-button :href="route('register')" size="sm">{{ __('common.sign_up') }}</x-button>
                </div>
            </div>
        </header>

        <main>
            <section class="mx-auto max-w-6xl px-4 py-12 sm:py-16">
                <div class="grid items-start gap-10 lg:grid-cols-2">
                    <div>
                        <span class="inline-flex items-center gap-2 rounded-full bg-accent px-3 py-1 text-xs font-medium text-accent-foreground">
                            <x-icon name="droplet" class="size-3.5" /> {{ number_format($donorCount) }} {{ __('landing.donor_badge') }}
                        </span>
                        <h1 class="mt-5 text-4xl font-semibold leading-[1.1] sm:text-5xl">
                            {{ __('landing.headline') }}
                        </h1>
                        <p class="mt-4 max-w-xl text-base leading-relaxed text-muted-foreground">
                            {{ __('landing.subtext') }}
                        </p>
                        <div class="mt-7 flex flex-wrap gap-3">
                            <x-button :href="route('register')" size="lg">{{ __('landing.cta_signup') }}</x-button>
                            <x-button :href="route('login')" size="lg" variant="outline">{{ __('landing.cta_login') }}</x-button>
                        </div>
                        @php
                            $stats = [
                                ['k' => __('landing.stat_fulfilled'), 'v' => number_format($fulfilledCount)],
                                ['k' => __('landing.stat_response'), 'v' => $avgResponseMinutes === null ? '—' : ($avgResponseMinutes < 60 ? round($avgResponseMinutes).' min' : round($avgResponseMinutes / 60, 1).' hr')],
                                ['k' => __('landing.stat_halls'), 'v' => number_format($hallsAndDepartmentsCount)],
                            ];
                        @endphp
                        {{-- Labels and numbers are two separate grid rows (not one div per
                             stat) so a label that wraps to two lines never pushes its own
                             number out of alignment with its neighbors. --}}
                        <dl class="mt-10 grid grid-cols-3 gap-x-4 gap-y-1.5 border-t border-border pt-6">
                            @foreach ($stats as $stat)
                                <dt class="self-end text-xs text-muted-foreground">{{ $stat['k'] }}</dt>
                            @endforeach
                            @foreach ($stats as $stat)
                                <dd class="text-2xl font-semibold tabular-nums">{{ $stat['v'] }}</dd>
                            @endforeach
                        </dl>
                    </div>

                    <div class="surface-panel p-5">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h2 class="text-sm font-semibold">{{ __('landing.live_requests_title') }}</h2>
                                <p class="text-xs text-muted-foreground">{{ __('landing.live_requests_subtitle') }}</p>
                            </div>
                            <span class="inline-flex items-center gap-1.5 text-xs text-primary">
                                <span class="size-2 animate-pulse rounded-full bg-primary"></span> {{ __('landing.live') }}
                            </span>
                        </div>
                        @if ($liveRequests->isEmpty())
                            <p class="py-6 text-center text-sm text-muted-foreground">No open requests right now — hopefully it stays that way.</p>
                        @else
                            <ul class="space-y-3">
                                @foreach ($liveRequests as $request)
                                    <li class="rounded-xl border border-border p-3">
                                        <div class="flex items-start gap-3">
                                            <x-blood-drop :group="$request->blood_group" />
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-sm font-semibold">{{ __('landing.units_needed', ['count' => $request->units_needed]) }}</span>
                                                    <x-urgency-badge :urgency="$request->urgency" />
                                                    @if ($request->is_verified)
                                                        <x-verified-badge />
                                                    @endif
                                                </div>
                                                <p class="mt-1 flex items-center gap-1 truncate text-xs text-muted-foreground">
                                                    <x-icon name="map-pin" class="size-3" /> {{ $request->hospital_name }}
                                                </p>
                                                <p class="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground">
                                                    <x-icon name="clock" class="size-3" /> {{ $request->created_at->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <x-button :href="route('login')" variant="outline" class="mt-4 w-full">{{ __('landing.see_all') }}</x-button>
                    </div>
                </div>
            </section>

            <section class="border-y border-border bg-surface">
                <div class="mx-auto grid max-w-6xl gap-6 px-4 py-14 sm:grid-cols-3">
                    @foreach ([
                        ['icon' => 'shield-check', 't' => __('landing.feature_1_title'), 'd' => __('landing.feature_1_desc')],
                        ['icon' => 'heart-handshake', 't' => __('landing.feature_2_title'), 'd' => __('landing.feature_2_desc')],
                        ['icon' => 'clock', 't' => __('landing.feature_3_title'), 'd' => __('landing.feature_3_desc')],
                    ] as $feature)
                        <div class="surface-panel p-5">
                            <span class="flex size-10 items-center justify-center rounded-xl bg-accent text-accent-foreground">
                                <x-icon :name="$feature['icon']" class="size-5" />
                            </span>
                            <h3 class="mt-4 text-base font-semibold">{{ $feature['t'] }}</h3>
                            <p class="mt-1.5 text-sm text-muted-foreground">{{ $feature['d'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="mx-auto max-w-3xl px-4 py-16 text-center">
                <h2 class="text-2xl font-semibold">{{ __('landing.ready_title') }}</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ __('landing.ready_subtitle') }}
                </p>
                <x-button :href="route('register')" size="lg" class="mt-6">{{ __('landing.ready_cta') }}</x-button>
            </section>
        </main>

        <footer class="border-t border-border py-8 text-center text-xs text-muted-foreground">
            <div class="mb-4">
                @include('partials.org-credit', ['setting' => $orgSetting])
            </div>
            {{ __('landing.footer') }}
            <div class="mt-2">
                <a href="{{ route('privacy') }}" class="underline hover:text-foreground">Privacy Policy</a>
            </div>
        </footer>
    </div>
</x-guest-layout>
