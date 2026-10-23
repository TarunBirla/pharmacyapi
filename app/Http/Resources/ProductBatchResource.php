<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'warehouse_id' => $this->warehouse_id,
            'product_name' => $this->product?->name,
            'warehouse_name' => $this->warehouse?->name,
            'batch_number' => $this->batch_number,
            'manufacturing_date' => $this->manufacturing_date?->format('Y-m-d'),
            'expiry_date' => $this->expiry_date->format('Y-m-d'),
            'mrp' => (float)$this->mrp,
            'purchase_rate' => (float)$this->purchase_rate,
            'sale_price' => (float)$this->sale_price,
            'wholesale_price' => (float)$this->wholesale_price,
            'quantity_received' => (float)$this->quantity_received,
            'quantity_available' => (float)$this->quantity_available,
            'quantity_reserved' => (float)$this->quantity_reserved,
            'quantity_quarantined' => (float)$this->quantity_quarantined,
            'quantity_damaged' => (float)$this->quantity_damaged,
            'quantity_expired' => (float)$this->quantity_expired,
            'status' => $this->status,
            'is_expired' => $this->isExpired(),
            'days_until_expiry' => $this->daysUntilExpiry(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
