<?php

namespace Tests\Unit;

use App\Models\BloodRequest;
use App\Models\RequestResponse;
use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use App\Notifications\DonationConfirmationPending;
use App\Notifications\DonationConfirmed;
use App\Notifications\DonorSelected;
use App\Notifications\EligibleDonorReminder;
use App\Notifications\NewMatchingRequest;
use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use App\Notifications\RequestRejected;
use App\Notifications\RequestResponded;
use App\Notifications\RequestVerified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every notification this app ever fires (see the exhaustive `->notify(new
 * ...)` / `Notification::send(...)` call sites) pushes via FcmChannel in
 * addition to whatever else it already used — this is the single place that
 * checks all eight stay wired, so a future edit to one notification can't
 * silently drop push support without a test noticing.
 */
class NotificationFcmWiringTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array{0: object, 1: bool}>
     */
    public static function notifications(): array
    {
        return [
            'RequestResponded' => [fn () => new RequestResponded(RequestResponse::factory()->create()), true],
            'DonorSelected' => [fn () => new DonorSelected(RequestResponse::factory()->create()), true],
            'NewMatchingRequest' => [fn () => new NewMatchingRequest(BloodRequest::factory()->create()), true],
            'RequestVerified' => [fn () => new RequestVerified(BloodRequest::factory()->create()), true],
            'RequestRejected' => [fn () => new RequestRejected(BloodRequest::factory()->create()), true],
            'DonationConfirmed' => [fn () => new DonationConfirmed(RequestResponse::factory()->create()), true],
            'DonationConfirmationPending' => [fn () => new DonationConfirmationPending(RequestResponse::factory()->create()), true],
            'EligibleDonorReminder' => [fn () => new EligibleDonorReminder(2), false],
            'QueuedVerifyEmail' => [fn () => new QueuedVerifyEmail, false],
            'QueuedResetPassword' => [fn () => new QueuedResetPassword('a-token'), false],
        ];
    }

    #[DataProvider('notifications')]
    public function test_notification_is_wired_for_push(\Closure $make, bool $requestIdExpected): void
    {
        $user = User::factory()->create();
        $notification = $make();

        $channels = $notification->via($user);
        $this->assertContains(FcmChannel::class, $channels, get_class($notification).'::via() must include FcmChannel');

        $this->assertTrue(method_exists($notification, 'toFcm'), get_class($notification).' must define toFcm()');

        $payload = $notification->toFcm($user);
        $this->assertIsString($payload['title'] ?? null);
        $this->assertNotSame('', $payload['title']);
        $this->assertIsString($payload['body'] ?? null);
        $this->assertNotSame('', $payload['body']);
        $this->assertIsArray($payload['data'] ?? null);

        if ($requestIdExpected) {
            $this->assertArrayHasKey('request_id', $payload['data']);
        }
    }
}
