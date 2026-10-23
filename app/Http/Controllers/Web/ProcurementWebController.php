<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\AuditLogService;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProcurementWebController extends Controller
{
    public function __construct(
        protected PurchaseService $procurementService
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $tab = $request->query('tab', 'invoices');

        $suppliers = Supplier::where('organization_id', $user->organization_id)
            ->withCount(['purchaseInvoices', 'purchaseOrders'])
            ->get();

        $invoices = PurchaseInvoice::where('organization_id', $user->organization_id)
            ->with(['supplier', 'items.product', 'items.batch'])
            ->latest('invoice_date')
            ->paginate(20);

        $purchaseOrders = PurchaseOrder::where('organization_id', $user->organization_id)
            ->with(['supplier', 'items.product'])
            ->latest('order_date')
            ->get();

        $products = Product::where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->get();

        return view('procurement.index', compact('suppliers', 'invoices', 'purchaseOrders', 'products', 'tab'));
    }

    public function storeSupplier(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'gstin' => 'nullable|string|max:20',
            'drug_license_number' => 'nullable|string|max:50',
            'address_line' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'credit_days' => 'nullable|integer|min:0',
        ]);

        $validated['organization_id'] = $user->organization_id;
        $validated['code'] = 'SUP-' . strtoupper(substr(uniqid(), -5));
        $validated['status'] = 'active';

        $supplier = Supplier::create($validated);

        return redirect()->route('procurement.index', ['tab' => 'suppliers'])
            ->with('success', "Supplier '{$supplier->name}' registered successfully!");
    }

    public function storeInvoice(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'supplier_invoice_number' => 'required|string|max:50',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.batch_number' => 'required|string|max:50',
            'items.*.expiry_date' => 'required|date',
            'items.*.billed_quantity' => 'required|numeric|min:0.01',
            'items.*.free_quantity' => 'nullable|numeric|min:0',
            'items.*.purchase_rate' => 'required|numeric|min:0',
            'items.*.mrp' => 'required|numeric|min:0',
            'items.*.gst_rate' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0',
        ]);

        try {
            $branchId = session('active_branch_id', $user->branch_id);
            $warehouse = \App\Models\Warehouse::where('branch_id', $branchId)->first();

            $data = [
                'supplier_id' => $validated['supplier_id'],
                'invoice_number' => $validated['supplier_invoice_number'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'warehouse_id' => $warehouse?->id,
                'items' => array_map(function ($it) {
                    return [
                        'product_id' => $it['product_id'],
                        'batch_number' => $it['batch_number'],
                        'expiry_date' => $it['expiry_date'],
                        'quantity' => (float)($it['billed_quantity'] ?? $it['quantity']),
                        'free_quantity' => (float)($it['free_quantity'] ?? 0),
                        'unit_cost' => (float)($it['purchase_rate'] ?? $it['unit_cost']),
                        'mrp' => (float)$it['mrp'],
                        'gst_rate' => (float)($it['gst_rate'] ?? 12),
                        'discount_percent' => (float)($it['discount_percent'] ?? 0),
                    ];
                }, $validated['items']),
            ];

            $invoice = $this->procurementService->postSupplierInvoice($data, $user);

            AuditLogService::log(
                action: 'purchase.post',
                description: "Posted supplier bill #{$invoice->invoice_number} from {$invoice->supplier->name} for ₹{$invoice->net_amount}",
                entity: $invoice
            );

            return redirect()->route('procurement.index', ['tab' => 'invoices'])
                ->with('success', "Supplier invoice #{$invoice->invoice_number} posted and inventory updated!");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
