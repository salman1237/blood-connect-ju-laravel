<?php

namespace Tests\Feature\Api\V1;

use App\Models\BloodRequest;
use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestTest extends TestCase
{
    use RefreshDatabase;

    private function onboardedUser(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'staff', 'department' => 'Physics'], $overrides));
        DonorProfile::factory()->for($user)->create();

        return $user;
    }

    public function test_requests_index_requires_onboarding(): void
    {
        $user = User::factory()->create(['role' => 'student', 'hall' => null, 'department' => null]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/requests');

        $response->assertForbidden();
    }

    public function test_requests_index_returns_open_requests_urgency_then_recency(): void
    {
        $user = $this->onboardedUser();
        $critical = BloodRequest::factory()->create(['urgency' => 'critical', 'status' => 'open']);
        $planned = BloodRequest::factory()->create(['urgency' => 'planned', 'status' => 'open']);
        BloodRequest::factory()->create(['status' => 'fulfilled']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/requests');

        $response->assertOk();
        $ids = collect($response->json())->pluck('id');
        $this->assertCount(2, $ids);
        $this->assertSame($critical->id, $ids->first());
        $this->assertSame($planned->id, $ids->last());
    }

    public function test_requests_index_filters_by_blood_group(): void
    {
        $user = $this->onboardedUser();
        $match = BloodRequest::factory()->create(['blood_group' => 'O-', 'status' => 'open']);
        BloodRequest::factory()->create(['blood_group' => 'A+', 'status' => 'open']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/requests?blood_group=O-');

        $response->assertOk();
        $ids = collect($response->json())->pluck('id');
        $this->assertEquals([$match->id], $ids->all());
    }

    public function test_requests_stats_endpoint(): void
    {
        $user = $this->onboardedUser();
        BloodRequest::factory()->create(['status' => 'open', 'urgency' => 'critical']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/requests/stats');

        $response->assertOk();
        $response->assertJsonStructure(['active', 'critical', 'fulfilled_today', 'registered_donors']);
    }

    public function test_mine_returns_every_status_the_user_has_posted(): void
    {
        $user = $this->onboardedUser();
        $open = BloodRequest::factory()->for($user, 'requester')->create(['status' => 'open']);
        $fulfilled = BloodRequest::factory()->for($user, 'requester')->create(['status' => 'fulfilled']);
        // Someone else's request must never show up here.
        BloodRequest::factory()->create(['status' => 'open']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/requests/mine');

        $response->assertOk();
        $ids = collect($response->json())->pluck('id');
        $this->assertEqualsCanonicalizing([$open->id, $fulfilled->id], $ids->all());
    }

    public function test_mine_requires_onboarding(): void
    {
        $user = User::factory()->create(['role' => 'student', 'hall' => null, 'department' => null]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/requests/mine');

        $response->assertForbidden();
    }

    public function test_a_request_can_be_created_via_the_api(): void
    {
        $user = $this->onboardedUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/requests', [
            'blood_group' => 'AB-',
            'units_needed' => 2,
            'hospital_name' => 'Enam Medical College',
            'location' => 'Savar',
            'urgency' => 'critical',
            'contact_method' => '01712345678',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('hospital_name', 'Enam Medical College');
        $response->assertJsonPath('requester.id', $user->id);
        // Regression: status/is_verified are DB-level defaults, not part of
        // the validated payload — Eloquent doesn't pull column defaults
        // back into the in-memory model after create(), so this response
        // used to serialize them as null instead of the real "open"/false.
        $response->assertJsonPath('status', 'open');
        $response->assertJsonPath('is_verified', false);
        $this->assertDatabaseHas('blood_requests', ['hospital_name' => 'Enam Medical College', 'requester_id' => $user->id]);
    }

    public function test_creating_a_request_validates_required_fields(): void
    {
        $user = $this->onboardedUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/requests', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['blood_group', 'units_needed', 'hospital_name', 'urgency', 'contact_method']);
    }

    public function test_request_show_includes_responses(): void
    {
        $requester = $this->onboardedUser();
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->create();

        $response = $this->actingAs($requester, 'sanctum')->getJson("/api/v1/requests/{$bloodRequest->id}");

        $response->assertOk();
        $response->assertJsonPath('id', $bloodRequest->id);
        $response->assertJsonStructure(['requester' => ['id', 'name'], 'responses']);
    }

    public function test_matching_donors_endpoint_ranks_and_filters_correctly(): void
    {
        $requester = $this->onboardedUser(['hall' => null, 'department' => 'Physics']);
        $bloodRequest = BloodRequest::factory()->for($requester, 'requester')->create(['blood_group' => 'O-']);

        $exactMatch = $this->onboardedUser(['department' => 'Physics']);
        DonorProfile::query()->where('user_id', $exactMatch->id)->update(['blood_group' => 'O-', 'is_available' => true]);

        $unavailable = $this->onboardedUser();
        DonorProfile::query()->where('user_id', $unavailable->id)->update(['blood_group' => 'O-', 'is_available' => false]);

        $incompatible = $this->onboardedUser();
        DonorProfile::query()->where('user_id', $incompatible->id)->update(['blood_group' => 'A+', 'is_available' => true]);

        $response = $this->actingAs($requester, 'sanctum')->getJson("/api/v1/requests/{$bloodRequest->id}/donors");

        $response->assertOk();
        $ids = collect($response->json())->pluck('id');
        $this->assertContains($exactMatch->id, $ids);
        $this->assertNotContains($unavailable->id, $ids);
        $this->assertNotContains($incompatible->id, $ids);
    }

    public function test_requests_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/requests')->assertUnauthorized();
    }
}
