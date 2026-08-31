<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Mirrors AdminCreateDonorRequest's shape row by row. A duplicate email (or
 * any other invalid row) is skipped and reported rather than aborting the
 * whole file — see SkipsOnFailure/SkipsFailures — and never silently
 * overwrites an existing account.
 */
class DonorsImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use Importable, SkipsFailures;

    private int $importedCount = 0;

    public function model(array $row): User
    {
        $user = User::forceCreate([
            'name' => $row['name'],
            'email' => $row['email'],
            'password' => Str::password(32),
            'role' => $row['role'],
            'email_verified_at' => now(),
            'is_active' => true,
            'email_notifications_enabled' => true,
        ]);

        $user->updateDonorProfile([
            'blood_group' => $row['blood_group'],
            'gender' => $row['gender'],
            'date_of_birth' => $this->normalizeDate($row['date_of_birth']),
            'department' => $row['department'],
            'hall' => $row['hall'] ?? null,
            'batch' => $row['batch'] ?? null,
            'phone' => $row['phone'] ?? null,
            'phone_has_whatsapp' => (bool) ($row['phone_has_whatsapp'] ?? true),
            'whatsapp_number' => $row['whatsapp_number'] ?? null,
            'phone_visibility' => $row['phone_visibility'] ?? 'public',
        ]);

        $this->importedCount++;

        return $user;
    }

    public function rules(): array
    {
        $allDepartments = collect(config('juniv.departments'))->flatten()->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['student', 'staff', 'faculty'])],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['required'],
            'blood_group' => ['required', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'department' => ['required', Rule::in($allDepartments)],
            'hall' => ['nullable', Rule::in(config('juniv.halls'))],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone_visibility' => ['nullable', Rule::in(['public', 'admin_only'])],
        ];
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }

    public function failureSummaries(): array
    {
        return collect($this->failures())
            ->map(fn ($failure) => "Row {$failure->row()}: ".implode(' ', $failure->errors()))
            ->all();
    }

    /**
     * Excel stores dates as numeric serials once a cell is formatted as a
     * date (easy to happen by accident when someone edits the template in
     * Excel/Sheets) — convert those back to a Y-m-d string so
     * updateDonorProfile()'s 'date' validation rule doesn't choke on a raw
     * serial number.
     */
    private function normalizeDate(mixed $value): ?string
    {
        if (is_numeric($value)) {
            return Date::excelToDateTimeObject($value)->format('Y-m-d');
        }

        return $value !== null ? (string) $value : null;
    }
}
