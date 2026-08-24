<?php

namespace Tests\Unit;

use App\Models\PushToken;
use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use App\Services\Fcm\FcmSender;
use App\Services\Fcm\FcmSendResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Mockery;
use Tests\TestCase;

class FcmChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_to_every_registered_device_and_prunes_invalid_tokens(): void
    {
        $user = User::factory()->create();
        $goodToken = PushToken::factory()->for($user)->create(['token' => 'good-token']);
        $badToken = PushToken::factory()->for($user)->create(['token' => 'bad-token']);

        $sender = Mockery::mock(FcmSender::class);
        $sender->shouldReceive('send')
            ->with('good-token', 'Title', 'Body', ['k' => 'v'])
            ->andReturn(FcmSendResult::Sent);
        $sender->shouldReceive('send')
            ->with('bad-token', 'Title', 'Body', ['k' => 'v'])
            ->andReturn(FcmSendResult::InvalidToken);

        $notification = new class extends Notification
        {
            public function toFcm(object $notifiable): array
            {
                return ['title' => 'Title', 'body' => 'Body', 'data' => ['k' => 'v']];
            }
        };

        (new FcmChannel($sender))->send($user, $notification);

        $this->assertDatabaseHas('push_tokens', ['id' => $goodToken->id]);
        $this->assertDatabaseMissing('push_tokens', ['id' => $badToken->id]);
    }

    public function test_it_does_nothing_when_the_notification_has_no_to_fcm_method(): void
    {
        $user = User::factory()->create();
        PushToken::factory()->for($user)->create();

        $sender = Mockery::mock(FcmSender::class);
        $sender->shouldNotReceive('send');

        $notification = new class extends Notification {};

        (new FcmChannel($sender))->send($user, $notification);
    }
}
