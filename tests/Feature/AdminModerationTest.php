<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_the_moderation_queue(): void
    {
        $verifier = User::factory()->create(['role' => 'verifier']);

        $response = $this->actingAs($verifier)->get(route('admin.reports.index'));

        $response->assertForbidden();
    }

    public function test_queue_lists_only_pending_reports(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $bloodRequest = BloodRequest::factory()->create();
        $reporter = User::factory()->create();
        $pending = Report::create([
            'request_id' => $bloodRequest->id,
            'reporter_id' => $reporter->id,
            'reason' => 'spam',
            'status' => 'pending',
        ]);
        Report::create([
            'request_id' => BloodRequest::factory()->create()->id,
            'reporter_id' => $reporter->id,
            'reason' => 'other',
            'status' => 'dismissed',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.index'));

        $ids = $response->viewData('reports')->pluck('id');
        $this->assertTrue($ids->contains($pending->id));
        $this->assertCount(1, $ids);
    }

    public function test_admin_can_mark_a_report_reviewed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $report = Report::create([
            'request_id' => BloodRequest::factory()->create()->id,
            'reporter_id' => User::factory()->create()->id,
            'reason' => 'spam',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.reports.review', $report));

        $response->assertRedirect(route('admin.reports.index'));
        $report->refresh();
        $this->assertSame('reviewed', $report->status);
        $this->assertSame($admin->id, $report->reviewed_by);
        $this->assertNotNull($report->reviewed_at);
    }

    public function test_admin_can_dismiss_a_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $report = Report::create([
            'request_id' => BloodRequest::factory()->create()->id,
            'reporter_id' => User::factory()->create()->id,
            'reason' => 'spam',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->post(route('admin.reports.dismiss', $report));

        $this->assertSame('dismissed', $report->fresh()->status);
    }
}
