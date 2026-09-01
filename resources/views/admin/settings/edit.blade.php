<x-app-layout title="Site settings" subtitle="Org credit shown on the landing page and Settings">
    @if (session('status') === 'settings-updated')
        <x-auth-session-status status="Saved." class="mb-5" />
    @elseif (session('status') === 'logo-updated')
        <x-auth-session-status status="Logo updated." class="mb-5" />
    @elseif (session('status') === 'logo-removed')
        <x-auth-session-status status="Logo removed." class="mb-5" />
    @endif

    <div class="mx-auto max-w-lg space-y-5">
        @include('admin.settings.partials.credit-line', [
            'which' => 'funded_by',
            'fieldLabel' => 'Implemented & funded by',
            'value' => $setting->funded_by,
            'logoUrl' => $setting->funded_by_logo_url,
        ])

        @include('admin.settings.partials.credit-line', [
            'which' => 'maintained_by',
            'fieldLabel' => 'Maintained by',
            'value' => $setting->maintained_by,
            'logoUrl' => $setting->maintained_by_logo_url,
        ])
    </div>
</x-app-layout>
