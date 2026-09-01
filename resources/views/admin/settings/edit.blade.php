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
            <x-section-title title="Logo" subtitle="Shown next to the org credit on the landing page and Settings." />

            <div class="flex items-center gap-4">
                @if ($setting->logo_url)
                    <img src="{{ $setting->logo_url }}" alt="Logo" class="size-16 shrink-0 rounded-lg border border-border object-contain">
                @else
                    <div class="flex size-16 shrink-0 items-center justify-center rounded-lg border border-dashed border-border text-xs text-muted-foreground">
                        None
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-3">
                    <form method="post" action="{{ route('admin.settings.logo.update') }}" enctype="multipart/form-data" x-data="{ fileName: null }">
                        @csrf

                        <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-border bg-card px-3 py-2 text-sm font-medium hover:bg-accent">
                            <x-icon name="camera" class="size-4" />
                            <span x-text="fileName ?? 'Upload logo'"></span>
                            <input
                                type="file"
                                name="photo"
                                accept="image/*"
                                class="hidden"
                                required
                                @change="fileName = $event.target.files[0]?.name; $event.target.closest('form').submit()"
                            >
                        </label>
                    </form>

                    @if ($setting->logo_url)
                        <form method="post" action="{{ route('admin.settings.logo.destroy') }}">
                            @csrf
                            @method('delete')
                            <button type="submit" class="text-sm text-muted-foreground underline hover:text-foreground">Remove logo</button>
                        </form>
                    @endif
                </div>
            </div>

            <x-input-error :messages="$errors->get('photo')" class="mt-2" />
        </div>

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
    </div>
</x-app-layout>
