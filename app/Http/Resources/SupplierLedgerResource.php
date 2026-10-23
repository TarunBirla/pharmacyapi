<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierLedgerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'entry_date' => $this->entry_date->format('Y-m-d'),
            'entry_type' => $this->entry_type,
            'debit_amount' => (float)$this->debit_amount,
            'credit_amount' => (float)$this->credit_amount,
            'balance_after' => (float)$this->balance_after,
            'narration' => $this->narration,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
