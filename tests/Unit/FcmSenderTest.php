<?php

namespace Tests\Unit;

use App\Services\Fcm\FcmAccessTokenProvider;
use App\Services\Fcm\FcmSender;
use App\Services\Fcm\FcmSendResult;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FcmSenderTest extends TestCase
{
    private function senderWithToken(?string $token): FcmSender
    {
        $this->mock(FcmAccessTokenProvider::class, function ($mock) use ($token) {
            $mock->shouldReceive('token')->andReturn($token);
        });

        return $this->app->make(FcmSender::class);
    }

    public function test_returns_failed_when_no_access_token_is_available(): void
    {
        $result = $this->senderWithToken(null)->send('some-token', 'Title', 'Body');

        $this->assertSame(FcmSendResult::Failed, $result);
        Http::assertNothingSent();
    }

    public function test_sends_successfully(): void
    {
        config(['services.firebase.project_id' => 'test-project']);
        Http::fake(['fcm.googleapis.com/*' => Http::response(['name' => 'projects/x/messages/1'], 200)]);

        $result = $this->senderWithToken('fake-access-token')
            ->send('device-token', 'Title', 'Body', ['request_id' => 5]);

        $this->assertSame(FcmSendResult::Sent, $result);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://fcm.googleapis.com/v1/projects/test-project/messages:send'
                && $request->hasHeader('Authorization', 'Bearer fake-access-token')
                && $request['message']['token'] === 'device-token'
                && $request['message']['notification']['title'] === 'Title'
                && $request['message']['data']['request_id'] === '5';
        });
    }

    public function test_unregistered_token_is_reported_as_invalid(): void
    {
        config(['services.firebase.project_id' => 'test-project']);
        Http::fake(['fcm.googleapis.com/*' => Http::response(['error' => ['status' => 'UNREGISTERED']], 404)]);

        $result = $this->senderWithToken('fake-access-token')->send('dead-token', 'Title', 'Body');

        $this->assertSame(FcmSendResult::InvalidToken, $result);
    }

    public function test_transient_error_is_reported_as_failed_not_invalid(): void
    {
        config(['services.firebase.project_id' => 'test-project']);
        Http::fake(['fcm.googleapis.com/*' => Http::response(['error' => ['status' => 'INTERNAL']], 500)]);

        $result = $this->senderWithToken('fake-access-token')->send('some-token', 'Title', 'Body');

        $this->assertSame(FcmSendResult::Failed, $result);
    }
}
