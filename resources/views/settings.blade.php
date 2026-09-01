<x-app-layout title="{{ __('nav.settings') }}" subtitle="Language, notifications, and account">
    @if (session('status') === 'notifications-updated')
        <x-auth-session-status status="Preferences saved." class="mb-5" />
    @endif

    <div class="mx-auto max-w-lg space-y-5">
        <div class="surface-panel p-5 sm:p-6">
            <x-section-title :title="__('common.language')" subtitle="Applies across the whole app." />
            <x-language-toggle />
        </div>

        <div class="surface-panel p-5 sm:p-6">
            <x-section-title title="Notifications" subtitle="Control what gets emailed to you." />
            <form method="POST" action="{{ route('settings.notifications.update') }}">
                @csrf
                @method('PATCH')
                <label class="flex items-center justify-between rounded-xl border border-border p-4">
                    <div>
                        <p class="text-sm font-medium">{{ __('common.email_notifications') }}</p>
                        <p class="text-xs text-muted-foreground">Matching requests, responses, and account activity. In-app alerts always stay on.</p>
                    </div>
                    <input type="hidden" name="email_notifications_enabled" value="0">
                    <input type="checkbox" name="email_notifications_enabled" value="1" onchange="this.form.requestSubmit()"
                           {{ auth()->user()->email_notifications_enabled ? 'checked' : '' }}
                           class="size-5 rounded border-border text-primary focus:ring-primary">
                </label>
            </form>
        </div>

        <div class="surface-panel p-5 sm:p-6">
            <x-section-title title="Account" />
            <div class="flex flex-col gap-2 sm:flex-row">
                <x-button :href="route('profile.edit')" variant="outline" class="flex-1">Edit profile</x-button>
                <form method="POST" action="{{ route('logout') }}" class="flex-1">
                    @csrf
                    <x-button type="submit" variant="outline" class="w-full">{{ __('nav.sign_out') }}</x-button>
                </form>
            </div>
        </div>

        <div class="surface-panel p-5 sm:p-6">
            <x-section-title title="About" />
            @include('partials.org-credit', ['setting' => $orgSetting])
        </div>
    </div>
</x-app-layout>
