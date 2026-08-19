@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-lg bg-success/[12%] p-3 text-sm font-medium text-success']) }}>
        {{ $status }}
    </div>
@endif
