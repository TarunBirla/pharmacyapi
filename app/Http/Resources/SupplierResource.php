<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'alternate_phone' => $this->alternate_phone,
            'gstin' => $this->gstin,
            'pan' => $this->pan,
            'dl_number_20b' => $this->dl_number_20b,
            'dl_number_21b' => $this->dl_number_21b,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'credit_period_days' => $this->credit_period_days,
            'credit_limit' => (float)$this->credit_limit,
            'current_balance' => (float)$this->current_balance,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_ifsc' => $this->bank_ifsc,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
