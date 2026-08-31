<x-app-layout title="Add donor" subtitle="Register a donor recruited offline">
    <div class="mx-auto max-w-lg space-y-5" x-data="{ role: '{{ old('role', 'student') }}', hasWhatsapp: {{ old('phone_has_whatsapp', '1') === '1' ? 'true' : 'false' }} }">
        <div class="surface-panel p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.donors.store') }}" class="space-y-4">
                @csrf

                <div class="space-y-1.5">
                    <x-input-label for="name" value="Full name" />
                    <x-text-input id="name" type="text" name="name" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" />
                </div>

                <div class="space-y-1.5">
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" type="email" name="email" :value="old('email')" required />
                    <x-input-error :messages="$errors->get('email')" />
                </div>

                <div class="space-y-1.5">
                    <x-input-label value="I am a" />
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (['student' => 'Student', 'staff' => 'Staff', 'faculty' => 'Teacher'] as $value => $label)
                            <label class="flex cursor-pointer items-center justify-center rounded-lg border border-border px-2 py-3 text-sm has-[:checked]:border-primary has-[:checked]:bg-accent has-[:checked]:font-medium has-[:checked]:text-accent-foreground">
                                <input type="radio" name="role" value="{{ $value }}" x-model="role" class="sr-only" {{ old('role', 'student') === $value ? 'checked' : '' }}>
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
                                <input type="radio" name="gender" value="{{ $value }}" class="sr-only" {{ old('gender') === $value ? 'checked' : '' }}>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('gender')" />
                </div>

                <div class="space-y-1.5">
                    <x-input-label for="date_of_birth" value="Date of birth" />
                    <x-text-input id="date_of_birth" type="date" name="date_of_birth" :value="old('date_of_birth')" max="{{ now()->toDateString() }}" />
                    <x-input-error :messages="$errors->get('date_of_birth')" />
                </div>

                <div class="space-y-1.5">
                    <x-input-label for="blood_group" value="Blood group" />
                    <select id="blood_group" name="blood_group" class="block w-full rounded-lg border border-border bg-card px-3 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        @foreach ($bloodGroups as $group)
                            <option value="{{ $group }}" {{ old('blood_group') === $group ? 'selected' : '' }}>{{ $group }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('blood_group')" />
                </div>

                <div x-show="role === 'student'" class="space-y-4">
                    <div class="space-y-1.5">
                        <x-input-label for="hall" value="Hall" />
                        <select id="hall" name="hall" class="block w-full rounded-lg border border-border bg-card px-3 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                            <option value="">Select hall</option>
                            @foreach ($halls as $hall)
                                <option value="{{ $hall }}" {{ old('hall') === $hall ? 'selected' : '' }}>{{ $hall }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('hall')" />
                    </div>

                    <div class="space-y-1.5">
                        <x-input-label for="batch" value="Batch" />
                        <select id="batch" name="batch" class="block w-full rounded-lg border border-border bg-card px-3 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                            <option value="">Select batch</option>
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
                        <option value="">Select department</option>
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

                <label class="flex items-center justify-between gap-3 rounded-xl border border-border p-4">
                    <span>
                        <span class="block text-sm font-medium">Hide their number from other users</span>
                        <span class="text-xs text-muted-foreground">Only admins can see it. The donor can change this any time from their own profile.</span>
                    </span>
                    <input type="hidden" name="phone_visibility" value="public">
                    <input type="checkbox" name="phone_visibility" value="admin_only" {{ old('phone_visibility') === 'admin_only' ? 'checked' : '' }} class="size-5 shrink-0 rounded border-border text-primary focus:ring-primary">
                </label>

                <label class="flex items-center justify-between rounded-xl border border-border p-4">
                    <div>
                        <p class="text-sm font-medium">Available to donate</p>
                        <p class="text-xs text-muted-foreground">Show in donor search results</p>
                    </div>
                    <input type="hidden" name="is_available" value="0">
                    <input type="checkbox" name="is_available" value="1" {{ old('is_available', '1') === '1' ? 'checked' : '' }} class="size-5 rounded border-border text-primary focus:ring-primary">
                </label>

                <div class="space-y-1.5">
                    <x-input-label for="last_donation_date" value="Last donation date (optional)" />
                    <x-text-input id="last_donation_date" type="date" name="last_donation_date" :value="old('last_donation_date')" max="{{ now()->toDateString() }}" />
                    <x-input-error :messages="$errors->get('last_donation_date')" />
                </div>

                <p class="text-xs text-muted-foreground">
                    A random password is generated automatically. The donor sets their own via "Forgot password" the first time they want to log in.
                </p>

                <x-button type="submit">Add donor</x-button>
            </form>
        </div>
    </div>
</x-app-layout>
