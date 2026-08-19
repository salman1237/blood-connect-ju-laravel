<x-guest-layout>
    <x-slot name="title">Forgot Password — Blood Connect JU</x-slot>

    <x-auth-card
        title="Forgot your password?"
        subtitle="Enter your email and we'll send a reset link."
    >
        <x-slot name="footer">
            <a href="{{ route('login') }}" class="font-medium text-primary underline">Back to login</a>
        </x-slot>

        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <div class="space-y-1.5">
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" type="email" name="email" placeholder="you@example.com" :value="old('email')" required autofocus />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <x-button type="submit" size="lg" class="w-full">Send reset link</x-button>
        </form>
    </x-auth-card>
</x-guest-layout>
