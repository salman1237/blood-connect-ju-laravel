<?php

namespace Tests\Feature;

use App\Models\DonationHistory;
use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_page_is_displayed_even_with_no_donor_profile_yet(): void
    {
        // /profile is deliberately reachable before onboarding is complete.
        $user = User::factory()->create(['role' => 'student', 'hall' => null, 'department' => null]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
    }

    public function test_profile_page_shows_confirmed_donation_history(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        DonorProfile::factory()->for($user)->create();
        DonationHistory::factory()->for($user, 'donor')->create(['confirmed_at' => now()]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $this->assertCount(1, $response->viewData('donationHistory'));
    }

    public function test_profile_page_shows_earned_badges(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        DonorProfile::factory()->for($user)->create(['blood_group' => 'O-']);
        DonationHistory::factory()->for($user, 'donor')->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee('Rare Donor');
    }

    public function test_donor_profile_can_be_updated(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        DonorProfile::factory()->for($user)->create(['blood_group' => 'O-']);

        $response = $this->actingAs($user)->patch('/profile/donor', [
            'blood_group' => 'AB+',
            'department' => 'Physics',
            'phone' => '01711111111',
            'is_available' => '0',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('AB+', $user->donorProfile->blood_group);
        $this->assertSame('Physics', $user->department);
        $this->assertFalse($user->donorProfile->is_available);
    }

    public function test_unchecking_availability_actually_turns_it_off(): void
    {
        // Regression: unchecked checkboxes don't submit at all, so without
        // the hidden-input pairing this would silently stay "available".
        $user = User::factory()->create(['role' => 'staff']);
        DonorProfile::factory()->for($user)->create(['is_available' => true]);

        $this->actingAs($user)->patch('/profile/donor', [
            'blood_group' => 'O-',
            'department' => 'Physics',
            'is_available' => '0',
        ]);

        $this->assertFalse($user->donorProfile->fresh()->is_available);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
