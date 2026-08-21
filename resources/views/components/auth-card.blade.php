@props(['title' => null, 'subtitle' => null])

<div class="min-h-screen bg-background">
    <header class="flex items-center justify-between gap-2 px-4 py-4 sm:px-8">
        <x-logo compact />
        <x-language-toggle />
    </header>
    <main class="mx-auto w-full max-w-md px-4 pb-16 pt-4">
        <div class="surface-panel p-6">
            @isset($step)
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-primary">{{ $step }}</p>
            @endisset
            @if ($title)
                <h1 class="text-2xl font-semibold">{{ $title }}</h1>
            @endif
            @if ($subtitle)
                <p class="mt-1.5 text-sm text-muted-foreground">{{ $subtitle }}</p>
            @endif
            <div class="mt-6 space-y-4">
                {{ $slot }}
            </div>
        </div>
        @isset($footer)
            <div class="mt-5 text-center text-sm text-muted-foreground">
                {{ $footer }}
            </div>
        @endisset
    </main>
</div>
