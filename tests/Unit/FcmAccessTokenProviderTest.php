<?php

namespace Tests\Unit;

use App\Services\Fcm\FcmAccessTokenProvider;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FcmAccessTokenProviderTest extends TestCase
{
    public function test_returns_null_when_no_project_id_is_configured(): void
    {
        config(['services.firebase.project_id' => null]);

        $this->assertNull((new FcmAccessTokenProvider)->token());
        $this->assertNull(Cache::get('fcm.access_token'));
    }

    public function test_returns_null_when_the_credentials_file_does_not_exist(): void
    {
        config(['services.firebase.project_id' => 'test-project']);
        config(['services.firebase.credentials' => '/path/does/not/exist.json']);

        $this->assertNull((new FcmAccessTokenProvider)->token());
        $this->assertNull(Cache::get('fcm.access_token'));
    }
}
