<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\DonorProfile;
use App\Models\RequestResponse;
use App\Models\User;
use App\Notifications\DonationConfirmationPending;
use App\Notifications\DonationConfirmed;
use App\Notifications\DonorSelected;
use App\Notifications\RequestRejected;
use App\Notifications\RequestVerified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTriggersTest extends TestCase
{
    use RefreshDatabase;

    private function onboardedUser(array $overrides = [], array $profileOverrides = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'staff', 'department' => 'Physics'], $overrides));
        DonorProfile::factory()->for($user)->create($profileOverrides);

        return $user;
    }

    public function test_confirming_a_responder_notifies_the_donor_they_were_selected(): void
    {
        Notification::fake();
        $requester = $this->onboardedUser();
        $donor = $this->onboardedUser(profileOverrides: ['blood_group' => 'O-']);
        $request = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);
        $this->actingAs($donor)->post(route('requests.respond', $request));
        $response = $request->responses()->first();

        $this->actingAs($requester)->patch(route('requests.responses.confirm', [$request, $response]));

        Notification::assertSentTo($donor, DonorSelected::class);
    }

    public function test_approving_a_request_notifies_the_requester(): void
    {
        Notification::fake();
        $verifier = User::factory()->create(['role' => 'verifier']);
        $requester = $this->onboardedUser();
        $request = BloodRequest::factory()->for($requester, 'requester')->create(['is_verified' => false]);

        $this->actingAs($verifier)->post(route('verify.approve', $request));

        Notification::assertSentTo($requester, RequestVerified::class);
    }

    public function test_rejecting_a_request_notifies_the_requester(): void
    {
        Notification::fake();
        $verifier = User::factory()->create(['role' => 'verifier']);
        $requester = $this->onboardedUser();
        $request = BloodRequest::factory()->for($requester, 'requester')->create(['is_verified' => false, 'status' => 'open']);

        $this->actingAs($verifier)->post(route('verify.reject', $request));

        Notification::assertSentTo($requester, RequestRejected::class);
    }

    public function test_mutual_confirmation_notifies_both_donor_and_requester(): void
    {
        Notification::fake();
        $requester = $this->onboardedUser();
        $donor = $this->onboardedUser();
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->fulfilled()->create();
        $response = RequestResponse::factory()->confirmed()->for($bloodRequest, 'bloodRequest')->for($donor, 'donor')->create();

        $this->actingAs($donor)->patch(route('requests.responses.confirm-donation', [$bloodRequest, $response]));
        $this->actingAs($requester)->patch(route('requests.responses.confirm-donation', [$bloodRequest, $response]));

        Notification::assertSentTo($donor, DonationConfirmed::class);
        Notification::assertSentTo($requester, DonationConfirmed::class);
    }

    public function test_donation_confirmed_mail_content_differs_for_donor_vs_requester(): void
    {
        $requester = $this->onboardedUser();
        $donor = $this->onboardedUser();
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->fulfilled()->create();
        $response = RequestResponse::factory()->confirmed()->for($bloodRequest, 'bloodRequest')->for($donor, 'donor')->create();
        $notification = new DonationConfirmed($response);

        $donorMail = $notification->toMail($donor);
        $requesterMail = $notification->toMail($requester);

        $this->assertSame('Thanks for donating!', $donorMail->subject);
        $this->assertSame('Donation confirmed', $requesterMail->subject);
    }

    public function test_donor_confirming_first_notifies_the_requester_to_confirm_too(): void
    {
        Notification::fake();
        $requester = $this->onboardedUser();
        $donor = $this->onboardedUser();
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->fulfilled()->create();
        $response = RequestResponse::factory()->confirmed()->for($bloodRequest, 'bloodRequest')->for($donor, 'donor')->create();

        $this->actingAs($donor)->patch(route('requests.responses.confirm-donation', [$bloodRequest, $response]));

        Notification::assertSentTo($requester, DonationConfirmationPending::class);
        Notification::assertNotSentTo($donor, DonationConfirmationPending::class);
        Notification::assertNotSentTo($requester, DonationConfirmed::class);
    }

    public function test_requester_confirming_first_notifies_the_donor_to_confirm_too(): void
    {
        Notification::fake();
        $requester = $this->onboardedUser();
        $donor = $this->onboardedUser();
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->fulfilled()->create();
        $response = RequestResponse::factory()->confirmed()->for($bloodRequest, 'bloodRequest')->for($donor, 'donor')->create();

        $this->actingAs($requester)->patch(route('requests.responses.confirm-donation', [$bloodRequest, $response]));

        Notification::assertSentTo($donor, DonationConfirmationPending::class);
        Notification::assertNotSentTo($requester, DonationConfirmationPending::class);
        Notification::assertNotSentTo($donor, DonationConfirmed::class);
    }

    public function test_confirmation_pending_notification_is_not_sent_again_once_mutually_confirmed(): void
    {
        Notification::fake();
        $requester = $this->onboardedUser();
        $donor = $this->onboardedUser();
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->fulfilled()->create();
        $response = RequestResponse::factory()->confirmed()->for($bloodRequest, 'bloodRequest')->for($donor, 'donor')->create();

        $this->actingAs($donor)->patch(route('requests.responses.confirm-donation', [$bloodRequest, $response]));
        $this->actingAs($requester)->patch(route('requests.responses.confirm-donation', [$bloodRequest, $response]));

        Notification::assertSentToTimes($requester, DonationConfirmationPending::class, 1);
        Notification::assertSentToTimes($donor, DonationConfirmationPending::class, 0);
        Notification::assertSentTo($donor, DonationConfirmed::class);
        Notification::assertSentTo($requester, DonationConfirmed::class);
    }

    public function test_donation_confirmation_pending_mail_content_differs_for_donor_vs_requester(): void
    {
        $requester = $this->onboardedUser();
        $donor = $this->onboardedUser();
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->fulfilled()->create();
        $response = RequestResponse::factory()->confirmed()->for($bloodRequest, 'bloodRequest')->for($donor, 'donor')->create();
        $notification = new DonationConfirmationPending($response);

        $donorMail = $notification->toMail($donor);
        $requesterMail = $notification->toMail($requester);

        $this->assertSame('Please confirm your donation', $donorMail->subject);
        $this->assertSame('Please confirm the donation', $requesterMail->subject);
    }
}
