<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'supplier' => [
                'id' => $this->supplier_id,
                'name' => $this->supplier?->name,
                'gstin' => $this->supplier?->gstin,
            ],
            'warehouse' => [
                'id' => $this->warehouse_id,
                'name' => $this->warehouse?->name,
            ],
            'invoice_date' => $this->invoice_date->format('Y-m-d'),
            'due_date' => $this->due_date->format('Y-m-d'),
            'subtotal' => (float)$this->subtotal,
            'discount_amount' => (float)$this->discount_amount,
            'taxable_amount' => (float)$this->taxable_amount,
            'cgst_amount' => (float)$this->cgst_amount,
            'sgst_amount' => (float)$this->sgst_amount,
            'igst_amount' => (float)$this->igst_amount,
            'total_tax' => (float)$this->total_tax,
            'additional_charges' => (float)$this->additional_charges,
            'round_off' => (float)$this->round_off,
            'net_amount' => (float)$this->net_amount,
            'paid_amount' => (float)$this->paid_amount,
            'due_amount' => (float)$this->due_amount,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'batch_number' => $item->batch_number,
                'expiry_date' => $item->expiry_date->format('Y-m-d'),
                'quantity' => (float)$item->quantity,
                'free_quantity' => (float)$item->free_quantity,
                'mrp' => (float)$item->mrp,
                'unit_cost' => (float)$item->unit_cost,
                'taxable_value' => (float)$item->taxable_value,
                'gst_rate' => (float)$item->gst_rate,
                'total_amount' => (float)$item->total_amount,
            ])),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
