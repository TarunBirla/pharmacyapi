<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashierShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shift_code' => $this->shift_code,
            'cashier' => [
                'id' => $this->user_id,
                'name' => $this->cashier?->name,
            ],
            'started_at' => $this->started_at->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'opening_cash' => (float)$this->opening_cash,
            'closing_cash' => (float)$this->closing_cash,
            'expected_cash' => (float)$this->expected_cash,
            'discrepancy' => (float)$this->discrepancy,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
