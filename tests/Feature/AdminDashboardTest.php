<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\DonorProfile;
use App\Models\RequestResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_non_admin_cannot_view_the_dashboard(): void
    {
        $verifier = User::factory()->create(['role' => 'verifier']);

        $response = $this->actingAs($verifier)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_dashboard_counts_fulfilled_and_expired_requests(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        BloodRequest::factory()->fulfilled()->count(2)->create();
        BloodRequest::factory()->expired()->create();
        BloodRequest::factory()->create(['status' => 'open']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertViewHas('fulfilledCount', 2);
        $response->assertViewHas('expiredCount', 1);
    }

    public function test_dashboard_computes_average_response_time(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = BloodRequest::factory()->create(['created_at' => now()->subHours(2)]);
        RequestResponse::factory()->for($request, 'bloodRequest')->create(['created_at' => now()->subHours(1)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertViewHas('avgResponseMinutes', fn ($minutes) => (int) round($minutes) === 60);
    }

    public function test_dashboard_groups_donors_by_blood_group(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $donorA = User::factory()->create();
        DonorProfile::factory()->for($donorA)->create(['blood_group' => 'O-']);
        $donorB = User::factory()->create();
        DonorProfile::factory()->for($donorB)->create(['blood_group' => 'O-']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertViewHas('donorsByBloodGroup', fn ($grouped) => $grouped['O-'] === 2);
    }
}
