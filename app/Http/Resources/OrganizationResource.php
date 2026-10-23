<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'drug_license_number' => $this->drug_license_number,
            'gstin' => $this->gstin,
            'pan' => $this->pan,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'logo_url' => $this->logo_url,
            'status' => $this->status,
            'settings' => $this->settings,
            'branches_count' => $this->whenCounted('branches'),
            'users_count' => $this->whenCounted('users'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
