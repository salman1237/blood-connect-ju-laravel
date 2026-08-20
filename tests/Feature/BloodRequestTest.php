<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BloodRequestTest extends TestCase
{
    use RefreshDatabase;

    private function onboardedUser(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => 'staff',
            'hall' => null,
            'department' => 'Physics',
        ], $attributes));
        DonorProfile::factory()->for($user)->create();

        return $user;
    }

    public function test_dashboard_shows_only_open_requests(): void
    {
        $user = $this->onboardedUser();
        BloodRequest::factory()->create(['status' => 'open']);
        BloodRequest::factory()->fulfilled()->create();
        BloodRequest::factory()->expired()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('requests', fn ($requests) => $requests->count() === 1);
    }

    public function test_dashboard_sorts_critical_before_planned_regardless_of_recency(): void
    {
        $user = $this->onboardedUser();
        $planned = BloodRequest::factory()->create(['urgency' => 'planned', 'created_at' => now()]);
        $critical = BloodRequest::factory()->critical()->create(['created_at' => now()->subHour()]);

        $response = $this->actingAs($user)->get('/dashboard');

        $ids = $response->viewData('requests')->pluck('id');
        $this->assertSame([$critical->id, $planned->id], $ids->all());
    }

    public function test_dashboard_filters_by_blood_group(): void
    {
        $user = $this->onboardedUser();
        BloodRequest::factory()->create(['blood_group' => 'O-']);
        BloodRequest::factory()->create(['blood_group' => 'AB+']);

        $response = $this->actingAs($user)->get('/dashboard?blood_group=O-');

        $requests = $response->viewData('requests');
        $this->assertCount(1, $requests);
        $this->assertSame('O-', $requests->first()->blood_group);
    }

    public function test_dashboard_filters_by_requesters_hall(): void
    {
        $user = $this->onboardedUser();
        $inHall = User::factory()->create(['hall' => 'Rokeya Hall']);
        $elsewhere = User::factory()->create(['hall' => 'Al Beruni Hall']);
        BloodRequest::factory()->for($inHall, 'requester')->create();
        BloodRequest::factory()->for($elsewhere, 'requester')->create();

        $response = $this->actingAs($user)->get('/dashboard?hall=Rokeya+Hall');

        $requests = $response->viewData('requests');
        $this->assertCount(1, $requests);
        $this->assertSame('Rokeya Hall', $requests->first()->requester->hall);
    }

    public function test_request_can_be_created(): void
    {
        $user = $this->onboardedUser();

        $response = $this->actingAs($user)->post('/requests', [
            'blood_group' => 'O-',
            'units_needed' => 2,
            'hospital_name' => 'Enam Medical College Hospital',
            'location' => 'Savar',
            'urgency' => 'critical',
            'patient_context' => 'Accident victim',
            'contact_method' => '01712345678',
        ]);

        $this->assertDatabaseHas('blood_requests', [
            'requester_id' => $user->id,
            'blood_group' => 'O-',
            'status' => 'open',
        ]);

        $bloodRequest = BloodRequest::first();
        $response->assertRedirect(route('requests.show', $bloodRequest));
        $this->assertNotNull($bloodRequest->expires_at);
        $this->assertTrue($bloodRequest->expires_at->isSameHour(now()->addHours(72)));
    }

    public function test_request_creation_validates_required_fields(): void
    {
        $user = $this->onboardedUser();

        $response = $this->actingAs($user)->post('/requests', []);

        $response->assertSessionHasErrors(['blood_group', 'units_needed', 'hospital_name', 'urgency', 'contact_method']);
    }

    public function test_request_detail_page_shows_status_and_requester(): void
    {
        $user = $this->onboardedUser();
        $bloodRequest = BloodRequest::factory()->create();

        $response = $this->actingAs($user)->get(route('requests.show', $bloodRequest));

        $response->assertOk();
        $response->assertSee($bloodRequest->hospital_name);
        $response->assertSee($bloodRequest->requester->name);
    }

    public function test_requester_can_advance_status_from_open_to_donor_found_to_fulfilled(): void
    {
        $user = $this->onboardedUser();
        $bloodRequest = BloodRequest::factory()->for($user, 'requester')->create(['status' => 'open']);

        $this->actingAs($user)->post(route('requests.fulfill', $bloodRequest));
        $this->assertSame('donor_found', $bloodRequest->fresh()->status);

        $this->actingAs($user)->post(route('requests.fulfill', $bloodRequest));
        $this->assertSame('fulfilled', $bloodRequest->fresh()->status);
    }

    public function test_only_the_requester_can_fulfill_their_request(): void
    {
        $requester = $this->onboardedUser();
        $someoneElse = $this->onboardedUser();
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->create(['status' => 'open']);

        $response = $this->actingAs($someoneElse)->post(route('requests.fulfill', $bloodRequest));

        $response->assertForbidden();
        $this->assertSame('open', $bloodRequest->fresh()->status);
    }

    public function test_fulfilled_requests_cannot_be_fulfilled_again(): void
    {
        $user = $this->onboardedUser();
        $bloodRequest = BloodRequest::factory()->for($user, 'requester')->fulfilled()->create();

        $response = $this->actingAs($user)->post(route('requests.fulfill', $bloodRequest));

        $response->assertForbidden();
    }

    public function test_requests_index_lists_all_statuses_paginated(): void
    {
        $user = $this->onboardedUser();
        BloodRequest::factory()->count(3)->create();
        BloodRequest::factory()->fulfilled()->create();

        $response = $this->actingAs($user)->get('/requests');

        $response->assertOk();
        $this->assertCount(4, $response->viewData('requests'));
    }
}
