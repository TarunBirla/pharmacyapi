<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('branches.manage') || $this->user()->hasRole(['super_admin', 'organization_owner']);
    }

    public function rules(): array
    {
        $orgId = $this->user()->organization_id ?? $this->input('organization_id');

        return [
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('branches')->where(fn ($query) => $query->where('organization_id', $orgId)),
            ],
            'business_type' => ['required', 'in:retail,wholesale,both'],
            'dl_number_20b' => ['nullable', 'string', 'max:100'],
            'dl_number_21b' => ['nullable', 'string', 'max:100'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'invoice_prefix' => ['nullable', 'string', 'max:10'],
            'is_main_branch' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
