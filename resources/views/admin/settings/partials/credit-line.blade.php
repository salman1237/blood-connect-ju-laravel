{{-- $which: 'funded_by' or 'maintained_by' -- $fieldLabel: the field's own label -- $value/$logoUrl: current values. --}}
<div class="surface-panel p-5 sm:p-6">
    <x-section-title :title="$fieldLabel" />

    <form method="POST" action="{{ route('admin.settings.update', $which) }}" class="space-y-1.5">
        @csrf
        @method('PATCH')
        <x-text-input type="text" name="value" :value="old('value', $value)" />
        <p class="text-xs text-muted-foreground">Leave blank to hide this line entirely.</p>
        <x-input-error :messages="$errors->get('value')" />
        <x-button type="submit" size="sm" class="mt-2">Save</x-button>
    </form>

    <div class="mt-4 border-t border-border pt-4">
        @include('admin.settings.partials.logo-upload', ['which' => $which, 'logoUrl' => $logoUrl])
    </div>
</div>
