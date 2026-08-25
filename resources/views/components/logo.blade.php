{{--
    compact: hides the "Jahangirnagar University" line below sm — for use
    wherever the logo shares a header row with other controls (language
    toggle, nav buttons) that would otherwise force horizontal overflow on
    narrow screens. The full two-line wordmark is safe wherever the logo has
    a row to itself (e.g. the desktop-only sidebar).
--}}
@props(['href' => '/', 'compact' => false])

<a href="{{ $href }}" {{ $attributes->class(['flex min-w-0 items-center gap-2']) }}>
    <img src="{{ asset('images/logo-mark.png') }}" alt="" class="h-9 w-auto shrink-0" />
    <span class="min-w-0 leading-tight">
        <span class="block truncate text-sm font-semibold tracking-tight">Blood Connect JU</span>
        <span @class(['truncate text-[11px] text-muted-foreground', 'hidden sm:block' => $compact, 'block' => ! $compact])>Jahangirnagar University</span>
    </span>
</a>
