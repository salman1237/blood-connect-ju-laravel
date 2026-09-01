{{-- $which: 'funded_by' or 'maintained_by' -- $label: display name -- $logoUrl: current logo, if any. --}}
<div>
    <p class="mb-2 text-sm font-medium">{{ $label }}</p>

    <div class="flex items-center gap-4">
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $label }}" class="size-16 shrink-0 rounded-lg border border-border object-contain">
        @else
            <div class="flex size-16 shrink-0 items-center justify-center rounded-lg border border-dashed border-border text-xs text-muted-foreground">
                None
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <form method="post" action="{{ route('admin.settings.logo.update', $which) }}" enctype="multipart/form-data" x-data="{ fileName: null }">
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

            @if ($logoUrl)
                <form method="post" action="{{ route('admin.settings.logo.destroy', $which) }}">
                    @csrf
                    @method('delete')
                    <button type="submit" class="text-sm text-muted-foreground underline hover:text-foreground">Remove</button>
                </form>
            @endif
        </div>
    </div>

    <x-input-error :messages="$errors->get('photo')" class="mt-2" />
</div>
