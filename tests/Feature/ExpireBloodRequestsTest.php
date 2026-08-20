<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireBloodRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_expires_open_requests_past_their_expiry_time(): void
    {
        $stale = BloodRequest::factory()->create([
            'status' => 'open',
            'expires_at' => now()->subMinute(),
        ]);

        $this->artisan('requests:expire')->assertExitCode(0);

        $this->assertSame('expired', $stale->fresh()->status);
    }

    public function test_leaves_open_requests_that_have_not_expired_yet(): void
    {
        $fresh = BloodRequest::factory()->create([
            'status' => 'open',
            'expires_at' => now()->addHour(),
        ]);

        $this->artisan('requests:expire');

        $this->assertSame('open', $fresh->fresh()->status);
    }

    public function test_leaves_already_fulfilled_requests_alone(): void
    {
        $fulfilled = BloodRequest::factory()->fulfilled()->create([
            'expires_at' => now()->subDay(),
        ]);

        $this->artisan('requests:expire');

        $this->assertSame('fulfilled', $fulfilled->fresh()->status);
    }
}
