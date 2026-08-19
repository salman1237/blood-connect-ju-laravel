<x-guest-layout>
    <x-slot name="title">Verify your email — Blood Connect JU</x-slot>

    <x-auth-card
        title="Verify your email"
        subtitle="We sent a confirmation link to your email address. Click it to activate your donor profile."
    >
        @if (session('status') == 'verification-link-sent')
            <x-auth-session-status status="A new verification link has been sent to your email address." />
        @endif

        <div class="space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-button type="submit" size="lg" class="w-full">Resend verification email</x-button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-button type="submit" variant="ghost" class="w-full">Log out</x-button>
            </form>
        </div>
    </x-auth-card>
</x-guest-layout>
