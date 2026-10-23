<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:organizations,code'],
            'drug_license_number' => ['nullable', 'string', 'max:100'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'pan' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:25'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:50'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
