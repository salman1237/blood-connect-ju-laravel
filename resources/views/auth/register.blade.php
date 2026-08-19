<x-guest-layout>
    <x-slot name="title">Sign Up — Blood Connect JU</x-slot>

    <x-auth-card
        title="Create your account"
        subtitle="Join the campus donor network — students, staff, faculty, and alumni all welcome."
    >
        <x-slot name="footer">
            Already registered?
            <a href="{{ route('login') }}" class="font-medium text-primary underline">Login</a>
        </x-slot>

        <x-google-button />

        <div class="flex items-center gap-3 text-xs text-muted-foreground">
            <span class="h-px flex-1 bg-border"></span>
            or
            <span class="h-px flex-1 bg-border"></span>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div class="space-y-1.5">
                <x-input-label for="name" value="Full name" />
                <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div class="space-y-1.5">
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" type="email" name="email" placeholder="you@example.com" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div class="space-y-1.5">
                <x-input-label for="password" value="Password" />
                <x-text-input id="password" type="password" name="password" placeholder="At least 8 characters" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div class="space-y-1.5">
                <x-input-label for="password_confirmation" value="Confirm password" />
                <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" />
            </div>

            <div class="space-y-1.5">
                <x-input-label value="I am a" />
                <div class="grid grid-cols-3 gap-2">
                    @foreach (['student' => 'Student', 'staff' => 'Staff', 'faculty' => 'Faculty'] as $value => $label)
                        <label class="flex cursor-pointer items-center justify-center rounded-lg border border-border px-2 py-2.5 text-sm has-[:checked]:border-primary has-[:checked]:bg-accent has-[:checked]:font-medium has-[:checked]:text-accent-foreground">
                            <input type="radio" name="role" value="{{ $value }}" class="sr-only" {{ old('role', 'student') === $value ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('role')" />
            </div>

            <x-button type="submit" size="lg" class="w-full">Create account</x-button>

            <p class="text-center text-xs text-muted-foreground">
                By signing up you agree to donate only when medically eligible.
            </p>
        </form>
    </x-auth-card>
</x-guest-layout>
