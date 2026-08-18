@props(['href' => '/'])

<a href="{{ $href }}" {{ $attributes->class(['flex items-center gap-2']) }}>
    <span class="flex size-9 items-center justify-center rounded-xl bg-primary text-primary-foreground">
        <x-icon name="droplet" class="size-5" />
    </span>
    <span class="leading-tight">
        <span class="block text-sm font-semibold tracking-tight">Blood Connect JU</span>
        <span class="block text-[11px] text-muted-foreground">Jahangirnagar University</span>
    </span>
</a>
