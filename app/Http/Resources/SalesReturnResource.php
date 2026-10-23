<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'credit_note_number' => $this->credit_note_number,
            'invoice_id' => $this->sales_invoice_id,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
            ]),
            'return_date' => $this->return_date->format('Y-m-d'),
            'reason' => $this->reason,
            'subtotal' => (float)$this->subtotal,
            'tax_amount' => (float)$this->tax_amount,
            'total_amount' => (float)$this->total_amount,
            'refund_mode' => $this->refund_mode,
            'status' => $this->status,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'batch_number' => $item->batch?->batch_number,
                'quantity_returned' => (float)$item->quantity_returned,
                'unit_price' => (float)$item->unit_price,
                'tax_amount' => (float)$item->tax_amount,
                'total_amount' => (float)$item->total_amount,
                'condition' => $item->condition,
            ])),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
