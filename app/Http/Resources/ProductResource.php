<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'generic_name' => $this->generic_name,
            'brand' => $this->whenLoaded('brand', fn () => [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
            ]),
            'manufacturer' => $this->whenLoaded('manufacturer', fn () => [
                'id' => $this->manufacturer->id,
                'name' => $this->manufacturer->name,
            ]),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'generic_composition' => $this->whenLoaded('genericComposition', fn () => [
                'id' => $this->genericComposition->id,
                'name' => $this->genericComposition->name,
            ]),
            'dosage_form' => $this->dosage_form,
            'strength' => $this->strength,
            'hsn_code' => $this->hsn_code,
            'gst_rate' => (float)$this->gst_rate,
            'primary_unit' => $this->whenLoaded('primaryUnit', fn () => [
                'id' => $this->primaryUnit->id,
                'name' => $this->primaryUnit->name,
                'code' => $this->primaryUnit->code,
            ]),
            'secondary_unit' => $this->whenLoaded('secondaryUnit', fn () => [
                'id' => $this->secondaryUnit?->id,
                'name' => $this->secondaryUnit?->name,
                'code' => $this->secondaryUnit?->code,
            ]),
            'unit_conversion_factor' => (float)$this->unit_conversion_factor,
            'packaging_type' => $this->packaging_type,
            'rack_location' => $this->rack_location,
            'shelf_location' => $this->shelf_location,
            'prescription_type' => $this->prescription_type,
            'storage_temperature' => $this->storage_temperature,
            'min_stock_level' => (float)$this->min_stock_level,
            'reorder_level' => (float)$this->reorder_level,
            'reorder_quantity' => (float)$this->reorder_quantity,
            'is_active' => (bool)$this->is_active,
            'is_narcotic' => (bool)$this->is_narcotic,
            'barcodes' => $this->whenLoaded('barcodes', fn () => $this->barcodes->pluck('barcode')),
            'compositions' => $this->whenLoaded('compositions', fn () => $this->compositions->map(fn ($c) => [
                'active_ingredient' => $c->active_ingredient,
                'strength' => (float)$c->strength,
                'unit' => $c->unit,
            ])),
            'total_stock' => $this->when(isset($this->batches_sum_quantity_available), (float)$this->batches_sum_quantity_available),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
