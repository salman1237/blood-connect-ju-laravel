{{--
    Wraps a horizontally-scrolling row (filter chips, etc.) with edge fades
    hinting there's more to scroll — without this, a chip row that's cut off
    mid-chip is the only clue, easy to miss on a phone. Static (not
    scroll-position-aware) by design: these rows always have more options
    than fit on a phone, so the fade is safe to show unconditionally rather
    than adding JS scroll-tracking for a marginal gain.
--}}
<div {{ $attributes->class(['relative']) }}>
    <div class="pointer-events-none absolute inset-y-0 left-0 z-10 w-6 bg-gradient-to-r from-background to-transparent"></div>
    <div class="pointer-events-none absolute inset-y-0 right-0 z-10 w-6 bg-gradient-to-l from-background to-transparent"></div>
    {{ $slot }}
</div>
