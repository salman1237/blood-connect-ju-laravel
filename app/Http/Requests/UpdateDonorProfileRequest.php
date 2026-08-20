<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared by the onboarding wizard (first-time setup) and the profile page
 * (editing afterward) so the two never drift out of sync with each other.
 */
class UpdateDonorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Falls back to the current role so hall/batch requirements are
        // still evaluated correctly even when the role field isn't part of
        // this particular submission (verifiers/admins never see it).
        $role = $this->input('role', $this->user()->role);
        $isStudent = $role === 'student';
        $allDepartments = collect(config('juniv.departments'))->flatten()->all();

        return [
            'blood_group' => ['required', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'role' => [$this->user()->canSelfServiceRole() ? 'required' : 'nullable', Rule::in(['student', 'staff', 'faculty'])],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'department' => ['required', Rule::in($allDepartments)],
            'hall' => [$isStudent ? 'required' : 'nullable', Rule::in(config('juniv.halls'))],
            'batch' => [$isStudent ? 'required' : 'nullable', Rule::in(User::batchOptions())],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone_has_whatsapp' => ['boolean'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'is_available' => ['boolean'],
            'last_donation_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
