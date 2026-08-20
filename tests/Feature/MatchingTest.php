<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\DonorProfile;
use App\Models\User;
use App\Notifications\NewMatchingRequest;
use App\Notifications\RequestResponded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MatchingTest extends TestCase
{
    use RefreshDatabase;

    private function donor(string $bloodGroup, array $overrides = [], array $profileOverrides = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'staff', 'department' => 'Physics'], $overrides));
        DonorProfile::factory()->for($user)->create(array_merge(['blood_group' => $bloodGroup], $profileOverrides));

        return $user;
    }

    private function requester(array $overrides = [], ?array $profileOverrides = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'staff', 'department' => 'Physics'], $overrides));

        // Needs to have completed onboarding too, since confirming a
        // responder goes through the same 'onboarded' middleware. Pass
        // profileOverrides: null to skip (when the test creates its own).
        if ($profileOverrides !== null) {
            DonorProfile::factory()->for($user)->create($profileOverrides);
        }

        return $user;
    }

    // --- Blood-group compatibility ---

    public function test_o_negative_is_the_universal_donor(): void
    {
        $request = BloodRequest::factory()->make(['blood_group' => 'AB+']);

        $this->assertContains('O-', $request->compatibleDonorBloodGroups());
    }

    public function test_ab_positive_can_only_donate_to_ab_positive_requests(): void
    {
        $abPlusDonorCompatibleWith = collect(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB-', 'AB+'])
            ->filter(fn ($group) => in_array('AB+', BloodRequest::DONOR_COMPATIBILITY[$group], true));

        $this->assertSame(['AB+'], $abPlusDonorCompatibleWith->values()->all());
    }

    public function test_a_positive_request_accepts_a_negative_and_o_donors_but_not_b_or_ab(): void
    {
        $compatible = (new BloodRequest(['blood_group' => 'A+']))->compatibleDonorBloodGroups();

        $this->assertEqualsCanonicalizing(['O-', 'O+', 'A-', 'A+'], $compatible);
    }

    // --- Ranking ---

    public function test_matching_scope_ranks_exact_blood_group_before_compatible_only(): void
    {
        $requester = $this->requester();
        $request = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'A+']);
        $oNegDonor = $this->donor('O-');
        $aPosDonor = $this->donor('A+');

        $ranked = DonorProfile::query()->matchingRequest($request)->get();

        $this->assertSame($aPosDonor->id, $ranked->first()->user_id);
        $this->assertSame($oNegDonor->id, $ranked->last()->user_id);
    }

    public function test_matching_scope_ranks_same_hall_before_elsewhere(): void
    {
        $requester = $this->requester(['hall' => null, 'department' => 'Physics']);
        $request = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);
        $sameDept = $this->donor('O-', ['department' => 'Physics']);
        $elsewhere = $this->donor('O-', ['department' => 'Chemistry']);

        $ranked = DonorProfile::query()->matchingRequest($request)->get();

        $this->assertSame($sameDept->id, $ranked->first()->user_id);
        $this->assertSame($elsewhere->id, $ranked->last()->user_id);
    }

    public function test_matching_scope_excludes_ineligible_donors(): void
    {
        $requester = $this->requester();
        $request = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);
        $this->donor('O-', [], ['last_donation_date' => now()->subDays(10)]);

        $ranked = DonorProfile::query()->matchingRequest($request)->get();

        $this->assertCount(0, $ranked);
    }

    public function test_matching_scope_excludes_the_requester_even_if_they_are_a_compatible_donor(): void
    {
        $requester = $this->requester(profileOverrides: ['blood_group' => 'O-']);
        $request = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);

        $ranked = DonorProfile::query()->matchingRequest($request)->get();

        $this->assertCount(0, $ranked);
    }

    // --- Responding ---

    public function test_compatible_donor_can_respond_to_an_open_request(): void
    {
        $requester = $this->requester();
        $request = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-', 'status' => 'open']);
        $donor = $this->donor('O-');

        $response = $this->actingAs($donor)->post(route('requests.respond', $request));

        $response->assertRedirect(route('requests.show', $request));
        $this->assertDatabaseHas('request_responses', [
            'request_id' => $request->id,
            'donor_id' => $donor->id,
            'status' => 'responded',
        ]);
    }

    public function test_responding_notifies_the_requester(): void
    {
        Notification::fake();

        $requester = $this->requester();
        $request = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);
        $donor = $this->donor('O-');

        $this->actingAs($donor)->post(route('requests.respond', $request));

        Notification::assertSentTo($requester, RequestResponded::class);
    }

    public function test_donor_cannot_respond_twice_to_the_same_request(): void
    {
        $requester = $this->requester();
        $request = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);
        $donor = $this->donor('O-');
        $this->actingAs($donor)->post(route('requests.respond', $request));

        $response = $this->actingAs($donor)->post(route('requests.respond', $request));

        $response->assertForbidden();
        $this->assertSame(1, $request->responses()->count());
    }

    public function test_requester_cannot_respond_to_their_own_request(): void
    {
        $requester = $this->requester(profileOverrides: ['blood_group' => 'O-']);
        $request = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);

        $response = $this->actingAs($requester)->post(route('requests.respond', $request));

        $response->assertForbidden();
    }

    public function test_incompatible_donor_cannot_respond(): void
    {
        $requester = $this->requester();
        $request = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);
        $donor = $this->donor('A+'); // not compatible with an O- request

        $response = $this->actingAs($donor)->post(route('requests.respond', $request));

        $response->assertForbidden();
    }

    public function test_ineligible_donor_cannot_respond(): void
    {
        $requester = $this->requester();
        $request = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);
        $donor = $this->donor('O-', [], ['last_donation_date' => now()->subDays(10)]);

        $response = $this->actingAs($donor)->post(route('requests.respond', $request));

        $response->assertForbidden();
    }

    // --- Confirming ---

    public function test_requester_can_confirm_a_responder(): void
    {
        $requester = $this->requester();
        $request = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);
        $donor = $this->donor('O-');
        $this->actingAs($donor)->post(route('requests.respond', $request));
        $response = $request->responses()->first();

        $result = $this->actingAs($requester)->patch(route('requests.responses.confirm', [$request, $response]));

        $result->assertRedirect(route('requests.show', $request));
        $this->assertSame('confirmed', $response->fresh()->status);
    }

    public function test_non_requester_cannot_confirm_a_responder(): void
    {
        $requester = $this->requester();
        $request = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);
        $donor = $this->donor('O-');
        $this->actingAs($donor)->post(route('requests.respond', $request));
        $response = $request->responses()->first();

        $result = $this->actingAs($donor)->patch(route('requests.responses.confirm', [$request, $response]));

        $result->assertForbidden();
    }

    // --- New-request notifications ---

    public function test_creating_a_request_notifies_matching_available_donors(): void
    {
        Notification::fake();

        $matchingDonor = $this->donor('O-');
        $requester = $this->requester();

        $request = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);

        Notification::assertSentTo($matchingDonor, NewMatchingRequest::class);
    }

    public function test_creating_a_request_does_not_notify_unavailable_donors(): void
    {
        Notification::fake();

        $unavailableDonor = $this->donor('O-', [], ['is_available' => false]);
        $requester = $this->requester();

        BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);

        Notification::assertNotSentTo($unavailableDonor, NewMatchingRequest::class);
    }

    public function test_creating_a_request_does_not_notify_incompatible_donors(): void
    {
        Notification::fake();

        $incompatibleDonor = $this->donor('A+');
        $requester = $this->requester();

        BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);

        Notification::assertNotSentTo($incompatibleDonor, NewMatchingRequest::class);
    }
}
