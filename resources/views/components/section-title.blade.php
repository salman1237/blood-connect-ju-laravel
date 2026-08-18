@props(['title', 'subtitle' => null])

<div {{ $attributes->class(['mb-4 flex items-end justify-between gap-4']) }}>
    <div>
        <h2 class="text-lg font-semibold">{{ $title }}</h2>
        @if ($subtitle)
            <p class="text-sm text-muted-foreground">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($action)
        {{ $action }}
    @endisset
</div>
