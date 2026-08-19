@php
    $profile = $user->donorProfile;
    $isStudent = $user->role === 'student';
@endphp

<section>
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

        @if ($isStudent)
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
        @endif

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
