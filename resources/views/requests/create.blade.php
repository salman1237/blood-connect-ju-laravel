<x-app-layout title="Post a request" subtitle="Verified before it's broadcast campus-wide">
    <div class="mx-auto max-w-xl">
        <div class="surface-panel p-5 sm:p-6">
            <form method="POST" action="{{ route('requests.store') }}" class="space-y-4">
                @csrf

                <div class="space-y-1.5">
                    <x-input-label value="Blood group needed" />
                    <div class="grid grid-cols-4 gap-2">
                        @foreach ($bloodGroups as $group)
                            <label class="flex cursor-pointer items-center justify-center rounded-lg border border-border py-3 text-sm font-medium has-[:checked]:border-primary has-[:checked]:bg-accent has-[:checked]:text-accent-foreground">
                                <input type="radio" name="blood_group" value="{{ $group }}" class="sr-only" {{ old('blood_group') === $group ? 'checked' : '' }} required>
                                {{ $group }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('blood_group')" />
                </div>

                <div class="space-y-1.5">
                    <x-input-label value="Urgency" />
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (['critical' => 'Critical', 'within_24h' => 'Within 24h', 'planned' => 'Planned'] as $value => $label)
                            <label class="flex cursor-pointer items-center justify-center rounded-lg border border-border px-2 py-2.5 text-sm has-[:checked]:border-primary has-[:checked]:bg-accent has-[:checked]:font-medium has-[:checked]:text-accent-foreground">
                                <input type="radio" name="urgency" value="{{ $value }}" class="sr-only" {{ old('urgency', 'critical') === $value ? 'checked' : '' }} required>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('urgency')" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <x-input-label for="units_needed" value="Units needed" />
                        <x-text-input id="units_needed" type="number" name="units_needed" min="1" max="20" :value="old('units_needed', 1)" required />
                        <x-input-error :messages="$errors->get('units_needed')" />
                    </div>
                    <div class="space-y-1.5">
                        <x-input-label for="contact_method" value="Contact method" />
                        <x-text-input id="contact_method" type="text" name="contact_method" placeholder="01XXXXXXXXX" :value="old('contact_method')" required />
                        <x-input-error :messages="$errors->get('contact_method')" />
                    </div>
                </div>

                <div class="space-y-1.5">
                    <x-input-label for="hospital_name" value="Hospital" />
                    <x-text-input id="hospital_name" type="text" name="hospital_name" placeholder="e.g. Enam Medical College Hospital" :value="old('hospital_name')" required />
                    <x-input-error :messages="$errors->get('hospital_name')" />
                </div>

                <div class="space-y-1.5">
                    <x-input-label for="location" value="Location (optional)" />
                    <x-text-input id="location" type="text" name="location" placeholder="e.g. Savar" :value="old('location')" />
                    <x-input-error :messages="$errors->get('location')" />
                </div>

                <div class="space-y-1.5">
                    <x-input-label for="patient_context" value="Patient context (optional)" />
                    <textarea id="patient_context" name="patient_context" rows="3" class="block w-full rounded-lg border border-border bg-card px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ old('patient_context') }}</textarea>
                    <x-input-error :messages="$errors->get('patient_context')" />
                </div>

                <x-button type="submit" size="lg" class="w-full">Post request</x-button>
            </form>
        </div>
    </div>
</x-app-layout>
