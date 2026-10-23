<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductComposition;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['brand', 'manufacturer', 'category', 'primaryUnit', 'secondaryUnit', 'barcodes'])
            ->withSum(['batches as total_stock' => function ($q) {
                $q->where('status', 'active')->where('expiry_date', '>', now()->toDateString());
            }], 'quantity_available');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('prescription_type')) {
            $query->where('prescription_type', $request->prescription_type);
        }

        if ($request->filled('dosage_form')) {
            $query->where('dosage_form', $request->dosage_form);
        }

        $products = $query->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products)->response()->getData(true),
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['brand', 'manufacturer', 'category', 'genericComposition', 'primaryUnit', 'secondaryUnit', 'barcodes', 'compositions'])
            ->loadSum(['batches as total_stock' => function ($q) {
                $q->where('status', 'active')->where('expiry_date', '>', now()->toDateString());
            }], 'quantity_available');

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ]);
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $product = DB::transaction(function () use ($data, $user) {
            $productData = collect($data)->except(['barcodes', 'compositions'])->all();
            $productData['organization_id'] = $user->organization_id;

            $prod = Product::create($productData);

            if (!empty($data['barcodes'])) {
                foreach ($data['barcodes'] as $index => $barcode) {
                    ProductBarcode::create([
                        'product_id' => $prod->id,
                        'unit_id' => $prod->primary_unit_id,
                        'barcode' => $barcode,
                        'is_primary' => ($index === 0),
                    ]);
                }
            }

            if (!empty($data['compositions'])) {
                foreach ($data['compositions'] as $comp) {
                    ProductComposition::create([
                        'product_id' => $prod->id,
                        'active_ingredient' => $comp['active_ingredient'],
                        'strength' => $comp['strength'],
                        'unit' => $comp['unit'] ?? 'mg',
                    ]);
                }
            }

            return $prod;
        });

        AuditLogService::log(
            action: 'product.create',
            description: "Created medicine {$product->name} ({$product->code})",
            entity: $product,
            newValues: $product->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Product catalog item created successfully',
            'data' => new ProductResource($product->load(['brand', 'manufacturer', 'category', 'primaryUnit', 'barcodes', 'compositions'])),
        ], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'dosage_form' => ['sometimes', 'string', 'max:50'],
            'strength' => ['nullable', 'string', 'max:50'],
            'hsn_code' => ['nullable', 'string', 'max:20'],
            'gst_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'rack_location' => ['nullable', 'string', 'max:50'],
            'shelf_location' => ['nullable', 'string', 'max:50'],
            'prescription_type' => ['sometimes', 'in:otc,schedule_h,schedule_h1,schedule_x,narcotic'],
            'storage_temperature' => ['sometimes', 'in:room_temperature,cold_chain_2_8c,cool_dark_place'],
            'reorder_level' => ['sometimes', 'numeric', 'min:0'],
            'reorder_quantity' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $oldValues = $product->toArray();
        $product->update($validated);

        AuditLogService::log(
            action: 'product.update',
            description: "Updated product {$product->name}",
            entity: $product,
            oldValues: $oldValues,
            newValues: $product->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => new ProductResource($product->load(['brand', 'manufacturer', 'category', 'primaryUnit', 'barcodes'])),
        ]);
    }

    /**
     * Get medically equivalent alternative brands with exact active composition.
     */
    public function substitutes(Product $product): JsonResponse
    {
        $substitutes = $product->getSubstitutes();

        return response()->json([
            'success' => true,
            'disclaimer' => 'Substitutions must follow pharmacist verification and applicable prescription regulations.',
            'original_product' => [
                'id' => $product->id,
                'name' => $product->name,
                'generic_composition' => $product->genericComposition?->name,
            ],
            'substitutes' => ProductResource::collection($substitutes),
        ]);
    }
}
