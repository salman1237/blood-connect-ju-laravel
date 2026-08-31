<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AdminDonorManagementTest extends TestCase
{
    use RefreshDatabase;

    private function importFile(array $rows): UploadedFile
    {
        $headings = [
            'name', 'email', 'role', 'gender', 'date_of_birth', 'blood_group',
            'hall', 'department', 'batch', 'phone', 'phone_has_whatsapp',
            'whatsapp_number', 'phone_visibility',
        ];

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headings, null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'donor-import').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'import.xlsx', null, null, true);
    }

    public function test_admin_can_view_the_add_donor_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.donors.create'));

        $response->assertOk();
        $response->assertSee('Add donor');
    }

    public function test_admin_can_view_the_bulk_import_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.donors.import'));

        $response->assertOk();
        $response->assertSee('Download template');
    }

    public function test_non_admin_cannot_access_the_add_donor_form(): void
    {
        $verifier = User::factory()->create(['role' => 'verifier']);

        $response = $this->actingAs($verifier)->get(route('admin.donors.create'));

        $response->assertForbidden();
    }

    public function test_admin_can_manually_register_a_donor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.donors.store'), [
            'name' => 'Rahim Uddin',
            'email' => 'rahim.manual@example.com',
            'role' => 'student',
            'gender' => 'male',
            'date_of_birth' => '2000-01-15',
            'blood_group' => 'O+',
            'hall' => 'Al Beruni Hall',
            'department' => 'Computer Science and Engineering',
            'batch' => '2020-21',
            'phone' => '01712345678',
            'phone_has_whatsapp' => '1',
            'phone_visibility' => 'admin_only',
            'is_available' => '1',
        ]);

        $response->assertSessionHasNoErrors();

        $donor = User::where('email', 'rahim.manual@example.com')->firstOrFail();
        $this->assertSame('student', $donor->role);
        $this->assertSame('admin_only', $donor->phone_visibility);
        $this->assertNotNull($donor->email_verified_at);
        $this->assertTrue($donor->is_active);
        $this->assertNotNull($donor->donorProfile);
        $this->assertSame('O+', $donor->donorProfile->blood_group);
    }

    public function test_manual_registration_requires_a_unique_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($admin)->post(route('admin.donors.store'), [
            'name' => 'Rahim Uddin',
            'email' => 'taken@example.com',
            'role' => 'staff',
            'gender' => 'male',
            'date_of_birth' => '2000-01-15',
            'blood_group' => 'O+',
            'department' => 'Physics',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_download_the_import_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.donors.import.template'));

        $response->assertOk();
    }

    public function test_admin_can_bulk_import_donors_and_skipped_rows_are_reported(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $file = $this->importFile([
            ['Karim Ahmed', 'karim.import@example.com', 'student', 'male', '2000-01-15', 'O+', 'Al Beruni Hall', 'Computer Science and Engineering', '2020-21', '01712345678', 1, '', 'public'],
            ['', 'invalid-row@example.com', 'student', 'male', '2000-01-15', 'O+', 'Al Beruni Hall', 'Computer Science and Engineering', '2020-21', '', 1, '', 'public'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.donors.import.store'), ['file' => $file]);

        $response->assertRedirect(route('admin.donors.import'));
        $response->assertSessionHas('importedCount', 1);

        $this->assertDatabaseHas('users', ['email' => 'karim.import@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'invalid-row@example.com']);

        $imported = User::where('email', 'karim.import@example.com')->firstOrFail();
        $this->assertNotNull($imported->donorProfile);
        $this->assertSame('O+', $imported->donorProfile->blood_group);
    }

    public function test_duplicate_email_in_import_is_skipped_not_overwritten(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $existing = User::factory()->create(['name' => 'Original Name', 'email' => 'existing@example.com']);

        $file = $this->importFile([
            ['Overwritten Name', 'existing@example.com', 'student', 'male', '2000-01-15', 'O+', 'Al Beruni Hall', 'Computer Science and Engineering', '2020-21', '01712345678', 1, '', 'public'],
        ]);

        $this->actingAs($admin)->post(route('admin.donors.import.store'), ['file' => $file]);

        $this->assertSame('Original Name', $existing->fresh()->name);
    }
}
