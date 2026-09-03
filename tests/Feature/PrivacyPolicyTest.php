<?php

namespace Tests\Feature;

use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    public function test_privacy_policy_page_is_reachable(): void
    {
        $response = $this->get('/privacy');

        $response->assertOk();
        $response->assertSee('Privacy Policy');
    }

    public function test_privacy_policy_covers_account_deletion(): void
    {
        $response = $this->get('/privacy');

        $response->assertOk();
        $response->assertSee('Account &amp; data deletion', false);
        $response->assertSee('Delete account');
        $response->assertSee('hard delete', false);
    }
}
