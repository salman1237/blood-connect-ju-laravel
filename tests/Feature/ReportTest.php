<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function onboardedUser(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['role' => 'staff', 'department' => 'Physics'], $attributes));
        DonorProfile::factory()->for($user)->create();

        return $user;
    }

    public function test_a_user_can_report_a_request(): void
    {
        $reporter = $this->onboardedUser();
        $bloodRequest = BloodRequest::factory()->create();

        $response = $this->actingAs($reporter)->post(route('requests.report', $bloodRequest), [
            'reason' => 'fraudulent',
            'details' => 'This hospital does not exist.',
        ]);

        $response->assertRedirect(route('requests.show', $bloodRequest));
        $this->assertDatabaseHas('reports', [
            'request_id' => $bloodRequest->id,
            'reporter_id' => $reporter->id,
            'reason' => 'fraudulent',
            'status' => 'pending',
        ]);
    }

    public function test_a_user_cannot_report_the_same_request_twice(): void
    {
        $reporter = $this->onboardedUser();
        $bloodRequest = BloodRequest::factory()->create();
        $this->actingAs($reporter)->post(route('requests.report', $bloodRequest), ['reason' => 'spam']);

        $response = $this->actingAs($reporter)->post(route('requests.report', $bloodRequest), ['reason' => 'spam']);

        $response->assertForbidden();
        $this->assertSame(1, $bloodRequest->reports()->count());
    }

    public function test_reporting_requires_a_valid_reason(): void
    {
        $reporter = $this->onboardedUser();
        $bloodRequest = BloodRequest::factory()->create();

        $response = $this->actingAs($reporter)->post(route('requests.report', $bloodRequest), [
            'reason' => 'not-a-real-reason',
        ]);

        $response->assertSessionHasErrors('reason');
    }
}
