<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_runs_without_error_and_produces_expected_data(): void
    {
        $this->seed();

        $this->assertTrue(User::where('role', 'admin')->exists());
        $this->assertTrue(User::where('role', 'verifier')->exists());
        $this->assertGreaterThanOrEqual(2, User::where('role', 'verifier')->count());
        $this->assertTrue(User::whereHas('donorProfile')->exists());

        $this->assertSame(3, BloodRequest::count());
        $this->assertDatabaseHas('blood_requests', [
            'blood_group' => 'O-', 'units_needed' => 2, 'urgency' => 'critical',
            'hospital_name' => 'Enam Medical College Hospital', 'is_verified' => true,
        ]);
        $this->assertDatabaseHas('blood_requests', [
            'blood_group' => 'B+', 'units_needed' => 1, 'urgency' => 'within_24h',
            'hospital_name' => 'Savar Upazila Health Complex', 'is_verified' => true,
        ]);
        $this->assertDatabaseHas('blood_requests', [
            'blood_group' => 'A+', 'units_needed' => 3, 'urgency' => 'critical',
            'hospital_name' => 'Dhaka Medical College Hospital', 'is_verified' => false,
        ]);

        $this->assertDatabaseCount('donation_history', 10);
        $this->assertGreaterThan(0, DB::table('donor_badges')->count());
    }

    /**
     * Regression: student donor entries in the seeder originally only set
     * 'hall', not 'department' — but hasCompletedOnboarding() requires a
     * department for every role, not just staff/faculty. Every seeded
     * donor got stuck being redirected to /onboarding instead of reaching
     * the dashboard, discovered only when logging in as one manually.
     */
    public function test_every_seeded_donor_can_actually_reach_the_dashboard(): void
    {
        $this->seed();

        $donors = User::whereHas('donorProfile')->get();
        $this->assertGreaterThan(0, $donors->count());

        foreach ($donors as $donor) {
            $this->assertTrue(
                $donor->hasCompletedOnboarding(),
                "{$donor->email} has a donor profile but can't complete onboarding (missing hall/department)."
            );
        }
    }

    /**
     * Regression: User::create() silently drops email_verified_at because
     * it's deliberately excluded from $fillable — the seeder set it in every
     * array literal, but without forceCreate() none of it actually landed
     * in the database, and every seeded account got stuck at /verify-email
     * on a real login despite looking correct in the seeder's own source.
     */
    public function test_every_seeded_user_can_pass_the_verified_middleware(): void
    {
        $this->seed();

        $users = User::all();
        $this->assertGreaterThan(0, $users->count());

        foreach ($users as $user) {
            $this->assertTrue(
                $user->hasVerifiedEmail(),
                "{$user->email} was seeded but has no email_verified_at — real login would bounce to /verify-email."
            );
        }
    }
}
