@php
    $profile = $user->donorProfile;
@endphp

<section x-data="{ role: '{{ old('role', $user->role) }}', hasWhatsapp: {{ old('phone_has_whatsapp', $user->phone_has_whatsapp ? '1' : '0') === '1' ? 'true' : 'false' }} }">
    <x-section-title title="Donor profile" subtitle="Blood group, hall/department, and availability." />

    @if ($profile)
        <div class="mb-5 flex flex-wrap items-center gap-3">
            <x-blood-drop :group="$profile->blood_group" size="sm" />
            @if ($profile->is_eligible)
                <span class="inline-flex items-center gap-1 rounded-full bg-success/15 px-2.5 py-0.5 text-[11px] font-medium text-success">
                    Eligible to donate
                </span>
            @else
                <span class="inline-flex items-center gap-1 rounded-full bg-warning/20 px-2.5 py-0.5 text-[11px] font-medium text-warning-foreground">
                    Eligible again {{ $profile->next_eligible_date->diffForHumans() }}
                </span>
            @endif
            <span class="text-xs text-muted-foreground">Trust score: {{ $profile->trust_score }}</span>
        </div>
    @endif

    @if ($user->whatsapp_url)
        <a href="{{ $user->whatsapp_url }}" target="_blank" rel="noopener"
           class="mb-5 flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-[#25D366] text-sm font-medium text-white transition hover:opacity-90">
            <x-whatsapp-icon class="size-4" />
            Open my WhatsApp link
        </a>
    @endif

    <form method="post" action="{{ route('profile.donor.update') }}" class="space-y-4">
        @csrf
        @method('patch')

        <div class="space-y-1.5">
            <x-input-label for="blood_group" value="Blood group" />
            <select id="blood_group" name="blood_group" class="block w-full rounded-lg border border-border bg-card px-3 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                @foreach ($bloodGroups as $group)
                    <option value="{{ $group }}" {{ old('blood_group', $profile?->blood_group) === $group ? 'selected' : '' }}>{{ $group }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('blood_group')" />
        </div>

        @if ($user->canSelfServiceRole())
            <div class="space-y-1.5">
                <x-input-label value="I am a" />
                <div class="grid grid-cols-3 gap-2">
                    @foreach (['student' => 'Student', 'staff' => 'Staff', 'faculty' => 'Teacher'] as $value => $label)
                        <label class="flex cursor-pointer items-center justify-center rounded-lg border border-border px-2 py-3 text-sm has-[:checked]:border-primary has-[:checked]:bg-accent has-[:checked]:font-medium has-[:checked]:text-accent-foreground">
                            <input type="radio" name="role" value="{{ $value }}" x-model="role" class="sr-only">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('role')" />
            </div>
        @endif

        <div class="space-y-1.5">
            <x-input-label value="Gender" />
            <div class="grid grid-cols-3 gap-2">
                @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                    <label class="flex cursor-pointer items-center justify-center rounded-lg border border-border px-2 py-3 text-sm has-[:checked]:border-primary has-[:checked]:bg-accent has-[:checked]:font-medium has-[:checked]:text-accent-foreground">
                        <input type="radio" name="gender" value="{{ $value }}" class="sr-only" {{ old('gender', $user->gender) === $value ? 'checked' : '' }}>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('gender')" />
        </div>

        <div class="space-y-1.5">
            <x-input-label for="date_of_birth" value="Date of birth" />
            <x-text-input id="date_of_birth" type="date" name="date_of_birth" :value="old('date_of_birth', $user->date_of_birth?->toDateString())" max="{{ now()->toDateString() }}" />
            <x-input-error :messages="$errors->get('date_of_birth')" />
        </div>

        <div x-show="role === 'student'" class="space-y-4">
            <div class="space-y-1.5">
                <x-input-label for="hall" value="Hall" />
                <select id="hall" name="hall" class="block w-full rounded-lg border border-border bg-card px-3 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                    <option value="">Select your hall</option>
                    @foreach ($halls as $hall)
                        <option value="{{ $hall }}" {{ old('hall', $user->hall) === $hall ? 'selected' : '' }}>{{ $hall }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('hall')" />
            </div>

            <div class="space-y-1.5">
                <x-input-label for="batch" value="Batch" />
                <select id="batch" name="batch" class="block w-full rounded-lg border border-border bg-card px-3 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                    <option value="">Select your batch</option>
                    @foreach ($batches as $batch)
                        <option value="{{ $batch }}" {{ old('batch', $user->batch) === $batch ? 'selected' : '' }}>{{ $batch }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('batch')" />
            </div>
        </div>

        <div class="space-y-1.5">
            <x-input-label for="department" value="Department" />
            <select id="department" name="department" class="block w-full rounded-lg border border-border bg-card px-3 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                <option value="">Select your department</option>
                @foreach ($departments as $faculty => $names)
                    <optgroup label="{{ $faculty }}">
                        @foreach ($names as $name)
                            <option value="{{ $name }}" {{ old('department', $user->department) === $name ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('department')" />
        </div>

        <div class="space-y-1.5">
            <x-input-label for="phone" value="Phone" />
            <x-text-input id="phone" type="tel" name="phone" placeholder="01XXXXXXXXX" :value="old('phone', $user->phone)" />
            <x-input-error :messages="$errors->get('phone')" />
        </div>

        <label class="flex items-center justify-between rounded-xl border border-border p-4">
            <span class="text-sm font-medium">This number has WhatsApp</span>
            <input type="hidden" name="phone_has_whatsapp" value="0">
            <input type="checkbox" name="phone_has_whatsapp" value="1" x-model="hasWhatsapp" class="size-5 rounded border-border text-primary focus:ring-primary">
        </label>

        <div class="space-y-1.5" x-show="!hasWhatsapp">
            <x-input-label for="whatsapp_number" value="WhatsApp number (optional)" />
            <x-text-input id="whatsapp_number" type="tel" name="whatsapp_number" placeholder="01XXXXXXXXX" :value="old('whatsapp_number', $user->whatsapp_number)" />
            <x-input-error :messages="$errors->get('whatsapp_number')" />
        </div>

        <label class="flex items-center justify-between rounded-xl border border-border p-4">
            <div>
                <p class="text-sm font-medium">Available to donate</p>
                <p class="text-xs text-muted-foreground">Show me in donor search results</p>
            </div>
            <input type="hidden" name="is_available" value="0">
            <input type="checkbox" name="is_available" value="1" {{ old('is_available', $profile?->is_available ?? true) ? 'checked' : '' }} class="size-5 rounded border-border text-primary focus:ring-primary">
        </label>

        <div class="space-y-1.5">
            <x-input-label for="last_donation_date" value="Last donation date" />
            <x-text-input id="last_donation_date" type="date" name="last_donation_date" :value="old('last_donation_date', $profile?->last_donation_date?->toDateString())" max="{{ now()->toDateString() }}" />
            <x-input-error :messages="$errors->get('last_donation_date')" />
        </div>

        <div class="flex items-center gap-4">
            <x-button type="submit">Save donor profile</x-button>

            @if (session('status') === 'donor-profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-muted-foreground">Saved.</p>
            @endif
        </div>
    </form>
</section>
