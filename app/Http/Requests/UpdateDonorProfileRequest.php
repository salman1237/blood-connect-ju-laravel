<?php

namespace App\Http\Requests;

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
        $isStudent = $this->user()->role === 'student';
        $allDepartments = collect(config('juniv.departments'))->flatten()->all();

        return [
            'blood_group' => ['required', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'department' => ['required', Rule::in($allDepartments)],
            'hall' => [$isStudent ? 'required' : 'nullable', Rule::in(config('juniv.halls'))],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_available' => ['boolean'],
            'last_donation_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
