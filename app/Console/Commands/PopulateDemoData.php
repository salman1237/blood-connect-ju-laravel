<?php

namespace App\Console\Commands;

use App\Models\Badge;
use App\Models\BloodRequest;
use App\Models\DonationHistory;
use App\Models\DonorProfile;
use App\Models\RequestResponse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * One-off demo/client-showcase data generator — additive only, never
 * touches existing rows. Every account uses a non-deliverable @example.com
 * address (IANA-reserved for exactly this) so nothing can ever actually
 * send mail to a real inbox, and events are suppressed for the whole run
 * so it doesn't dispatch 500 real notification jobs.
 *
 * Deliberately dependency-free (no Faker) — this needs to run in
 * production, where `composer install --no-dev` never installs it.
 */
class PopulateDemoData extends Command
{
    protected $signature = 'demo:populate {--donors=30} {--requests=500} {--open-percent=15} {--fulfilled-percent=55}';

    protected $description = 'Generate realistic demo accounts and blood requests for a populated client-facing demo';

    private const FIRST_NAMES = [
        'Rakib', 'Sultana', 'Hasan', 'Nasrin', 'Mahin', 'Tania', 'Shakil', 'Ruma', 'Faisal', 'Momotaz',
        'Arif', 'Lima', 'Shanto', 'Poly', 'Nayeem', 'Shathi', 'Rasel', 'Mitu', 'Emon', 'Riya',
        'Sohel', 'Nodi', 'Anik', 'Tuli', 'Rafi', 'Jui', 'Sagor', 'Ishrat', 'Mizan', 'Purnima',
        'Zahid', 'Afsana', 'Rubel', 'Tahmina', 'Shuvo',
    ];

    private const LAST_NAMES = [
        'Chowdhury', 'Talukder', 'Sarker', 'Molla', 'Biswas', 'Khan', 'Sikder', 'Pramanik', 'Mondal', 'Bhuiyan',
    ];

    private const HOSPITALS = [
        ['name' => 'Enam Medical College Hospital', 'location' => 'Savar'],
        ['name' => 'Savar Upazila Health Complex', 'location' => 'Savar'],
        ['name' => 'Dhaka Medical College Hospital', 'location' => 'Dhaka'],
        ['name' => 'Kuwait Bangladesh Friendship Government Hospital', 'location' => 'Dhaka'],
        ['name' => 'Combined Military Hospital', 'location' => 'Savar'],
        ['name' => 'National Institute of Neurosciences & Hospital', 'location' => 'Dhaka'],
        ['name' => 'Square Hospital', 'location' => 'Dhaka'],
        ['name' => 'United Hospital', 'location' => 'Dhaka'],
        ['name' => 'Ibn Sina Hospital', 'location' => 'Dhaka'],
        ['name' => 'Labaid Hospital', 'location' => 'Dhaka'],
        ['name' => 'Popular Diagnostic Centre & Hospital', 'location' => 'Dhaka'],
        ['name' => 'BIRDEM General Hospital', 'location' => 'Dhaka'],
        ['name' => 'Evercare Hospital Dhaka', 'location' => 'Dhaka'],
        ['name' => 'National Heart Foundation Hospital', 'location' => 'Dhaka'],
        ['name' => 'Mugda Medical College Hospital', 'location' => 'Dhaka'],
        ['name' => 'Sir Salimullah Medical College Hospital', 'location' => 'Dhaka'],
    ];

    private const PATIENT_CONTEXTS = [
        'Road traffic accident, emergency surgery required.',
        'Scheduled thalassemia transfusion.',
        'Complicated delivery, postpartum hemorrhage.',
        'Dengue fever, platelet count critically low.',
        'Cancer treatment, chemotherapy-related anemia.',
        'Kidney dialysis patient, routine transfusion needed.',
        'Emergency appendectomy, pre-surgery blood reserve.',
        'Burn injury, ongoing treatment.',
        'Cardiac surgery scheduled this week.',
        'Severe anemia, doctor recommended transfusion.',
        'Accident victim, multiple fractures, surgery pending.',
        'Liver disease patient, platelet support needed.',
        'Premature birth, NICU transfusion required.',
        'Post-operative recovery, blood loss during surgery.',
        'Chronic illness, routine blood support.',
        null, null, // some requests just don't include this optional field
    ];

