<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\Rules;

class RegistrationValidation
{
    /**
     * The rules a new account must satisfy, shared between the web
     * registration form and the API registration endpoint — kept in one
     * place so a mobile-only or web-only drift (e.g. a looser DOB range on
     * one side) can't happen by accident.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:student,staff,faculty'],
            'gender' => ['required', 'in:male,female,other'],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1900-01-01'],
        ];
    }
}
