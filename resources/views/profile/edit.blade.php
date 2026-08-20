<x-app-layout title="Profile" subtitle="Account & donor settings">
    <div class="space-y-6">
        <div class="surface-panel p-5 sm:p-6">
            @include('profile.partials.update-photo-form')
        </div>

        <div class="surface-panel p-5 sm:p-6">
            @include('profile.partials.donor-profile-form')
        </div>

        <div class="surface-panel p-5 sm:p-6">
            @include('profile.partials.donation-history')
        </div>

        <div class="surface-panel p-5 sm:p-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="surface-panel p-5 sm:p-6">
            @include('profile.partials.update-password-form')
        </div>

        <div class="surface-panel p-5 sm:p-6">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
