<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_site_settings(): void
    {
        $verifier = User::factory()->create(['role' => 'verifier']);

        $response = $this->actingAs($verifier)->get(route('admin.settings.edit'));

        $response->assertForbidden();
    }

    public function test_the_migration_seeds_the_real_org_credit(): void
    {
        $setting = AppSetting::current();

        $this->assertSame("Jahangirnagar University Central Students' Union (JUCSU)", $setting->funded_by);
        $this->assertSame('Badhan, Jahangirnagar University', $setting->maintained_by);
        $this->assertNull($setting->logo_url);
    }

    public function test_admin_can_update_the_org_credit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'funded_by' => 'A Different Funder',
            'maintained_by' => 'A Different Maintainer',
        ]);

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertSame('A Different Funder', AppSetting::current()->funded_by);
        $this->assertSame('A Different Maintainer', AppSetting::current()->maintained_by);
    }

    public function test_clearing_a_field_hides_that_line(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'funded_by' => '',
            'maintained_by' => 'Still Set',
        ]);

        $this->assertNull(AppSetting::current()->funded_by);

        // Landing redirects a logged-in user straight to /dashboard, so
        // check the still-admin-authenticated Settings page instead.
        $response = $this->get(route('settings.edit'));
        $response->assertDontSee('Implemented &amp; funded by', false);
        $response->assertSee('Still Set');
    }

    public function test_admin_can_upload_and_remove_a_logo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $upload = $this->actingAs($admin)->post(route('admin.settings.logo.update'), [
            'photo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $upload->assertRedirect(route('admin.settings.edit'));
        $this->assertNotNull(AppSetting::current()->logo_url);

        $remove = $this->actingAs($admin)->delete(route('admin.settings.logo.destroy'));

        $remove->assertRedirect(route('admin.settings.edit'));
        $this->assertNull(AppSetting::current()->logo_url);
    }

    public function test_landing_page_shows_the_org_credit(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('JUCSU', false);
        $response->assertSee('Badhan, Jahangirnagar University');
    }

    public function test_settings_page_shows_the_org_credit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.edit'));

        $response->assertOk();
        $response->assertSee('JUCSU', false);
        $response->assertSee('Badhan, Jahangirnagar University');
    }
}
