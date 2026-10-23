<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostSalesInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('sales.create') || $this->user()->hasPermission('sales.post');
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'prescription_id' => ['nullable', 'exists:prescriptions,id'],
            'patient_name' => ['nullable', 'string', 'max:150'],
            'doctor_name' => ['nullable', 'string', 'max:150'],
            'invoice_type' => ['nullable', 'in:retail,wholesale'],
            'invoice_date' => ['nullable', 'date'],
            'bill_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bill_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_batch_id' => ['required', 'exists:product_batches,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.free_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payments' => ['nullable', 'array'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0.01'],
            'payments.*.payment_mode' => ['required_with:payments', 'in:cash,card,upi,bank_transfer,credit_note'],
            'payments.*.transaction_reference' => ['nullable', 'string', 'max:100'],
        ];
    }
}
