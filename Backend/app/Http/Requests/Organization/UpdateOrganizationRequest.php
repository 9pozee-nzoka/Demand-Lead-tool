<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only owner/admin may update organization settings
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name'     => ['sometimes', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:100'],
            'country'  => ['nullable', 'string', 'size:2'],
            'timezone' => ['nullable', 'string', 'max:60'],
        ];
    }
}
