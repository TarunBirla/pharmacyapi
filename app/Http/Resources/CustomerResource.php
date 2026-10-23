<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'phone' => $this->phone,
            'email' => $this->email,
            'gstin' => $this->gstin,
            'pan' => $this->pan,
            'dl_number' => $this->dl_number,
            'credit_limit' => (float)$this->credit_limit,
            'credit_period_days' => $this->credit_period_days,
            'current_balance' => (float)$this->current_balance,
            'loyalty_points' => $this->loyalty_points,
            'status' => $this->status,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
