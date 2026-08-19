{{--
    Shared button primitive. Sizes are floored at 44px tall (h-11+) on every
    breakpoint per the brief's touch-target requirement (Section 8), not just
    on mobile, to keep one consistent rule instead of a breakpoint-reversed one.
--}}
@props(['variant' => 'primary', 'size' => 'default', 'href' => null, 'type' => 'button'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50';

    $variants = [
        'primary' => 'bg-primary text-primary-foreground hover:opacity-90',
        'outline' => 'border border-border bg-card text-foreground hover:bg-secondary',
        'ghost' => 'text-foreground hover:bg-secondary',
        'danger' => 'border border-destructive text-destructive hover:bg-destructive/10',
    ];

    $sizes = [
        'sm' => 'h-11 px-3 text-xs',
        'default' => 'h-11 px-4 text-sm',
        'lg' => 'h-12 px-6 text-base',
    ];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['default']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
