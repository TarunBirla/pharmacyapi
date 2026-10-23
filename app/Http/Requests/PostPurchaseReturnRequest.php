<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostPurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('purchase.returns');
    }

    public function rules(): array
    {
        return [
            'purchase_invoice_id' => ['required', 'exists:purchase_invoices,id'],
            'reason' => ['required', 'in:damaged,expired,defective,excess,recall,rate_difference'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_batch_id' => ['required', 'exists:product_batches,id'],
            'items.*.quantity_returned' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
