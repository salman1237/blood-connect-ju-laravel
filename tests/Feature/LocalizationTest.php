<?php

namespace Tests\Feature;

use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_switch_locale_and_it_persists_in_session(): void
    {
        $this->post('/locale/bn');

        $this->assertSame('bn', session('locale'));

        $response = $this->get('/');

        $response->assertOk();
        $this->assertSame('bn', app()->getLocale());
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $response = $this->post('/locale/fr');

        $response->assertNotFound();
    }

    public function test_switching_locale_persists_to_a_logged_in_users_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/locale/bn');

        $this->assertSame('bn', $user->fresh()->locale);
    }

    public function test_logged_in_users_saved_locale_applies_on_a_fresh_session(): void
    {
        // '/' redirects a logged-in user straight to the dashboard, so check
        // the locale applied via that page rather than the landing page.
        $user = User::factory()->create(['role' => 'staff', 'department' => 'Physics', 'locale' => 'bn']);
        DonorProfile::factory()->for($user)->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $this->assertSame('bn', app()->getLocale());
    }

    public function test_landing_page_renders_in_bangla_after_switching(): void
    {
        $this->post('/locale/bn');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('ক্যাম্পাসে কারো রক্তের প্রয়োজন হলে', false);
    }
}
