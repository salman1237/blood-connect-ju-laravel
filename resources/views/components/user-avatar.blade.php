{{--
    Caller must always pass both a display utility (flex/inline-flex,
    since centering utilities are no-ops without one) and a size — this
    component deliberately bakes in neither, since Tailwind's utility
    cascade order makes "default size + override size" combinations
    unpredictable rather than a clean caller-wins override.
--}}
@props(['user'])

@if ($user->avatar_url)
    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" {{ $attributes->class(['shrink-0 rounded-full object-cover']) }}>
@else
    <span {{ $attributes->class(['shrink-0 items-center justify-center rounded-full bg-secondary font-semibold']) }}>
        {{ collect(explode(' ', $user->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}
    </span>
@endif
