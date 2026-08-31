<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Account-level shape borrowed from RegistrationValidation (minus
 * password — an admin-created account gets a random one, see
 * AdminDonorController::store()) combined with the donor-profile shape
 * from UpdateDonorProfileRequest, so an admin-entered donor ends up
 * validated the same way a self-registered one would be.
 */
class AdminCreateDonorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $isStudent = $this->input('role') === 'student';
        $allDepartments = collect(config('juniv.departments'))->flatten()->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', Rule::in(['student', 'staff', 'faculty'])],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'blood_group' => ['required', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'department' => ['required', Rule::in($allDepartments)],
            'hall' => [$isStudent ? 'required' : 'nullable', Rule::in(config('juniv.halls'))],
            'batch' => [$isStudent ? 'required' : 'nullable', Rule::in(User::batchOptions())],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone_has_whatsapp' => ['boolean'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'phone_visibility' => ['nullable', Rule::in(['public', 'admin_only'])],
            'is_available' => ['boolean'],
            'last_donation_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
