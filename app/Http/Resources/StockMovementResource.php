<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => [
                'id' => $this->product_id,
                'name' => $this->product?->name,
                'code' => $this->product?->code,
            ],
            'batch' => [
                'id' => $this->product_batch_id,
                'batch_number' => $this->batch?->batch_number,
                'expiry_date' => $this->batch?->expiry_date?->format('Y-m-d'),
            ],
            'warehouse' => [
                'id' => $this->warehouse_id,
                'name' => $this->warehouse?->name,
            ],
            'movement_type' => $this->movement_type,
            'quantity' => (float)$this->quantity,
            'unit_cost' => (float)$this->unit_cost,
            'balance_before' => (float)$this->balance_before,
            'balance_after' => (float)$this->balance_after,
            'remarks' => $this->remarks,
            'created_by' => $this->creator?->name,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
