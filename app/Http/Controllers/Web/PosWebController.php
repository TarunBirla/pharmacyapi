<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Doctor;
use App\Models\PosHeldBill;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\SalesInvoice;
use App\Services\AuditLogService;
use App\Services\SalesService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PosWebController extends Controller
{
    public function __construct(
        protected SalesService $salesService
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $branchId = session('active_branch_id', $user->branch_id);

        // Fetch products with their active batches and stock
        $products = Product::where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->with(['batches' => function ($q) use ($branchId) {
                $q->where('quantity_available', '>', 0)
                  ->where('status', 'active')
                  ->where('expiry_date', '>', Carbon::today())
                  ->orderBy('expiry_date', 'asc');
            }])
            ->get();

        $customers = Customer::where('organization_id', $user->organization_id)->get();
        $doctors = Doctor::where('organization_id', $user->organization_id)->get();

        // Get current cashier shift
        $currentShift = CashierShift::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('started_at')
            ->first();

        // Held bills
        $heldBills = PosHeldBill::with('customer')
            ->where('organization_id', $user->organization_id)
            ->where('branch_id', $branchId)
            ->latest('id')
            ->get();

        return view('pos.index', compact('products', 'customers', 'doctors', 'currentShift', 'heldBills'));
    }

    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'doctor_id' => 'nullable|exists:doctors,id',
            'doctor_name' => 'nullable|string',
            'prescription_number' => 'nullable|string',
            'prescription_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.batch_id' => 'required|exists:product_batches,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.gst_rate' => 'nullable|numeric|min:0|max:28',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method' => 'required|string|in:cash,upi,card,credit,cheque,bank_transfer',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.reference_number' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $user = Auth::user();
            $data = $request->all();
            $data['invoice_type'] = $data['invoice_type'] ?? 'retail';
            $branchId = session('active_branch_id', $user->branch_id);
            $data['branch_id'] = $branchId;

            $warehouse = \App\Models\Warehouse::where('branch_id', $branchId)->first() ?? \App\Models\Warehouse::first();
            $data['warehouse_id'] = $data['warehouse_id'] ?? $warehouse?->id;

            // Map batch_id to product_batch_id
            $data['items'] = array_map(function ($it) {
                return [
                    'product_id' => $it['product_id'],
                    'product_batch_id' => $it['batch_id'] ?? $it['product_batch_id'],
                    'quantity' => (float)$it['quantity'],
                    'unit_price' => (float)$it['unit_price'],
                    'discount_percent' => (float)($it['discount_percent'] ?? 0),
                    'gst_rate' => (float)($it['gst_rate'] ?? 0),
                ];
            }, $request->input('items', []));

            // Map payments to ensure payment_mode is set
            $data['payments'] = array_map(function ($p) {
                return [
                    'payment_mode' => $p['payment_mode'] ?? $p['payment_method'] ?? 'cash',
                    'amount' => (float)$p['amount'],
                    'transaction_reference' => $p['transaction_reference'] ?? $p['reference_number'] ?? null,
                ];
            }, $request->input('payments', []));

            $invoice = $this->salesService->postSalesInvoice($data, $user);

            AuditLogService::log(
                action: 'pos.checkout',
                description: "POS Counter sale #{$invoice->invoice_number} for ₹{$invoice->net_amount}",
                entity: $invoice
            );

            return response()->json([
                'success' => true,
                'message' => "Invoice #{$invoice->invoice_number} created successfully!",
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'net_amount' => $invoice->net_amount,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function receipt(SalesInvoice $invoice): View
    {
        $invoice->load(['items.product', 'items.batch', 'customer', 'doctor', 'payments', 'branch.organization']);
        return view('pos.receipt', compact('invoice'));
    }

    public function holdBill(Request $request): JsonResponse
    {
        $request->validate([
            'cart_payload' => 'required|array',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $user = Auth::user();
        $refCode = 'HOLD-' . strtoupper(Str::random(6));

        $heldBill = PosHeldBill::create([
            'organization_id' => $user->organization_id,
            'branch_id' => session('active_branch_id', $user->branch_id),
            'user_id' => $user->id,
            'reference_code' => $refCode,
            'customer_id' => $request->input('customer_id'),
            'cart_data' => $request->input('cart_payload'),
            'total_amount' => $request->input('total_amount', 0),
            'held_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Bill held under reference: {$refCode}",
            'reference_code' => $refCode,
            'held_bill' => $heldBill,
        ]);
    }

    public function resumeBill(string $referenceCode): JsonResponse
    {
        $user = Auth::user();
        $held = PosHeldBill::where('organization_id', $user->organization_id)
            ->where('reference_code', $referenceCode)
            ->firstOrFail();

        $cartData = $held->cart_data;
        $held->delete();

        return response()->json([
            'success' => true,
            'data' => [
                'cart_payload' => $cartData,
            ],
        ]);
    }

    public function startShift(Request $request): JsonResponse
    {
        $request->validate([
            'opening_float' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $shift = $this->salesService->startShift(
            openingCash: (float)$request->input('opening_float'),
            user: $user
        );

        return response()->json([
            'success' => true,
            'message' => 'Cashier shift opened successfully.',
            'data' => $shift,
        ]);
    }

    public function closeShift(Request $request): JsonResponse
    {
        $request->validate([
            'shift_id' => 'required|exists:cashier_shifts,id',
            'closing_cash_counted' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $shift = $this->salesService->closeShift(
            shiftId: (int)$request->input('shift_id'),
            closingCash: (float)$request->input('closing_cash_counted'),
            notes: $request->input('closing_notes')
        );

        return response()->json([
            'success' => true,
            'message' => 'Cashier shift closed successfully.',
            'data' => $shift,
        ]);
    }
}
