<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('inventory.adjust') || $this->user()->hasPermission('purchase.create');
    }

    public function rules(): array
    {
        $warehouseId = $this->input('warehouse_id');
        $productId = $this->input('product_id');

        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_id' => ['required', 'exists:products,id'],
            'batch_number' => [
                'required',
                'string',
                'max:60',
                Rule::unique('product_batches')->where(fn ($q) => $q->where('warehouse_id', $warehouseId)->where('product_id', $productId)),
            ],
            'manufacturing_date' => ['nullable', 'date'],
            'expiry_date' => ['required', 'date', 'after:today'],
            'mrp' => ['required', 'numeric', 'min:0'],
            'purchase_rate' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'opening_quantity' => ['required', 'numeric', 'min:0'],
        ];
    }
}
