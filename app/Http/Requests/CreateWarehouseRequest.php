<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('warehouses.manage') || $this->user()->hasRole(['super_admin', 'organization_owner', 'branch_manager']);
    }

    public function rules(): array
    {
        $branchId = $this->input('branch_id', $this->user()->branch_id);

        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('warehouses')->where(fn ($query) => $query->where('branch_id', $branchId)),
            ],
            'type' => ['required', 'in:retail_dispensary,main_godown,cold_storage,quarantine,damaged'],
            'is_default' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}
