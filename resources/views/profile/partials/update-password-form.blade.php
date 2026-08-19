<section>
    <x-section-title title="Update password" subtitle="Use a long, random password to stay secure." />

    @if (! $user->password)
        <p class="text-sm text-muted-foreground">
            You signed in with Google, so there's no password on this account to update.
        </p>
    @else
    <form method="post" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('put')

        <div class="space-y-1.5">
            <x-input-label for="update_password_current_password" value="Current password" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="space-y-1.5">
            <x-input-label for="update_password_password" value="New password" />
            <x-text-input id="update_password_password" name="password" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" />
        </div>

        <div class="space-y-1.5">
            <x-input-label for="update_password_password_confirmation" value="Confirm password" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <div class="flex items-center gap-4">
            <x-button type="submit">Save</x-button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-muted-foreground">Saved.</p>
            @endif
        </div>
    </form>
    @endif
</section>
