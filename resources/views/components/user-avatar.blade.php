@props(['user'])

@if ($user->avatar_url)
    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" {{ $attributes->class(['size-9 shrink-0 rounded-full object-cover']) }}>
@else
    <span {{ $attributes->class(['items-center justify-center rounded-full bg-secondary text-xs font-semibold']) }}>
        {{ collect(explode(' ', $user->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}
    </span>
@endif
