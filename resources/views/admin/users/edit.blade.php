@php
    $isSelf = $user->id === auth()->id();
@endphp

<x-app-layout title="Edit user" :subtitle="$user->name">
    <div class="mx-auto max-w-lg space-y-5">
        @if ($isSelf)
            <div class="rounded-lg bg-warning/[12%] p-3 text-sm font-medium text-warning-foreground">
                This is your own account — you can't deactivate it or change your role away from admin.
            </div>
        @endif

        <div class="surface-panel p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="space-y-1.5">
                    <x-input-label for="role" value="Role" />
                    <select id="role" name="role" class="block w-full rounded-lg border border-border bg-card px-3 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        @foreach (['student', 'staff', 'faculty', 'verifier', 'admin'] as $role)
                            <option value="{{ $role }}" {{ old('role', $user->role) === $role ? 'selected' : '' }} class="capitalize">{{ ucfirst($role) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" />
                </div>

                <label class="flex items-center justify-between rounded-xl border border-border p-4">
                    <div>
                        <p class="text-sm font-medium">Active</p>
                        <p class="text-xs text-muted-foreground">Deactivated users can't log in.</p>
                    </div>
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="size-5 rounded border-border text-primary focus:ring-primary">
                </label>
                <x-input-error :messages="$errors->get('is_active')" />

                <x-button type="submit">Save changes</x-button>
            </form>
        </div>

        <div class="surface-panel p-5 sm:p-6">
            <h3 class="text-sm font-semibold">Account details</h3>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-muted-foreground">Email</dt><dd>{{ $user->email }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted-foreground">Joined</dt><dd>{{ $user->created_at->format('M j, Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted-foreground">Email verified</dt><dd>{{ $user->email_verified_at ? 'Yes' : 'No' }}</dd></div>
            </dl>
        </div>
    </div>
</x-app-layout>
