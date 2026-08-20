<section>
    <x-section-title title="Profile photo" subtitle="Shown across the app wherever your account appears." />

    <div class="flex items-center gap-4">
        <x-user-avatar :user="$user" class="flex size-16 shrink-0 text-xl" />

        <div class="flex flex-wrap items-center gap-3">
            <form method="post" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" x-data="{ fileName: null }">
                @csrf

                <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-border bg-card px-3 py-2 text-sm font-medium hover:bg-accent">
                    <x-icon name="camera" class="size-4" />
                    <span x-text="fileName ?? 'Upload photo'"></span>
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

            @if ($user->avatar_url)
                <form method="post" action="{{ route('profile.photo.destroy') }}">
                    @csrf
                    @method('delete')
                    <button type="submit" class="text-sm text-muted-foreground underline hover:text-foreground">Remove photo</button>
                </form>
            @endif
        </div>
    </div>

    <x-input-error :messages="$errors->get('photo')" class="mt-2" />

    @if (session('status') === 'photo-updated' || session('status') === 'photo-removed')
        <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="mt-2 text-sm text-muted-foreground">Saved.</p>
    @endif
</section>
