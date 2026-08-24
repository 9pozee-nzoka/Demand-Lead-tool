<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $targetUser = $this->route('user');
        $authUser   = $this->user();

        // Users can update their own profile; admins can update anyone in the org
        return $authUser->id === $targetUser->id || $authUser->isAdmin();
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name'   => ['sometimes', 'string', 'max:255'],
            'phone'  => ['nullable', 'string', 'max:30'],
            'email'  => ['sometimes', 'email', Rule::unique('users')->ignore($userId)],
            'role'   => ['sometimes', Rule::in(['admin', 'analyst', 'marketing', 'sales', 'viewer'])],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
