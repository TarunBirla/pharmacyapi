<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Str;

class AiInvoiceOcrService
{
    /**
     * Parse unstructured or semi-structured supplier invoice payload and map to product catalog.
     */
    public function parseSupplierInvoice(array $payload, int $organizationId): array
    {
        $supplier = null;
        if (!empty($payload['supplier_gstin'])) {
            $supplier = Supplier::where('organization_id', $organizationId)
                ->where('gstin', $payload['supplier_gstin'])
                ->first();
        }

        if (!$supplier && !empty($payload['supplier_name'])) {
            $supplier = Supplier::where('organization_id', $organizationId)
                ->where('name', 'like', '%' . $payload['supplier_name'] . '%')
                ->first();
        }

        $parsedItems = [];
        $rawItems = $payload['items'] ?? [];

        foreach ($rawItems as $raw) {
            $name = trim($raw['medicine_name'] ?? '');
            $code = trim($raw['code'] ?? '');
            
            // Fuzzy search against product catalog
            $product = null;
            $confidence = 0.0;

            if ($code) {
                $product = Product::where('organization_id', $organizationId)->where('code', $code)->first();
                if ($product) $confidence = 100.0;
            }

            if (!$product && $name) {
                // Exact match
                $product = Product::where('organization_id', $organizationId)->where('name', $name)->first();
                if ($product) {
                    $confidence = 98.0;
                } else {
                    // Like match
                    $product = Product::where('organization_id', $organizationId)
                        ->where('name', 'like', '%' . substr($name, 0, 8) . '%')
                        ->first();
                    if ($product) $confidence = 85.0;
                }
            }

            $parsedItems[] = [
                'raw_name' => $name,
                'matched_product_id' => $product?->id,
                'matched_product_name' => $product?->name,
                'match_confidence' => $confidence,
                'is_catalog_match' => $product !== null,
                'batch_number' => strtoupper($raw['batch_number'] ?? 'BATCH-' . Str::random(6)),
                'expiry_date' => $raw['expiry_date'] ?? date('Y-m-d', strtotime('+2 years')),
                'quantity' => (float)($raw['quantity'] ?? 1),
                'free_quantity' => (float)($raw['free_quantity'] ?? 0),
                'unit_cost' => (float)($raw['unit_cost'] ?? 0),
                'mrp' => (float)($raw['mrp'] ?? 0),
                'gst_rate' => (float)($raw['gst_rate'] ?? $product?->gst_rate ?? 12.0),
                'hsn_code' => $raw['hsn_code'] ?? $product?->hsn_code ?? '3004',
            ];
        }

        return [
            'success' => true,
            'supplier' => $supplier ? [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'gstin' => $supplier->gstin,
                'code' => $supplier->code,
            ] : null,
            'invoice_number' => $payload['invoice_number'] ?? 'INV-' . strtoupper(Str::random(8)),
            'invoice_date' => $payload['invoice_date'] ?? date('Y-m-d'),
            'total_items_parsed' => count($parsedItems),
            'matched_items_count' => count(array_filter($parsedItems, fn($i) => $i['is_catalog_match'])),
            'items' => $parsedItems,
        ];
    }
}
