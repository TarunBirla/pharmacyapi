<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'code' => $this->code,
            'business_type' => $this->business_type,
            'dl_number_20b' => $this->dl_number_20b,
            'dl_number_21b' => $this->dl_number_21b,
            'gstin' => $this->gstin,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'invoice_prefix' => $this->invoice_prefix,
            'next_invoice_number' => $this->next_invoice_number,
            'is_main_branch' => (bool)$this->is_main_branch,
            'status' => $this->status,
            'settings' => $this->settings,
            'warehouses' => WarehouseResource::collection($this->whenLoaded('warehouses')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
