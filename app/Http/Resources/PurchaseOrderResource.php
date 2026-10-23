<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'po_number' => $this->po_number,
            'supplier' => [
                'id' => $this->supplier_id,
                'name' => $this->supplier?->name,
                'code' => $this->supplier?->code,
            ],
            'order_date' => $this->order_date->format('Y-m-d'),
            'expected_delivery_date' => $this->expected_delivery_date?->format('Y-m-d'),
            'subtotal' => (float)$this->subtotal,
            'discount_amount' => (float)$this->discount_amount,
            'tax_amount' => (float)$this->tax_amount,
            'total_amount' => (float)$this->total_amount,
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'unit' => $item->unit?->code,
                'quantity_ordered' => (float)$item->quantity_ordered,
                'quantity_received' => (float)$item->quantity_received,
                'unit_cost' => (float)$item->unit_cost,
                'discount_percent' => (float)$item->discount_percent,
                'tax_rate' => (float)$item->tax_rate,
                'total_amount' => (float)$item->total_amount,
            ])),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
