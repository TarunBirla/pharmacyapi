<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('products.create');
    }

    public function rules(): array
    {
        $orgId = $this->user()->organization_id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'generic_composition_id' => ['nullable', 'exists:generic_compositions,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'manufacturer_id' => ['nullable', 'exists:manufacturers,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'dosage_form' => ['required', 'string', 'max:50'],
            'strength' => ['nullable', 'string', 'max:50'],
            'hsn_code' => ['nullable', 'string', 'max:20'],
            'gst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'primary_unit_id' => ['required', 'exists:units,id'],
            'secondary_unit_id' => ['nullable', 'exists:units,id'],
            'unit_conversion_factor' => ['nullable', 'numeric', 'min:1'],
            'packaging_type' => ['nullable', 'string', 'max:100'],
            'rack_location' => ['nullable', 'string', 'max:50'],
            'shelf_location' => ['nullable', 'string', 'max:50'],
            'prescription_type' => ['required', 'in:otc,schedule_h,schedule_h1,schedule_x,narcotic'],
            'storage_temperature' => ['required', 'in:room_temperature,cold_chain_2_8c,cool_dark_place'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'barcodes' => ['nullable', 'array'],
            'barcodes.*' => ['string', 'max:60'],
            'compositions' => ['nullable', 'array'],
            'compositions.*.active_ingredient' => ['required_with:compositions', 'string'],
            'compositions.*.strength' => ['required_with:compositions', 'numeric'],
            'compositions.*.unit' => ['nullable', 'string', 'max:20'],
        ];
    }
}
