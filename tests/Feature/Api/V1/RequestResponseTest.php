<?php

namespace Tests\Feature\Api\V1;

use App\Models\BloodRequest;
use App\Models\DonorProfile;
use App\Models\RequestResponse;
use App\Models\User;
use App\Notifications\DonationConfirmed;
use App\Notifications\DonorSelected;
use App\Notifications\RequestResponded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RequestResponseTest extends TestCase
{
    use RefreshDatabase;

    private function donor(string $bloodGroup = 'O-', array $overrides = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'staff', 'department' => 'Physics'], $overrides));
        DonorProfile::factory()->for($user)->create(['blood_group' => $bloodGroup, 'is_available' => true]);

        return $user;
    }

    private function requester(): User
    {
        $user = User::factory()->create(['role' => 'staff', 'department' => 'Physics']);
        DonorProfile::factory()->for($user)->create();

        return $user;
    }

    public function test_a_compatible_donor_can_respond_via_the_api(): void
    {
        Notification::fake();

        $requester = $this->requester();
        $donor = $this->donor('O-');
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);

        $response = $this->actingAs($donor, 'sanctum')->postJson("/api/v1/requests/{$bloodRequest->id}/respond");

        $response->assertOk();
        $this->assertDatabaseHas('request_responses', [
            'request_id' => $bloodRequest->id,
            'donor_id' => $donor->id,
            'status' => 'responded',
        ]);
        Notification::assertSentTo($requester, RequestResponded::class);
    }

    public function test_the_requester_cannot_respond_to_their_own_request(): void
    {
        $requester = $this->requester();
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);

        $response = $this->actingAs($requester, 'sanctum')->postJson("/api/v1/requests/{$bloodRequest->id}/respond");

        $response->assertForbidden();
    }

    public function test_a_donor_cannot_respond_twice(): void
    {
        $requester = $this->requester();
        $donor = $this->donor('O-');
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);
        RequestResponse::factory()->for($bloodRequest, 'bloodRequest')->for($donor, 'donor')->create();

        $response = $this->actingAs($donor, 'sanctum')->postJson("/api/v1/requests/{$bloodRequest->id}/respond");

        $response->assertForbidden();
    }

    public function test_requester_can_confirm_a_responder_via_the_api(): void
    {
        Notification::fake();

        $requester = $this->requester();
        $donor = $this->donor('O-');
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);
        $requestResponse = RequestResponse::factory()->for($bloodRequest, 'bloodRequest')->for($donor, 'donor')->create();

        $response = $this->actingAs($requester, 'sanctum')
            ->patchJson("/api/v1/requests/{$bloodRequest->id}/responses/{$requestResponse->id}/confirm");

        $response->assertOk();
        $this->assertSame('confirmed', $requestResponse->fresh()->status);
        Notification::assertSentTo($donor, DonorSelected::class);
    }

    public function test_a_bystander_cannot_confirm_a_responder(): void
    {
        $requester = $this->requester();
        $donor = $this->donor('O-');
        $bystander = $this->requester();
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);
        $requestResponse = RequestResponse::factory()->for($bloodRequest, 'bloodRequest')->for($donor, 'donor')->create();

        $response = $this->actingAs($bystander, 'sanctum')
            ->patchJson("/api/v1/requests/{$bloodRequest->id}/responses/{$requestResponse->id}/confirm");

        $response->assertForbidden();
    }

    public function test_requester_can_fulfill_via_the_api(): void
    {
        $requester = $this->requester();
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->create(['status' => 'open']);

        $response = $this->actingAs($requester, 'sanctum')->postJson("/api/v1/requests/{$bloodRequest->id}/fulfill");

        $response->assertOk();
        $response->assertJsonPath('status', 'donor_found');
    }

    public function test_only_the_requester_can_fulfill(): void
    {
        $requester = $this->requester();
        $bystander = $this->requester();
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->create(['status' => 'open']);

        $response = $this->actingAs($bystander, 'sanctum')->postJson("/api/v1/requests/{$bloodRequest->id}/fulfill");

        $response->assertForbidden();
    }

    public function test_mutual_confirmation_via_the_api_logs_donation_and_bumps_trust_score(): void
    {
        Notification::fake();

        $requester = $this->requester();
        $donor = $this->donor('O-');
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-', 'status' => 'fulfilled']);
        $requestResponse = RequestResponse::factory()->confirmed()->for($bloodRequest, 'bloodRequest')->for($donor, 'donor')->create();

        $this->actingAs($requester, 'sanctum')
            ->patchJson("/api/v1/requests/{$bloodRequest->id}/responses/{$requestResponse->id}/confirm-donation")
            ->assertOk();
        $this->assertDatabaseCount('donation_history', 0);

        $this->actingAs($donor, 'sanctum')
            ->patchJson("/api/v1/requests/{$bloodRequest->id}/responses/{$requestResponse->id}/confirm-donation")
            ->assertOk();

        $this->assertDatabaseHas('donation_history', ['donor_id' => $donor->id, 'request_id' => $bloodRequest->id]);
        $this->assertSame(1, $donor->donorProfile->fresh()->trust_score);
        Notification::assertSentTo($donor, DonationConfirmed::class);
        Notification::assertSentTo($requester, DonationConfirmed::class);
    }

    public function test_cannot_confirm_the_same_side_of_a_donation_twice(): void
    {
        $requester = $this->requester();
        $donor = $this->donor('O-');
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->create(['status' => 'fulfilled']);
        $requestResponse = RequestResponse::factory()->confirmed()
            ->for($bloodRequest, 'bloodRequest')->for($donor, 'donor')
            ->create(['requester_confirmed_at' => now()]);

        $response = $this->actingAs($requester, 'sanctum')
            ->patchJson("/api/v1/requests/{$bloodRequest->id}/responses/{$requestResponse->id}/confirm-donation");

        $response->assertForbidden();
    }

    public function test_response_endpoints_require_authentication(): void
    {
        $bloodRequest = BloodRequest::factory()->create();

        $this->postJson("/api/v1/requests/{$bloodRequest->id}/respond")->assertUnauthorized();
    }
}
