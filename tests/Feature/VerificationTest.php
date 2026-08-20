<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verifier_can_reach_the_queue_without_completing_onboarding(): void
    {
        $verifier = User::factory()->create(['role' => 'verifier']);

        $response = $this->actingAs($verifier)->get(route('verify.queue'));

        $response->assertOk();
    }

    public function test_regular_donor_cannot_reach_the_queue(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get(route('verify.queue'));

        $response->assertForbidden();
    }

    public function test_queue_lists_unverified_open_requests_only(): void
    {
        $verifier = User::factory()->create(['role' => 'verifier']);
        $unverified = BloodRequest::factory()->create(['is_verified' => false, 'status' => 'open']);
        BloodRequest::factory()->verified()->create(['status' => 'open']);
        BloodRequest::factory()->expired()->create(['is_verified' => false]);

        $response = $this->actingAs($verifier)->get(route('verify.queue'));

        $ids = $response->viewData('requests')->pluck('id');
        $this->assertTrue($ids->contains($unverified->id));
        $this->assertCount(1, $ids);
    }

    public function test_admin_can_approve_a_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $bloodRequest = BloodRequest::factory()->create(['is_verified' => false]);

        $response = $this->actingAs($admin)->post(route('verify.approve', $bloodRequest));

        $response->assertRedirect(route('verify.queue'));
        $bloodRequest->refresh();
        $this->assertTrue($bloodRequest->is_verified);
        $this->assertSame($admin->id, $bloodRequest->verified_by);
    }

    public function test_verifier_can_reject_a_request(): void
    {
        $verifier = User::factory()->create(['role' => 'verifier']);
        $bloodRequest = BloodRequest::factory()->create(['is_verified' => false, 'status' => 'open']);

        $response = $this->actingAs($verifier)->post(route('verify.reject', $bloodRequest));

        $response->assertRedirect(route('verify.queue'));
        $bloodRequest->refresh();
        $this->assertSame('expired', $bloodRequest->status);
        $this->assertNotNull($bloodRequest->rejected_at);
        $this->assertSame($verifier->id, $bloodRequest->rejected_by);
    }

    public function test_already_verified_request_cannot_be_approved_again(): void
    {
        $verifier = User::factory()->create(['role' => 'verifier']);
        $bloodRequest = BloodRequest::factory()->verified()->create();

        $response = $this->actingAs($verifier)->post(route('verify.approve', $bloodRequest));

        $response->assertForbidden();
    }
}
