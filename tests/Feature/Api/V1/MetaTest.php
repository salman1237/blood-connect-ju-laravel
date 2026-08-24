<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_endpoint_returns_reference_data_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/meta');

        $response->assertOk();
        $response->assertJsonStructure(['halls', 'departments', 'blood_groups', 'batches']);
        $this->assertContains('A+', $response->json('blood_groups'));
        $this->assertContains('Rokeya Hall', $response->json('halls'));
    }

    public function test_meta_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/meta');

        $response->assertUnauthorized();
    }
}
