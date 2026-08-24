<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Organization
            'organization_name' => ['required', 'string', 'max:255'],
            'industry'          => ['nullable', 'string', 'max:100'],
            'country'           => ['nullable', 'string', 'size:2'],
            'timezone'          => ['nullable', 'string', 'max:60'],

            // Owner user
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
