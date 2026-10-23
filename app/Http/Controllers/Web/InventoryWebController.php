<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Services\AuditLogService;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InventoryWebController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $branchId = session('active_branch_id', $user->branch_id);
        $tab = $request->query('tab', 'catalog');
        $search = $request->query('search');

        $productsQuery = Product::where('organization_id', $user->organization_id)
            ->with(['batches' => function ($q) {
                $q->where('quantity_available', '>', 0);
            }]);

        if ($search) {
            $productsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('generic_name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('hsn_code', 'like', "%{$search}%");
            });
        }

        $products = $productsQuery->paginate(25);

        // Low stock items
        $lowStockProducts = Product::where('organization_id', $user->organization_id)
            ->whereHas('batches', function ($q) {
                $q->havingRaw('SUM(quantity_available) <= products.reorder_level');
            })
            ->with('batches')
            ->get();

        // Expiring batches (< 90 days)
        $expiringBatches = ProductBatch::where('organization_id', $user->organization_id)
            ->where('quantity_available', '>', 0)
            ->where('expiry_date', '<=', Carbon::now()->addDays(90))
            ->with('product')
            ->orderBy('expiry_date', 'asc')
            ->get();

        return view('inventory.index', compact('products', 'lowStockProducts', 'expiringBatches', 'tab', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'dosage_form' => 'required|string|max:50',
            'strength' => 'nullable|string|max:50',
            'barcode' => 'nullable|string|max:50|unique:product_barcodes,barcode',
            'hsn_code' => 'required|string|max:20',
            'gst_rate' => 'required|numeric|in:0,5,12,18,28',
            'prescription_type' => 'required|string|in:otc,schedule_h,schedule_h1,schedule_x,narcotic',
            'purchase_rate' => 'required|numeric|min:0',
            'retail_price' => 'required|numeric|min:0',
            'mrp' => 'required|numeric|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'rack_location' => 'nullable|string|max:50',
            'shelf_location' => 'nullable|string|max:50',
        ]);

        $primaryUnit = \App\Models\Unit::first();

        $product = Product::create([
            'organization_id' => $user->organization_id,
            'code' => 'MED-' . strtoupper(substr(uniqid(), -6)),
            'name' => $validated['name'],
            'generic_name' => $validated['generic_name'] ?? null,
            'dosage_form' => $validated['dosage_form'],
            'strength' => $validated['strength'] ?? null,
            'hsn_code' => $validated['hsn_code'],
            'gst_rate' => $validated['gst_rate'],
            'prescription_type' => $validated['prescription_type'],
            'primary_unit_id' => $primaryUnit?->id ?? 1,
            'reorder_level' => $validated['reorder_level'] ?? 20,
            'rack_location' => $validated['rack_location'] ?? null,
            'shelf_location' => $validated['shelf_location'] ?? null,
            'is_active' => true,
        ]);

        if (!empty($validated['barcode'])) {
            \App\Models\ProductBarcode::create([
                'product_id' => $product->id,
                'barcode' => $validated['barcode'],
                'is_primary' => true,
            ]);
        }

        // Create opening inventory batch
        $branchId = session('active_branch_id', $user->branch_id);
        $warehouse = \App\Models\Warehouse::where('branch_id', $branchId)->first() ?? \App\Models\Warehouse::first();

        ProductBatch::create([
            'organization_id' => $user->organization_id,
            'branch_id' => $branchId,
            'warehouse_id' => $warehouse?->id ?? 1,
            'product_id' => $product->id,
            'batch_number' => 'BTH-' . strtoupper(substr(uniqid(), -4)),
            'manufacturing_date' => now()->toDateString(),
            'expiry_date' => now()->addYears(2)->toDateString(),
            'mrp' => $validated['mrp'],
            'purchase_rate' => $validated['purchase_rate'],
            'sale_price' => $validated['retail_price'],
            'quantity_received' => 50,
            'quantity_available' => 50,
            'status' => 'active',
        ]);

        AuditLogService::log(
            action: 'products.create',
            description: "Created medicine '{$product->name}' (Code: {$product->code})",
            entity: $product,
            newValues: $product->toArray()
        );

        return redirect()->route('inventory.index')->with('success', "Medicine '{$product->name}' added to catalog with 50 units opening stock!");
    }

    public function substitutes(Product $product): View
    {
        $substitutes = $product->getSubstitutes();
        return view('inventory.substitutes_modal', compact('product', 'substitutes'));
    }
}
