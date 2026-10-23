<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('users.manage') || $this->user()->hasRole(['super_admin', 'organization_owner']);
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:25'],
            'pharmacist_reg_number' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
            'role_slugs' => ['required', 'array', 'min:1'],
            'role_slugs.*' => ['string', 'exists:roles,slug'],
            'assigned_branch_ids' => ['nullable', 'array'],
            'assigned_branch_ids.*' => ['exists:branches,id'],
        ];
    }
}
