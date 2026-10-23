<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostSalesReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('returns.create') || $this->user()->hasPermission('returns.approve');
    }

    public function rules(): array
    {
        return [
            'sales_invoice_id' => ['required', 'exists:sales_invoices,id'],
            'reason' => ['required', 'string', 'max:255'],
            'refund_mode' => ['nullable', 'in:cash_refund,credit_note'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sales_invoice_item_id' => ['required', 'exists:sales_invoice_items,id'],
            'items.*.quantity_returned' => ['required', 'numeric', 'min:0.001'],
            'items.*.condition' => ['nullable', 'in:restockable,damaged,expired'],
        ];
    }
}
