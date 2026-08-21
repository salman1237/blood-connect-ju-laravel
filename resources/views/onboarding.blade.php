<x-guest-layout>
    <x-slot name="title">Complete Your Profile — Blood Connect JU</x-slot>

    <div x-data="{
        step: {{ $errors->hasAny(['role', 'gender', 'date_of_birth']) ? 2 : ($errors->hasAny(['hall', 'department', 'batch']) ? 3 : ($errors->hasAny(['is_available', 'last_donation_date']) ? 4 : 1)) }},
        total: 4,
        role: '{{ old('role', auth()->user()->role) }}',
        hasWhatsapp: {{ old('phone_has_whatsapp', '1') === '1' ? 'true' : 'false' }},
        titles: ['Your blood group', 'About you', 'Where you are on campus', 'Your availability'],
        subtitles: [
            'Donors are matched to requests by compatible blood group.',
            'Helps us route requests to the right people.',
            'We use this to alert you about nearby requests first.',
            'You can change this any time from your profile.',
        ],
    }">
        <x-auth-card>
            <x-slot name="step">
                <span x-text="`Step ${step} of ${total}`"></span>
            </x-slot>

            <h1 class="text-2xl font-semibold" x-text="titles[step - 1]"></h1>
            <p class="mt-1.5 text-sm text-muted-foreground" x-text="subtitles[step - 1]"></p>

            <div class="mt-6 flex gap-1.5">
                <template x-for="s in total" :key="s">
                    <span class="h-1.5 flex-1 rounded-full" :class="s <= step ? 'bg-primary' : 'bg-secondary'"></span>
                </template>
            </div>

            <form method="POST" action="{{ route('onboarding.store') }}" class="mt-4 space-y-4">
                @csrf

                {{-- Step 1: blood group --}}
                <div x-show="step === 1">
                    <div class="grid grid-cols-4 gap-2">
                        @foreach ($bloodGroups as $group)
                            <label class="flex cursor-pointer items-center justify-center rounded-lg border border-border py-3 text-sm font-medium has-[:checked]:border-primary has-[:checked]:bg-accent has-[:checked]:text-accent-foreground">
                                <input type="radio" name="blood_group" value="{{ $group }}" class="sr-only" {{ old('blood_group') === $group ? 'checked' : '' }} required>
                                {{ $group }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('blood_group')" class="mt-2" />
                </div>

                {{-- Step 2: role / gender --}}
                <div x-show="step === 2" class="space-y-4">
                    <div class="space-y-1.5">
                        <x-input-label value="I am a" />
                        <div class="grid grid-cols-3 gap-2">
                            @foreach (['student' => 'Student', 'staff' => 'Staff', 'faculty' => 'Teacher'] as $value => $label)
                                <label class="flex cursor-pointer items-center justify-center rounded-lg border border-border px-2 py-3 text-sm has-[:checked]:border-primary has-[:checked]:bg-accent has-[:checked]:font-medium has-[:checked]:text-accent-foreground">
                                    <input type="radio" name="role" value="{{ $value }}" x-model="role" class="sr-only" required>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('role')" />
                    </div>

                    <div class="space-y-1.5">
                        <x-input-label value="Gender" />
                        <div class="grid grid-cols-3 gap-2">
                            @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                                <label class="flex cursor-pointer items-center justify-center rounded-lg border border-border px-2 py-3 text-sm has-[:checked]:border-primary has-[:checked]:bg-accent has-[:checked]:font-medium has-[:checked]:text-accent-foreground">
                                    <input type="radio" name="gender" value="{{ $value }}" class="sr-only" {{ old('gender', auth()->user()->gender) === $value ? 'checked' : '' }} required>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('gender')" />
                    </div>

                    <div class="space-y-1.5">
                        <x-input-label for="date_of_birth" value="Date of birth" />
                        <x-text-input id="date_of_birth" type="date" name="date_of_birth" :value="old('date_of_birth', auth()->user()->date_of_birth?->toDateString())" max="{{ now()->toDateString() }}" required />
                        <x-input-error :messages="$errors->get('date_of_birth')" />
                    </div>
                </div>

                {{-- Step 3: hall / batch / department / phone --}}
                <div x-show="step === 3" class="space-y-4">
                    <div x-show="role === 'student'" class="space-y-4">
                        <div class="space-y-1.5">
                            <x-input-label for="hall" value="Hall" />
                            <select id="hall" name="hall" class="block w-full rounded-lg border border-border bg-card px-3 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                                <option value="">Select your hall</option>
                                @foreach ($halls as $hall)
                                    <option value="{{ $hall }}" {{ old('hall') === $hall ? 'selected' : '' }}>{{ $hall }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('hall')" />
                        </div>

                        <div class="space-y-1.5">
                            <x-input-label for="batch" value="Batch" />
                            <select id="batch" name="batch" class="block w-full rounded-lg border border-border bg-card px-3 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                                <option value="">Select your batch</option>
                                @foreach ($batches as $batch)
                                    <option value="{{ $batch }}" {{ old('batch') === $batch ? 'selected' : '' }}>{{ $batch }}</option>
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
                                        <option value="{{ $name }}" {{ old('department') === $name ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('department')" />
                    </div>

                    <div class="space-y-1.5">
                        <x-input-label for="phone" value="Phone (optional)" />
                        <x-text-input id="phone" type="tel" name="phone" placeholder="01XXXXXXXXX" :value="old('phone')" />
                        <x-input-error :messages="$errors->get('phone')" />
                    </div>

                    <label class="flex items-center justify-between rounded-xl border border-border p-4">
                        <span class="text-sm font-medium">This number has WhatsApp</span>
                        <input type="hidden" name="phone_has_whatsapp" value="0">
                        <input type="checkbox" name="phone_has_whatsapp" value="1" x-model="hasWhatsapp" class="size-5 rounded border-border text-primary focus:ring-primary">
                    </label>

                    <div class="space-y-1.5" x-show="!hasWhatsapp">
                        <x-input-label for="whatsapp_number" value="WhatsApp number (optional)" />
                        <x-text-input id="whatsapp_number" type="tel" name="whatsapp_number" placeholder="01XXXXXXXXX" :value="old('whatsapp_number')" />
                        <x-input-error :messages="$errors->get('whatsapp_number')" />
                    </div>
                </div>

                {{-- Step 4: availability --}}
                <div x-show="step === 4" class="space-y-4">
                    <label class="flex items-center justify-between rounded-xl border border-border p-4">
                        <div>
                            <p class="text-sm font-medium">Available to donate</p>
                            <p class="text-xs text-muted-foreground">Show me in donor search results</p>
                        </div>
                        <input type="hidden" name="is_available" value="0">
                        <input type="checkbox" name="is_available" value="1" checked class="size-5 rounded border-border text-primary focus:ring-primary">
                    </label>

                    <div class="space-y-1.5">
                        <x-input-label for="last_donation_date" value="Last donation date (optional)" />
                        <x-text-input id="last_donation_date" type="date" name="last_donation_date" :value="old('last_donation_date')" max="{{ now()->toDateString() }}" />
                        <x-input-error :messages="$errors->get('last_donation_date')" />
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <x-button type="button" x-show="step > 1" @click="step--" variant="outline" class="flex-1">Back</x-button>
                    <x-button type="button" x-show="step < total" @click="step++" size="lg" class="flex-1">Continue</x-button>
                    <x-button type="submit" x-show="step === total" size="lg" class="flex-1">Finish setup</x-button>
                </div>
            </form>
        </x-auth-card>
    </div>
</x-guest-layout>
