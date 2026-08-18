@props(['urgency'])

@php
    $labels = [
        'critical' => 'Critical',
        'within_24h' => 'Within 24h',
        'planned' => 'Planned',
    ];

    $classes = match ($urgency) {
        'critical' => 'bg-primary text-primary-foreground',
        'within_24h' => 'bg-warning/20 text-warning-foreground',
        default => 'bg-secondary text-secondary-foreground',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold', $classes]) }}>
    @if ($urgency === 'critical')
        <span class="size-1.5 animate-pulse rounded-full bg-primary-foreground"></span>
    @endif
    {{ $labels[$urgency] ?? ucfirst($urgency) }}
</span>
