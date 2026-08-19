<x-guest-layout>
    <x-slot name="title">Reset Password — Blood Connect JU</x-slot>

    <x-auth-card
        title="Set a new password"
        subtitle="Choose something you haven't used before."
    >
        <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="space-y-1.5">
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div class="space-y-1.5">
                <x-input-label for="password" value="New password" />
                <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div class="space-y-1.5">
                <x-input-label for="password_confirmation" value="Confirm new password" />
                <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" />
            </div>

            <x-button type="submit" size="lg" class="w-full">Update password</x-button>
        </form>
    </x-auth-card>
</x-guest-layout>
