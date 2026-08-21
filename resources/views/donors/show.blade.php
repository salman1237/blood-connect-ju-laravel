<x-app-layout title="{{ $donor->name }}" subtitle="Donor profile">
    @php
        $roleLabels = ['student' => 'Student', 'staff' => 'Staff', 'faculty' => 'Teacher', 'verifier' => 'Verifier', 'admin' => 'Admin'];
    @endphp
    <div class="mx-auto max-w-2xl space-y-5">
        <div class="surface-panel p-5 sm:p-6">
            <div class="flex items-center gap-4">
                <x-user-avatar :user="$donor" class="flex size-14 text-lg" />
                <div class="min-w-0 flex-1">
                    <h2 class="truncate text-lg font-semibold">{{ $donor->name }}</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ $donor->hall ?? $donor->department ?? 'Campus' }}
                    </p>
                </div>
                <x-blood-drop :group="$donor->donorProfile->blood_group" size="lg" />
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-border pt-4">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $donor->donorProfile->is_available ? 'bg-success/15 text-success' : 'bg-muted text-muted-foreground' }}">
                    {{ $donor->donorProfile->is_available ? 'Available' : 'Unavailable' }}
                </span>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $donor->donorProfile->is_eligible ? 'bg-success/15 text-success' : 'bg-warning/20 text-warning-foreground' }}">
                    {{ $donor->donorProfile->is_eligible ? 'Eligible to donate' : 'Eligible '.$donor->donorProfile->next_eligible_date->diffForHumans() }}
                </span>
                <span class="text-xs text-muted-foreground">Trust score: {{ $donor->donorProfile->trust_score }}</span>
            </div>

            @if ($donor->whatsapp_url)
                <a href="{{ $donor->whatsapp_url }}" target="_blank" rel="noopener"
                   class="mt-4 flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-[#25D366] text-sm font-medium text-white transition hover:opacity-90">
                    <x-whatsapp-icon class="size-4" />
                    Message on WhatsApp
                </a>
            @endif
        </div>

        <div class="surface-panel p-5 sm:p-6">
            <h3 class="text-sm font-semibold">Details</h3>
            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-4 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-xs text-muted-foreground">Gender</dt>
                    <dd class="font-medium">{{ $donor->gender ? ucfirst($donor->gender) : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Role</dt>
                    <dd class="font-medium">{{ $roleLabels[$donor->role] ?? ucfirst($donor->role) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Age</dt>
                    <dd class="font-medium">{{ $donor->age !== null ? $donor->age.' years' : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Date of birth</dt>
                    <dd class="font-medium">{{ $donor->date_of_birth?->format('M j, Y') ?? '—' }}</dd>
                </div>
                @if ($donor->role === 'student')
                    <div>
                        <dt class="text-xs text-muted-foreground">Hall</dt>
                        <dd class="font-medium">{{ $donor->hall ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">Batch</dt>
                        <dd class="font-medium">{{ $donor->batch ?? '—' }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs text-muted-foreground">Department</dt>
                    <dd class="font-medium">{{ $donor->department ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">Phone</dt>
                    <dd class="font-medium">{{ $donor->phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground">WhatsApp</dt>
                    <dd class="font-medium">
                        @if ($donor->whatsapp_url)
                            <a href="{{ $donor->whatsapp_url }}" target="_blank" rel="noopener" class="text-primary underline">
                                {{ $donor->phone_has_whatsapp ? $donor->phone : $donor->whatsapp_number }}
                            </a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        @if ($donor->badges->isNotEmpty())
            <div class="surface-panel p-5 sm:p-6">
                <h3 class="text-sm font-semibold">Badges</h3>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($donor->badges as $badge)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-accent px-3 py-1 text-xs font-medium text-accent-foreground" title="{{ $badge->description }}">
                            <x-icon name="award" class="size-3.5" /> {{ $badge->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="surface-panel p-5 sm:p-6">
            <h3 class="text-sm font-semibold">Donation history ({{ $donationHistory->count() }})</h3>
            @if ($donationHistory->isEmpty())
                <p class="mt-2 text-sm text-muted-foreground">No confirmed donations yet.</p>
            @else
                <ul class="mt-3 space-y-2">
                    @foreach ($donationHistory as $entry)
                        <li class="flex items-center justify-between gap-3 rounded-xl border border-border p-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ $entry->bloodRequest?->hospital_name ?? 'Off-platform donation' }}</p>
                                <p class="text-xs text-muted-foreground">{{ $entry->confirmed_at->format('M j, Y') }}</p>
                            </div>
                            <x-icon name="heart-handshake" class="size-4 shrink-0 text-primary" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>
