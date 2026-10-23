<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'is_default' => (bool)$this->is_default,
            'is_active' => (bool)$this->is_active,
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
