<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(['student', 'staff', 'faculty', 'verifier', 'admin'])],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * An admin can manage everyone else, but not lock themselves out —
     * demoting or deactivating your own account here would leave nobody
     * able to undo it without direct database access.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $target = $this->route('user');

            if ($target->id !== $this->user()->id) {
                return;
            }

            if (! $this->boolean('is_active')) {
                $validator->errors()->add('is_active', "You can't deactivate your own account.");
            }

            if ($this->input('role') !== 'admin') {
                $validator->errors()->add('role', "You can't change your own role away from admin.");
            }
        });
    }
}
