<x-guest-layout>
    <x-slot name="title">Complete Your Profile — Blood Connect JU</x-slot>

    @php $isStudent = auth()->user()->role === 'student'; @endphp

    <div x-data="{
        step: {{ $errors->hasAny(['hall', 'department']) ? 2 : ($errors->hasAny(['is_available', 'last_donation_date']) ? 3 : 1) }},
        total: 3,
        titles: ['Your blood group', 'Where you are on campus', 'Your availability'],
        subtitles: [
            'Donors are matched to requests by compatible blood group.',
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

                {{-- Step 2: hall / department --}}
                <div x-show="step === 2" class="space-y-4">
                    @if ($isStudent)
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
                    @endif

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
                </div>

                {{-- Step 3: availability --}}
                <div x-show="step === 3" class="space-y-4">
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
