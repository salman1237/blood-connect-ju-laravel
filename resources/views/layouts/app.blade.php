<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title . ' — ' . config('app.name') : config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        @php
            $primaryNav = [
                ['route' => 'dashboard', 'label' => __('nav.home'), 'icon' => 'home'],
                ['route' => 'donors.index', 'label' => __('nav.donors'), 'icon' => 'search'],
                ['route' => 'notifications.index', 'label' => __('nav.alerts'), 'icon' => 'bell'],
                ['route' => 'leaderboard', 'label' => __('nav.ranks'), 'icon' => 'trophy'],
                ['route' => 'profile.edit', 'label' => __('nav.profile'), 'icon' => 'user'],
            ];
            $secondaryNav = [
                ['route' => 'settings.edit', 'label' => __('nav.settings'), 'icon' => 'settings'],
                ['route' => 'verify.queue', 'label' => __('nav.verifier_queue'), 'icon' => 'shield-check'],
                ['route' => 'admin.dashboard', 'label' => __('nav.admin_dashboard'), 'icon' => 'bar-chart'],
            ];
            $unreadNotificationsCount = Route::has('notifications.index') ? auth()->user()->unreadNotifications()->count() : 0;
        @endphp

        <aside class="fixed inset-y-0 left-0 hidden w-64 flex-col border-r border-sidebar-border bg-sidebar p-4 lg:flex">
            <x-logo class="px-2 py-2" />

            <nav class="mt-6 flex flex-1 flex-col gap-1">
                @foreach ($primaryNav as $item)
                    @if (Route::has($item['route']))
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs($item['route']) ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-sidebar-foreground hover:bg-sidebar-accent/60' }}">
                            <x-icon :name="$item['icon']" class="size-4" />
                            {{ $item['label'] }}
                            @if ($item['route'] === 'notifications.index' && $unreadNotificationsCount > 0)
                                <span class="ml-auto flex size-5 items-center justify-center rounded-full bg-primary text-[10px] font-semibold text-primary-foreground">{{ min($unreadNotificationsCount, 9) }}{{ $unreadNotificationsCount > 9 ? '+' : '' }}</span>
                            @endif
                        </a>
                    @endif
                @endforeach

                <div class="my-3 border-t border-sidebar-border"></div>

                @foreach ($secondaryNav as $item)
                    @if (Route::has($item['route']))
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs($item['route']) ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-sidebar-foreground hover:bg-sidebar-accent/60' }}">
                            <x-icon :name="$item['icon']" class="size-4" />
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach

                <div class="mt-auto space-y-3">
                    @if (Route::has('requests.create'))
                        <a href="{{ route('requests.create') }}"
                           class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:opacity-90">
                            <x-icon name="plus" class="size-4" /> {{ __('nav.post_request') }}
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-xs text-muted-foreground hover:bg-sidebar-accent">
                            {{ __('nav.sign_out') }}
                        </button>
                    </form>
                </div>
            </nav>
        </aside>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-20 border-b border-border bg-background/85 backdrop-blur">
                <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3">
                    <div class="min-w-0">
                        <h1 class="truncate text-base font-semibold sm:text-lg">{{ $title ?? '' }}</h1>
                        @isset($subtitle)
                            <p class="truncate text-xs text-muted-foreground sm:text-sm">{{ $subtitle }}</p>
                        @endisset
                    </div>
                    <div class="flex items-center gap-2">
                        <x-language-toggle />
                        <span class="hidden size-9 items-center justify-center rounded-full bg-secondary text-xs font-semibold sm:inline-flex">
                            {{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}
                        </span>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-5xl px-4 pb-28 pt-5 lg:pb-12">
                {{ $slot }}
            </main>
        </div>

        <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-border bg-card/95 backdrop-blur lg:hidden">
            <div class="mx-auto flex max-w-lg items-stretch justify-between px-2 py-1.5">
                @foreach ($primaryNav as $item)
                    @if (Route::has($item['route']))
                        <a href="{{ route($item['route']) }}"
                           class="relative flex flex-1 flex-col items-center gap-0.5 rounded-lg px-1 py-1.5 text-[10px] font-medium transition-colors {{ request()->routeIs($item['route']) ? 'text-primary' : 'text-muted-foreground' }}">
                            <span class="relative">
                                <x-icon :name="$item['icon']" class="size-5" />
                                @if ($item['route'] === 'notifications.index' && $unreadNotificationsCount > 0)
                                    <span class="absolute -right-1 -top-1 size-2 rounded-full bg-primary"></span>
                                @endif
                            </span>
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        </nav>
    </body>
</html>
