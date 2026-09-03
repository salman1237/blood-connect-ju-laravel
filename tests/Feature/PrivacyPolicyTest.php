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

    public function test_privacy_policy_links_to_the_account_deletion_page(): void
    {
        $response = $this->get('/privacy');

        $response->assertOk();
        $response->assertSee(route('account-deletion'), false);
    }

    public function test_account_deletion_page_is_reachable(): void
    {
        $response = $this->get('/account-deletion');

        $response->assertOk();
        $response->assertSee('Account Deletion');
        $response->assertSee('Delete account');
        $response->assertSee('hard delete', false);
    }

    public function test_account_deletion_page_links_back_to_the_privacy_policy(): void
    {
        $response = $this->get('/account-deletion');

        $response->assertOk();
        $response->assertSee(route('privacy'), false);
    }
}
