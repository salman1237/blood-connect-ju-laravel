@props(['status'])

@php
    $labels = [
        'open' => 'Open',
        'donor_found' => 'Donor found',
        'fulfilled' => 'Fulfilled',
        'expired' => 'Expired',
    ];

    $classes = match ($status) {
        'fulfilled' => 'border-transparent bg-success/15 text-success',
        'donor_found' => 'border-transparent bg-info/15 text-info',
        'expired' => 'border-transparent bg-muted text-muted-foreground',
        default => 'border-border text-muted-foreground',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-medium', $classes]) }}>
    {{ $labels[$status] ?? ucfirst($status) }}
</span>
