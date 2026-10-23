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
            'brand' => 'nullable|string|max:100',
            'dosage_form' => 'required|string|max:50',
            'strength' => 'nullable|string|max:50',
            'barcode' => 'nullable|string|max:50',
            'hsn_code' => 'required|string|max:20',
            'gst_rate' => 'required|numeric|in:0,5,12,18,28',
            'prescription_type' => 'required|string|in:otc,schedule_h,schedule_h1,schedule_x,narcotic',
            'purchase_rate' => 'required|numeric|min:0',
            'retail_price' => 'required|numeric|min:0',
            'mrp' => 'required|numeric|min:0',
            'package_unit' => 'required|string|max:50',
            'loose_unit' => 'required|string|max:50',
            'conversion_factor' => 'required|integer|min:1',
            'min_stock' => 'nullable|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'rack_location' => 'nullable|string|max:50',
            'shelf_location' => 'nullable|string|max:50',
        ]);

        $validated['organization_id'] = $user->organization_id;
        $validated['product_code'] = 'MED-' . strtoupper(substr(uniqid(), -6));
        $validated['status'] = 'active';

        $product = Product::create($validated);

        AuditLogService::log(
            action: 'products.create',
            description: "Created medicine '{$product->name}' (Code: {$product->product_code})",
            entity: $product,
            newValues: $product->toArray()
        );

        return redirect()->route('inventory.index')->with('success', "Medicine '{$product->name}' added to catalog successfully!");
    }

    public function substitutes(Product $product): View
    {
        $substitutes = $product->getSubstitutes();
        return view('inventory.substitutes_modal', compact('product', 'substitutes'));
    }
}
