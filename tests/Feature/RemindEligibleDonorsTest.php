<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\DonorProfile;
use App\Models\User;
use App\Notifications\EligibleDonorReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RemindEligibleDonorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_eligible_donor_never_reminded_before_gets_a_reminder(): void
    {
        Notification::fake();
        $donor = User::factory()->create();
        DonorProfile::factory()->for($donor)->create(['last_donation_date' => null, 'last_reminded_at' => null]);

        $this->artisan('donors:remind-eligible')->assertSuccessful();

        Notification::assertSentTo($donor, EligibleDonorReminder::class);
    }

    public function test_ineligible_donor_is_not_reminded(): void
    {
        Notification::fake();
        $donor = User::factory()->create();
        DonorProfile::factory()->for($donor)->create(['last_donation_date' => now()->subDays(10)]);

        $this->artisan('donors:remind-eligible')->assertSuccessful();

        Notification::assertNotSentTo($donor, EligibleDonorReminder::class);
    }

    public function test_donor_reminded_recently_is_not_reminded_again(): void
    {
        Notification::fake();
        $donor = User::factory()->create();
        DonorProfile::factory()->for($donor)->create([
            'last_donation_date' => null,
            'last_reminded_at' => now()->subDays(10),
        ]);

        $this->artisan('donors:remind-eligible')->assertSuccessful();

        Notification::assertNotSentTo($donor, EligibleDonorReminder::class);
    }

    public function test_donor_reminded_past_the_cooldown_is_reminded_again(): void
    {
        Notification::fake();
        $donor = User::factory()->create();
        DonorProfile::factory()->for($donor)->create([
            'last_donation_date' => null,
            'last_reminded_at' => now()->subDays(40),
        ]);

        $this->artisan('donors:remind-eligible')->assertSuccessful();

        Notification::assertSentTo($donor, EligibleDonorReminder::class);
    }

    public function test_reminding_sets_last_reminded_at(): void
    {
        Notification::fake();
        $donor = User::factory()->create();
        $profile = DonorProfile::factory()->for($donor)->create(['last_donation_date' => null, 'last_reminded_at' => null]);

        $this->artisan('donors:remind-eligible');

        $this->assertNotNull($profile->fresh()->last_reminded_at);
    }

    public function test_reminder_counts_currently_open_matching_requests(): void
    {
        Notification::fake();
        $donor = User::factory()->create();
        DonorProfile::factory()->for($donor)->create(['blood_group' => 'O-', 'last_donation_date' => null]);
        BloodRequest::factory()->create(['blood_group' => 'O-', 'status' => 'open']);
        BloodRequest::factory()->create(['blood_group' => 'A+', 'status' => 'open']);
        BloodRequest::factory()->create(['blood_group' => 'AB+', 'status' => 'fulfilled']);

        $this->artisan('donors:remind-eligible');

        Notification::assertSentTo($donor, EligibleDonorReminder::class, function (EligibleDonorReminder $notification) {
            // O- is a universal donor, compatible with both open A+ and O-
            // requests, but not the fulfilled (non-open) AB+ one.
            return $notification->matchingOpenRequests === 2;
        });
    }
}
