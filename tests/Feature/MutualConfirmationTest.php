<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\DonorProfile;
use App\Models\RequestResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MutualConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private function onboardedUser(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'staff', 'department' => 'Physics'], $overrides));
        DonorProfile::factory()->for($user)->create();

        return $user;
    }

    private function confirmedResponseOnFulfilledRequest(): array
    {
        $requester = $this->onboardedUser();
        $donor = $this->onboardedUser();
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->fulfilled()->create();
        $response = RequestResponse::factory()->confirmed()->for($bloodRequest, 'bloodRequest')->for($donor, 'donor')->create();

        return [$requester, $donor, $bloodRequest, $response];
    }

    public function test_donation_is_not_logged_until_both_sides_confirm(): void
    {
        [, $donor, $bloodRequest, $response] = $this->confirmedResponseOnFulfilledRequest();

        $this->actingAs($donor)->patch(route('requests.responses.confirm-donation', [$bloodRequest, $response]));

        $this->assertDatabaseCount('donation_history', 0);
        $this->assertSame(0, $donor->donorProfile->fresh()->trust_score);
    }

    public function test_donation_is_logged_and_trust_score_bumped_once_both_sides_confirm(): void
    {
        [$requester, $donor, $bloodRequest, $response] = $this->confirmedResponseOnFulfilledRequest();

        $this->actingAs($donor)->patch(route('requests.responses.confirm-donation', [$bloodRequest, $response]));
        $this->actingAs($requester)->patch(route('requests.responses.confirm-donation', [$bloodRequest, $response]));

        $this->assertDatabaseHas('donation_history', [
            'donor_id' => $donor->id,
            'request_id' => $bloodRequest->id,
        ]);
        $this->assertSame(1, $donor->donorProfile->fresh()->trust_score);
    }

    public function test_confirmation_order_does_not_matter(): void
    {
        [$requester, $donor, $bloodRequest, $response] = $this->confirmedResponseOnFulfilledRequest();

        $this->actingAs($requester)->patch(route('requests.responses.confirm-donation', [$bloodRequest, $response]));
        $this->assertDatabaseCount('donation_history', 0);

        $this->actingAs($donor)->patch(route('requests.responses.confirm-donation', [$bloodRequest, $response]));
        $this->assertDatabaseCount('donation_history', 1);
    }

    public function test_a_bystander_cannot_confirm_the_donation(): void
    {
        [, , $bloodRequest, $response] = $this->confirmedResponseOnFulfilledRequest();
        $bystander = $this->onboardedUser();

        $result = $this->actingAs($bystander)->patch(route('requests.responses.confirm-donation', [$bloodRequest, $response]));

        $result->assertForbidden();
    }

    public function test_cannot_confirm_before_the_request_is_fulfilled(): void
    {
        $requester = $this->onboardedUser();
        $donor = $this->onboardedUser();
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->create(['status' => 'donor_found']);
        $response = RequestResponse::factory()->confirmed()->for($bloodRequest, 'bloodRequest')->for($donor, 'donor')->create();

        $result = $this->actingAs($donor)->patch(route('requests.responses.confirm-donation', [$bloodRequest, $response]));

        $result->assertForbidden();
    }

    public function test_cannot_confirm_the_same_side_twice(): void
    {
        [, $donor, $bloodRequest, $response] = $this->confirmedResponseOnFulfilledRequest();
        $this->actingAs($donor)->patch(route('requests.responses.confirm-donation', [$bloodRequest, $response]));

        $result = $this->actingAs($donor)->patch(route('requests.responses.confirm-donation', [$bloodRequest, $response]));

        $result->assertForbidden();
    }
}