    private const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    private const URGENCIES = ['critical', 'within_24h', 'planned'];

    public function handle(): int
    {
        $donorCount = (int) $this->option('donors');
        $requestCount = (int) $this->option('requests');
        $openPercent = (int) $this->option('open-percent');
        $fulfilledPercent = (int) $this->option('fulfilled-percent');

        Model::unguard();

        [$donors, $verifiers] = Model::withoutEvents(function () use ($donorCount) {
            // --donors=0 tops up more requests against the existing demo
            // pool instead of creating a fresh batch of accounts.
            $donors = $donorCount > 0 ? $this->createDonors($donorCount) : $this->existingDemoDonors();

            return [$donors, $this->findOrCreateVerifiers()];
        });

        $this->info("Using {$donors->count()} demo accounts.");

        $badges = Badge::all()->keyBy('slug');

        $bar = $this->output->createProgressBar($requestCount);
        $bar->start();

        Model::withoutEvents(function () use ($requestCount, $donors, $verifiers, $badges, $bar, $openPercent, $fulfilledPercent) {
            for ($i = 0; $i < $requestCount; $i++) {
                $this->createOneRequest($donors, $verifiers, $badges, $openPercent, $fulfilledPercent);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Created {$requestCount} demo blood requests, all verified.");

        Model::reguard();

        return self::SUCCESS;
    }

    private function createDonors(int $count): Collection
    {
        $password = Hash::make('password');
        $halls = config('juniv.halls');
        $departments = collect(config('juniv.departments'))->flatten()->all();
        $batches = User::batchOptions();

        $donors = collect();

        for ($i = 0; $i < $count; $i++) {
            $firstName = $this->randomElement(self::FIRST_NAMES);
            $lastName = $this->randomElement(self::LAST_NAMES);
            $isStudent = $this->randomBool(60);
            $role = $isStudent ? 'student' : $this->randomElement(['staff', 'faculty']);

            $user = User::create([
                'name' => "{$firstName} {$lastName}",
                'email' => strtolower("{$firstName}.{$lastName}.demo{$i}@example.com"),
                'password' => $password,
                'role' => $role,
                'gender' => $this->randomElement(['male', 'female', 'other']),
                'hall' => $isStudent ? $this->randomElement($halls) : null,
                'department' => $this->randomElement($departments),
                'batch' => $isStudent ? $this->randomElement($batches) : null,
                'phone' => '01'.$this->randomDigits(9),
                'phone_has_whatsapp' => $this->randomBool(80),
                'email_verified_at' => now(),
                'is_active' => true,
                'email_notifications_enabled' => true,
            ]);

            $profile = DonorProfile::create([
                'user_id' => $user->id,
                'blood_group' => $this->randomElement(self::BLOOD_GROUPS),
                'is_available' => $this->randomBool(85),
                'last_donation_date' => $this->randomBool(40) ? $this->randomDateBetween('-2 years', '-130 days') : null,
                'trust_score' => 0,
            ]);

            $donors->push(['user' => $user, 'profile' => $profile]);
        }

        return $donors;
    }

    /** Used by --donors=0 top-up runs — reload the pool created by an earlier run. */
    private function existingDemoDonors(): Collection
    {
        return User::where('email', 'like', '%@example.com')
            ->where('email', 'not like', '%.verifier@example.com')
            ->with('donorProfile')
            ->get()
            ->filter(fn ($user) => $user->donorProfile !== null)
            ->map(fn ($user) => ['user' => $user, 'profile' => $user->donorProfile])
            ->values();
    }

    /** Reuses real verifiers if this has already been run once; creates two otherwise. */
    private function findOrCreateVerifiers(): Collection
    {
        $existing = User::where('role', 'verifier')->get();
        if ($existing->count() >= 2) {
            return $existing;
        }

        $password = Hash::make('password');

        return collect([
            ['name' => 'Farhana Islam', 'email' => 'farhana.demo.verifier@example.com', 'department' => 'Physics', 'gender' => 'female'],
            ['name' => 'Kamrul Hasan', 'email' => 'kamrul.demo.verifier@example.com', 'department' => 'Economics', 'gender' => 'male'],
        ])->map(fn ($data) => User::firstOrCreate(
            ['email' => $data['email']],
            [...$data, 'password' => $password, 'role' => 'verifier', 'email_verified_at' => now(), 'is_active' => true, 'email_notifications_enabled' => true]
        ));
    }

    private function createOneRequest(Collection $donors, Collection $verifiers, Collection $badges, int $openPercent, int $fulfilledPercent): void
    {
        $hospital = $this->randomElement(self::HOSPITALS);
        $bloodGroup = $this->randomElement(self::BLOOD_GROUPS);
        $requesterEntry = $donors->random();
        $requester = $requesterEntry['user'];

        $statusRoll = random_int(1, 100);
        $status = match (true) {
            $statusRoll <= $openPercent => 'open',
            $statusRoll <= $openPercent + $fulfilledPercent => 'fulfilled',
            default => 'expired',
        };

        $createdAt = $status === 'open'
            ? $this->randomDateBetween('-3 days', 'now')
            : $this->randomDateBetween('-60 days', '-4 days');

        $expiresAt = $createdAt->copy()->addHours(BloodRequest::EXPIRES_AFTER_HOURS);

        $request = BloodRequest::create([
            'requester_id' => $requester->id,
            'blood_group' => $bloodGroup,
            'units_needed' => random_int(1, 4),
            'hospital_name' => $hospital['name'],
            'location' => $hospital['location'],
            'urgency' => $this->randomElement(self::URGENCIES),
            'patient_context' => $this->randomElement(self::PATIENT_CONTEXTS),
            'contact_method' => '01'.$this->randomDigits(9),
            'status' => $status,
            'is_verified' => true,
            'verified_by' => $verifiers->random()->id,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            'expires_at' => $expiresAt,
        ]);

        if ($status !== 'fulfilled') {
            return;
        }

        $compatibleDonorGroups = BloodRequest::DONOR_COMPATIBILITY[$bloodGroup];
        $donorEntry = $donors->first(fn ($d) => $d['user']->id !== $requester->id && in_array($d['profile']->blood_group, $compatibleDonorGroups, true))
            ?? $donors->first(fn ($d) => $d['user']->id !== $requester->id);
        $donor = $donorEntry['user'];
        $donorProfile = $donorEntry['profile'];

        $confirmedAt = $createdAt->copy()->addHours(random_int(1, 48));

        RequestResponse::create([
            'request_id' => $request->id,
            'donor_id' => $donor->id,
            'status' => 'confirmed',
            'requester_confirmed_at' => $confirmedAt,
            'donor_confirmed_at' => $confirmedAt,
            'created_at' => $createdAt,
            'updated_at' => $confirmedAt,
        ]);

        DonationHistory::create([
            'donor_id' => $donor->id,
            'request_id' => $request->id,
            'confirmed_at' => $confirmedAt,
        ]);

        $donorProfile->increment('trust_score');

        $this->awardBadgesIfEarned($donor, $donorProfile, $badges);
    }

    private function awardBadgesIfEarned(User $donor, DonorProfile $profile, Collection $badges): void
    {
        $totalDonations = DonationHistory::where('donor_id', $donor->id)->count();

        if ($totalDonations === 1 && $badges->has('first-donation')) {
            $donor->badges()->syncWithoutDetaching([$badges['first-donation']->id => ['earned_at' => now()]]);
        }

        if ($totalDonations === 5 && $badges->has('five-time-donor')) {
            $donor->badges()->syncWithoutDetaching([$badges['five-time-donor']->id => ['earned_at' => now()]]);
        }

        if (in_array($profile->blood_group, DonorProfile::RARE_BLOOD_GROUPS, true) && $badges->has('rare-blood-type')) {
            $donor->badges()->syncWithoutDetaching([$badges['rare-blood-type']->id => ['earned_at' => now()]]);
        }
    }

    private function randomElement(array $items): mixed
    {
        return $items[array_rand($items)];
    }

    /** True with the given percent chance (0-100). */
    private function randomBool(int $percentChance): bool
    {
        return random_int(1, 100) <= $percentChance;
    }

    private function randomDigits(int $length): string
    {
        $digits = '';
        for ($i = 0; $i < $length; $i++) {
            $digits .= random_int(0, 9);
        }

        return $digits;
    }

    private function randomDateBetween(string $start, string $end): Carbon
    {
        $startTimestamp = Carbon::parse($start)->timestamp;
        $endTimestamp = Carbon::parse($end)->timestamp;

        return Carbon::createFromTimestamp(random_int($startTimestamp, $endTimestamp));
    }
}
