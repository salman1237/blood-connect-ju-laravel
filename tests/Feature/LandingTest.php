<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_the_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('landing');
    }

    public function test_authenticated_user_is_redirected_to_the_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_live_requests_panel_shows_real_open_requests(): void
    {
        $open = BloodRequest::factory()->create(['status' => 'open', 'hospital_name' => 'Real Hospital']);
        BloodRequest::factory()->fulfilled()->create(['hospital_name' => 'Fulfilled Hospital']);
        BloodRequest::factory()->expired()->create(['hospital_name' => 'Expired Hospital']);

        $response = $this->get('/');

        $response->assertOk();
        $liveRequests = $response->viewData('liveRequests');
        $this->assertCount(1, $liveRequests);
        $this->assertSame($open->id, $liveRequests->first()->id);
    }

    public function test_live_requests_panel_limits_to_three(): void
    {
        BloodRequest::factory()->count(5)->create(['status' => 'open']);

        $response = $this->get('/');

        $this->assertCount(3, $response->viewData('liveRequests'));
    }

    public function test_stats_reflect_real_data(): void
    {
        $user = User::factory()->create();
        DonorProfile::factory()->for($user)->create();
        BloodRequest::factory()->fulfilled()->count(2)->create();

        $response = $this->get('/');

        $response->assertViewHas('donorCount', 1);
        $response->assertViewHas('fulfilledCount', 2);
    }
}
