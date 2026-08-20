<?php

namespace App\Http\Requests;

use App\Models\BloodRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBloodRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', BloodRequest::class);
    }

    public function rules(): array
    {
        return [
            'blood_group' => ['required', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'units_needed' => ['required', 'integer', 'min:1', 'max:20'],
            'hospital_name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'urgency' => ['required', Rule::in(['critical', 'within_24h', 'planned'])],
            'patient_context' => ['nullable', 'string', 'max:1000'],
            'contact_method' => ['required', 'string', 'max:255'],
        ];
    }
}
