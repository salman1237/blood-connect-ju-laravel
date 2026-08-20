<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_user_management(): void
    {
        $verifier = User::factory()->create(['role' => 'verifier']);

        $response = $this->actingAs($verifier)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_index_searches_by_name_or_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $match = User::factory()->create(['name' => 'Rahim Uddin', 'email' => 'rahim@juniv.edu']);
        User::factory()->create(['name' => 'Karim Hasan', 'email' => 'karim@juniv.edu']);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Rahim']));

        $ids = $response->viewData('users')->pluck('id');
        $this->assertTrue($ids->contains($match->id));
        $this->assertCount(1, $ids);
    }

    public function test_index_filters_by_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $verifier = User::factory()->create(['role' => 'verifier']);
        User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'verifier']));

        $ids = $response->viewData('users')->pluck('id');
        $this->assertTrue($ids->contains($verifier->id));
        $this->assertFalse($ids->contains($admin->id));
    }

    public function test_admin_can_change_a_users_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $student), [
            'role' => 'verifier',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertSame('verifier', $student->fresh()->role);
    }

    public function test_admin_can_deactivate_a_user_via_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)->put(route('admin.users.update', $student), [
            'role' => 'student',
            'is_active' => '0',
        ]);

        $this->assertFalse($student->fresh()->is_active);
    }

    public function test_admin_can_deactivate_a_user_via_destroy(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $student));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertFalse($student->fresh()->is_active);
        $this->assertNotNull($student->fresh(), 'destroy should deactivate, not delete the row');
    }

    public function test_admin_cannot_deactivate_their_own_account_via_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'role' => 'admin',
            'is_active' => '0',
        ]);

        $response->assertSessionHasErrors('is_active');
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_cannot_demote_their_own_role_via_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'role' => 'student',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_admin_cannot_deactivate_their_own_account_via_destroy(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $response->assertForbidden();
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_can_view_a_users_detail_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $student));

        $response->assertOk();
        $response->assertSee($student->name);
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['is_active' => false, 'password' => bcrypt('password')]);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivating_a_logged_in_user_ends_their_session_on_next_request(): void
    {
        $user = User::factory()->create(['role' => 'staff', 'department' => 'Physics']);
        $user->update(['is_active' => false]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
