<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'debit_note_number' => $this->debit_note_number,
            'supplier' => [
                'id' => $this->supplier_id,
                'name' => $this->supplier?->name,
            ],
            'invoice_id' => $this->purchase_invoice_id,
            'return_date' => $this->return_date->format('Y-m-d'),
            'reason' => $this->reason,
            'subtotal' => (float)$this->subtotal,
            'tax_amount' => (float)$this->tax_amount,
            'total_amount' => (float)$this->total_amount,
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'batch_number' => $item->batch?->batch_number,
                'quantity_returned' => (float)$item->quantity_returned,
                'unit_cost' => (float)$item->unit_cost,
                'tax_amount' => (float)$item->tax_amount,
                'total_amount' => (float)$item->total_amount,
                'reason' => $item->reason,
            ])),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
