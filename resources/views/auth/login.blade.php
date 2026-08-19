<x-guest-layout>
    <x-slot name="title">Login — Blood Connect JU</x-slot>

    <x-auth-card
        title="Welcome back"
        subtitle="Log in to see active requests near your hall."
    >
        <x-slot name="footer">
            New here?
            <a href="{{ route('register') }}" class="font-medium text-primary underline">Create an account</a>
        </x-slot>

        <x-auth-session-status :status="session('status')" />

        <x-google-button />

        <div class="flex items-center gap-3 text-xs text-muted-foreground">
            <span class="h-px flex-1 bg-border"></span>
            or
            <span class="h-px flex-1 bg-border"></span>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div class="space-y-1.5">
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div class="space-y-1.5">
                <x-input-label for="password" value="Password" />
                <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2 text-muted-foreground">
                    <input type="checkbox" name="remember" class="rounded border-border text-primary focus:ring-primary">
                    Keep me signed in
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="font-medium text-primary hover:underline">Forgot password?</a>
                @endif
            </div>

            <x-button type="submit" size="lg" class="w-full">Login</x-button>
        </form>
    </x-auth-card>
</x-guest-layout>
