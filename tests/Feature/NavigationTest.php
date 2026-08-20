<?php

namespace Tests\Feature;

use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    private function onboardedUser(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'staff', 'department' => 'Physics'], $overrides));
        DonorProfile::factory()->for($user)->create();

        return $user;
    }

    public function test_regular_donor_does_not_see_verifier_or_admin_nav_links(): void
    {
        $user = $this->onboardedUser(['role' => 'student', 'hall' => 'Rokeya Hall', 'department' => 'Physics']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('Verifier queue');
        $response->assertDontSee('Admin dashboard');
    }

    public function test_verifier_sees_verifier_queue_link_but_not_admin_dashboard(): void
    {
        $user = $this->onboardedUser(['role' => 'verifier']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Verifier queue');
        $response->assertDontSee('Admin dashboard');
    }

    public function test_admin_sees_both_verifier_queue_and_admin_dashboard_links(): void
    {
        $user = $this->onboardedUser(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Verifier queue');
        $response->assertSee('Admin dashboard');
    }

    public function test_every_user_sees_settings_link(): void
    {
        $user = $this->onboardedUser(['role' => 'student', 'hall' => 'Rokeya Hall', 'department' => 'Physics']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Settings');
    }
}
