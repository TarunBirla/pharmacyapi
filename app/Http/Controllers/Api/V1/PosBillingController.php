<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostSalesInvoiceRequest;
use App\Http\Resources\CashierShiftResource;
use App\Http\Resources\SalesInvoiceResource;
use App\Models\PosHeldBill;
use App\Services\AuditLogService;
use App\Services\SalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PosBillingController extends Controller
{
    public function __construct(
        protected SalesService $salesService
    ) {}

    /**
     * Fast POS Checkout: Posts invoice, updates batch inventory, writes stock movements, and handles split payments.
     */
    public function checkout(PostSalesInvoiceRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['invoice_type'] = $data['invoice_type'] ?? 'retail';

            $invoice = $this->salesService->postSalesInvoice(
                data: $data,
                user: $request->user()
            );

            AuditLogService::log(
                action: 'pos.checkout',
                description: "POS Checkout #{$invoice->invoice_number} for ₹{$invoice->net_amount}",
                entity: $invoice
            );

            return response()->json([
                'success' => true,
                'message' => 'Sale posted successfully',
                'data' => new SalesInvoiceResource($invoice),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Hold active cart in POS counter.
     */
    public function holdBill(Request $request): JsonResponse
    {
        $request->validate([
            'cart_data' => ['required', 'array'],
            'total_amount' => ['required', 'numeric'],
            'customer_id' => ['nullable', 'exists:customers,id'],
        ]);

        $user = $request->user();
        $refCode = 'HOLD-' . strtoupper(Str::random(6));

        $held = PosHeldBill::create([
            'organization_id' => $user->organization_id,
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'reference_code' => $refCode,
            'customer_id' => $request->customer_id,
            'cart_data' => $request->cart_data,
            'total_amount' => $request->total_amount,
            'held_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bill held successfully',
            'reference_code' => $refCode,
            'data' => $held,
        ], 201);
    }

    /**
     * List held bills at active counter.
     */
    public function heldBills(Request $request): JsonResponse
    {
        $bills = PosHeldBill::with('customer')
            ->where('branch_id', $request->user()->branch_id)
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bills,
        ]);
    }

    /**
     * Resume a held bill and delete from hold queue.
     */
    public function resumeBill(Request $request, string $referenceCode): JsonResponse
    {
        $held = PosHeldBill::where('branch_id', $request->user()->branch_id)
            ->where('reference_code', $referenceCode)
            ->firstOrFail();

        $cartData = $held->cart_data;
        $customerId = $held->customer_id;
        $held->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bill resumed',
            'data' => [
                'reference_code' => $referenceCode,
                'customer_id' => $customerId,
                'cart_data' => $cartData,
            ]
        ]);
    }

    /**
     * Start Cashier Shift.
     */
    public function startShift(Request $request): JsonResponse
    {
        $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:0'],
        ]);

        $shift = $this->salesService->startShift(
            openingCash: (float)$request->opening_cash,
            user: $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Cashier shift opened',
            'data' => new CashierShiftResource($shift),
        ]);
    }

    /**
     * Close Cashier Shift with cash balance reconciliation.
     */
    public function closeShift(Request $request): JsonResponse
    {
        $request->validate([
            'shift_id' => ['required', 'exists:cashier_shifts,id'],
            'closing_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $shift = $this->salesService->closeShift(
            shiftId: (int)$request->shift_id,
            closingCash: (float)$request->closing_cash,
            notes: $request->notes
        );

        return response()->json([
            'success' => true,
            'message' => 'Cashier shift closed successfully',
            'data' => new CashierShiftResource($shift),
        ]);
    }
}
