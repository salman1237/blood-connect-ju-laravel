@props(['group', 'size' => 'md'])

@php
    $sizeClasses = [
        'sm' => 'size-9 text-xs',
        'md' => 'size-12 text-sm',
        'lg' => 'size-16 text-lg',
    ][$size] ?? 'size-12 text-sm';
@endphp

<span {{ $attributes->class(['inline-flex shrink-0 items-center justify-center rounded-xl bg-accent font-semibold text-accent-foreground tabular-nums', $sizeClasses]) }}>
    {{ $group }}
</span>
