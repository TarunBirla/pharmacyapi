<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostPurchaseInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('purchase.post');
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'goods_receipt_id' => ['nullable', 'exists:goods_receipts,id'],
            'invoice_number' => ['required', 'string', 'max:100'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'additional_charges' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.batch_number' => ['required', 'string', 'max:60'],
            'items.*.expiry_date' => ['required', 'date', 'after:invoice_date'],
            'items.*.manufacturing_date' => ['nullable', 'date', 'before_or_equal:invoice_date'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.free_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.mrp' => ['required', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.gst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
