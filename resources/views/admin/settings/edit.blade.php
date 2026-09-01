<x-app-layout title="Site settings" subtitle="Org credit shown on the landing page and Settings">
    @if (session('status') === 'settings-updated')
        <x-auth-session-status status="Settings saved." class="mb-5" />
    @elseif (session('status') === 'logo-updated')
        <x-auth-session-status status="Logo updated." class="mb-5" />
    @elseif (session('status') === 'logo-removed')
        <x-auth-session-status status="Logo removed." class="mb-5" />
    @endif

    <div class="mx-auto max-w-lg space-y-5">
        <div class="surface-panel p-5 sm:p-6">
            <x-section-title title="Org credit" subtitle="Displayed publicly on the landing page and to signed-in users on Settings." />

            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div class="space-y-1.5">
                    <x-input-label for="funded_by" value="Implemented & funded by" />
                    <x-text-input id="funded_by" type="text" name="funded_by" :value="old('funded_by', $setting->funded_by)" />
                    <p class="text-xs text-muted-foreground">Leave blank to hide this line entirely.</p>
                    <x-input-error :messages="$errors->get('funded_by')" />
                </div>

                <div class="space-y-1.5">
                    <x-input-label for="maintained_by" value="Maintained by" />
                    <x-text-input id="maintained_by" type="text" name="maintained_by" :value="old('maintained_by', $setting->maintained_by)" />
                    <p class="text-xs text-muted-foreground">Leave blank to hide this line entirely.</p>
                    <x-input-error :messages="$errors->get('maintained_by')" />
                </div>

                <x-button type="submit">Save</x-button>
            </form>
        </div>

        {{-- Two independent forms, one per credit line -- kept out of the
             text-fields form above since nested <form> elements aren't
             valid HTML. --}}
        <div class="surface-panel space-y-5 p-5 sm:p-6">
            <x-section-title title="Logos" subtitle="One per credit line, shown next to its text." />

            @include('admin.settings.partials.logo-upload', ['which' => 'funded_by', 'label' => 'JUCSU logo', 'logoUrl' => $setting->funded_by_logo_url])

            <div class="border-t border-border pt-5">
                @include('admin.settings.partials.logo-upload', ['which' => 'maintained_by', 'label' => 'Badhan logo', 'logoUrl' => $setting->maintained_by_logo_url])
            </div>
        </div>
    </div>
</x-app-layout>
