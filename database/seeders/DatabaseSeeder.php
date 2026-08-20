<?php

namespace Database\Seeders;

use App\Models\Badge;
use App\Models\BloodRequest;
use App\Models\DonationHistory;
use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo/dev data only — every seeded account uses the password "password".
 * WithoutModelEvents is on deliberately: creating sample blood_requests
 * shouldn't dispatch real matching-donor notification jobs, and
 * donation_history rows are paired with donor_badges explicitly below
 * rather than relying on DonationHistoryObserver to award them.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $password = Hash::make('password');

        // forceCreate (not create) throughout this seeder because
        // email_verified_at is deliberately excluded from $fillable — a
        // plain create() here would silently drop it, leaving seeded
        // accounts unable to pass the 'verified' middleware on a real login.
        $admin = User::forceCreate([
            'name' => 'Admin User', 'email' => 'admin@juniv.edu', 'password' => $password,
            'role' => 'admin', 'department' => 'Computer Science and Engineering', 'gender' => 'male',
            'email_verified_at' => now(), 'is_active' => true, 'email_notifications_enabled' => true,
        ]);

        $verifiers = collect([
            ['name' => 'Farhana Islam', 'email' => 'farhana.verifier@juniv.edu', 'department' => 'Physics', 'gender' => 'female'],
            ['name' => 'Kamrul Hasan', 'email' => 'kamrul.verifier@juniv.edu', 'department' => 'Economics', 'gender' => 'male'],
        ])->map(fn ($data) => User::forceCreate([
            ...$data, 'password' => $password, 'role' => 'verifier',
            'email_verified_at' => now(), 'is_active' => true, 'email_notifications_enabled' => true,
        ]));

        // Spread across roles, blood groups, and halls/departments so the
        // dashboard, donor search, and leaderboard all look populated.
        $donors = collect([
            ['name' => 'Rahim Uddin', 'email' => 'rahim.uddin@juniv.edu', 'role' => 'student', 'gender' => 'male', 'hall' => 'Al Beruni Hall', 'department' => 'Computer Science and Engineering', 'batch' => '2018-19', 'blood_group' => 'O-'],
            ['name' => 'Karim Ahmed', 'email' => 'karim.ahmed@juniv.edu', 'role' => 'student', 'gender' => 'male', 'hall' => 'Al Beruni Hall', 'department' => 'Mathematics', 'batch' => '2019-20', 'blood_group' => 'A+'],
            ['name' => 'Nusrat Jahan', 'email' => 'nusrat.jahan@juniv.edu', 'role' => 'student', 'gender' => 'female', 'hall' => 'Rokeya Hall', 'department' => 'English', 'batch' => '2020-21', 'blood_group' => 'B+'],
            ['name' => 'Fatema Khatun', 'email' => 'fatema.khatun@juniv.edu', 'role' => 'student', 'gender' => 'female', 'hall' => 'Rokeya Hall', 'department' => 'Economics', 'batch' => '2017-18', 'blood_group' => 'AB+'],
            ['name' => 'Tanvir Hossain', 'email' => 'tanvir.hossain@juniv.edu', 'role' => 'student', 'gender' => 'male', 'hall' => 'Mir Mosharraf Hossain Hall', 'department' => 'Physics', 'batch' => '2021-22', 'blood_group' => 'O+'],
            ['name' => 'Sabbir Ahmed', 'email' => 'sabbir.ahmed@juniv.edu', 'role' => 'student', 'gender' => 'male', 'hall' => 'Mir Mosharraf Hossain Hall', 'department' => 'Statistics and Data Science', 'batch' => '2016-17', 'blood_group' => 'A-'],
            ['name' => 'Ismat Ara', 'email' => 'ismat.ara@juniv.edu', 'role' => 'student', 'gender' => 'female', 'hall' => 'Fazilatunnesa Hall', 'department' => 'Bangla', 'batch' => '2019-20', 'blood_group' => 'B-'],
            ['name' => 'Shirin Akter', 'email' => 'shirin.akter@juniv.edu', 'role' => 'student', 'gender' => 'female', 'hall' => 'Fazilatunnesa Hall', 'department' => 'Anthropology', 'batch' => '2020-21', 'blood_group' => 'AB-'],
            ['name' => 'Jahid Hasan', 'email' => 'jahid.hasan@juniv.edu', 'role' => 'student', 'gender' => 'male', 'hall' => 'Bir Protik Taramon Bibi Hall', 'department' => 'History', 'batch' => '2018-19', 'blood_group' => 'O-'],
            ['name' => 'Mahmudul Alam', 'email' => 'mahmudul.alam@juniv.edu', 'role' => 'staff', 'gender' => 'male', 'department' => 'Computer Science and Engineering', 'blood_group' => 'A+'],
            ['name' => 'Rezaul Karim', 'email' => 'rezaul.karim@juniv.edu', 'role' => 'staff', 'gender' => 'male', 'department' => 'Physics', 'blood_group' => 'B+'],
            ['name' => 'Dilruba Yasmin', 'email' => 'dilruba.yasmin@juniv.edu', 'role' => 'faculty', 'gender' => 'female', 'department' => 'Economics', 'blood_group' => 'O+'],
            ['name' => 'Anisur Rahman', 'email' => 'anisur.rahman@juniv.edu', 'role' => 'faculty', 'gender' => 'male', 'department' => 'Mathematics', 'blood_group' => 'AB+'],
            ['name' => 'Shamima Nasrin', 'email' => 'shamima.nasrin@juniv.edu', 'role' => 'student', 'gender' => 'female', 'hall' => 'Jahanara Imam Hall', 'department' => 'Geography and Environment', 'batch' => '2015-16', 'blood_group' => 'A-', 'available' => false],
            ['name' => 'Omar Faruk', 'email' => 'omar.faruk@juniv.edu', 'role' => 'student', 'gender' => 'male', 'hall' => 'Shaheed Salam-Barkat Hall', 'department' => 'Chemistry', 'batch' => '2022-23', 'blood_group' => 'B-', 'available' => false],
        ])->map(function ($data) use ($password) {
            $user = User::forceCreate([
                'name' => $data['name'], 'email' => $data['email'], 'password' => $password,
                'role' => $data['role'], 'gender' => $data['gender'], 'hall' => $data['hall'] ?? null,
                'department' => $data['department'] ?? null, 'batch' => $data['batch'] ?? null,
                'email_verified_at' => now(), 'is_active' => true, 'email_notifications_enabled' => true,
            ]);

            $profile = DonorProfile::create([
                'user_id' => $user->id,
                'blood_group' => $data['blood_group'],
                'is_available' => $data['available'] ?? true,
                'last_donation_date' => null,
                'trust_score' => 0,
            ]);

            return ['user' => $user, 'profile' => $profile];
        });

        $requester = $donors->first()['user'];
        $secondRequester = $donors[3]['user'];
        $thirdRequester = $donors[9]['user'];

        // Exact sample requests from the brief, matching the landing page's
        // static preview so the live dashboard echoes what visitors already saw.
        BloodRequest::create([
            'requester_id' => $requester->id, 'blood_group' => 'O-', 'units_needed' => 2,
            'hospital_name' => 'Enam Medical College Hospital', 'location' => 'Savar', 'urgency' => 'critical',
            'patient_context' => 'Road traffic accident, surgery scheduled tonight.',
            'contact_method' => '01711000001', 'status' => 'open',
            'is_verified' => true, 'verified_by' => $verifiers->first()->id,
            'expires_at' => now()->addHours(BloodRequest::EXPIRES_AFTER_HOURS),
        ]);

        BloodRequest::create([
            'requester_id' => $secondRequester->id, 'blood_group' => 'B+', 'units_needed' => 1,
            'hospital_name' => 'Savar Upazila Health Complex', 'location' => 'Savar', 'urgency' => 'within_24h',
            'patient_context' => 'Scheduled surgery, pre-arranged donor fell through.',
            'contact_method' => '01711000004', 'status' => 'open',
            'is_verified' => true, 'verified_by' => $verifiers->last()->id,
            'expires_at' => now()->addHours(BloodRequest::EXPIRES_AFTER_HOURS),
        ]);

        BloodRequest::create([
            'requester_id' => $thirdRequester->id, 'blood_group' => 'A+', 'units_needed' => 3,
            'hospital_name' => 'Dhaka Medical College Hospital', 'location' => 'Dhaka', 'urgency' => 'critical',
            'patient_context' => 'Complicated delivery, ICU admission.',
            'contact_method' => '01711000010', 'status' => 'open',
            'is_verified' => false, 'verified_by' => null,
            'expires_at' => now()->addHours(BloodRequest::EXPIRES_AFTER_HOURS),
        ]);

        // Donation history + matching badges, seeded together explicitly
        // (WithoutModelEvents means DonationHistoryObserver won't fire) so
        // the leaderboard and profile pages aren't empty on first load.
        $firstDonationBadge = Badge::where('slug', 'first-donation')->first();
        $fiveTimeBadge = Badge::where('slug', 'five-time-donor')->first();
        $rareBadge = Badge::where('slug', 'rare-blood-type')->first();

        foreach ($donors->slice(0, 6) as $entry) {
            DonationHistory::create([
                'donor_id' => $entry['user']->id,
                'request_id' => null,
                'confirmed_at' => now()->subDays(rand(10, 300)),
            ]);
            $entry['profile']->increment('trust_score');

            if ($firstDonationBadge) {
                $entry['user']->badges()->attach($firstDonationBadge->id, ['earned_at' => now()]);
            }

            if (in_array($entry['profile']->blood_group, DonorProfile::RARE_BLOOD_GROUPS, true) && $rareBadge) {
                $entry['user']->badges()->attach($rareBadge->id, ['earned_at' => now()]);
            }
        }

        // One donor with a longer history to demonstrate the 5-time badge.
        $veteranDonor = $donors->first();
        for ($i = 0; $i < 4; $i++) {
            DonationHistory::create([
                'donor_id' => $veteranDonor['user']->id,
                'request_id' => null,
                'confirmed_at' => now()->subDays(150 + $i * 130),
            ]);
            $veteranDonor['profile']->increment('trust_score');
        }
        if ($fiveTimeBadge) {
            $veteranDonor['user']->badges()->attach($fiveTimeBadge->id, ['earned_at' => now()]);
        }

        $this->command?->info('Seeded: '.User::count().' users, '.BloodRequest::count().' requests, '.DonationHistory::count().' donations.');
    }
}
