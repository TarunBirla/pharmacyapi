<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('customers.manage') || $this->user()->hasPermission('sales.create');
    }

    public function rules(): array
    {
        $orgId = $this->user()->organization_id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('customers')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:retail,wholesale_dealer,hospital,institutional'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'pan' => ['nullable', 'string', 'max:20'],
            'dl_number' => ['nullable', 'string', 'max:50'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_period_days' => ['nullable', 'integer', 'min:0'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ];
    }
}
