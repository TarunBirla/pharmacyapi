<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSupplierRequest;
use App\Http\Requests\SupplierPaymentRequest;
use App\Http\Resources\SupplierLedgerResource;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Services\AuditLogService;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('gstin', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $suppliers = $query->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => SupplierResource::collection($suppliers)->response()->getData(true),
        ]);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new SupplierResource($supplier),
        ]);
    }

    public function store(CreateSupplierRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['organization_id'] = $request->user()->organization_id;

        $supplier = Supplier::create($data);

        AuditLogService::log(
            action: 'supplier.create',
            description: "Registered supplier {$supplier->name} ({$supplier->code})",
            entity: $supplier
        );

        return response()->json([
            'success' => true,
            'message' => 'Supplier registered successfully',
            'data' => new SupplierResource($supplier),
        ], 201);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:25'],
            'alternate_phone' => ['nullable', 'string', 'max:25'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'pan' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'credit_period_days' => ['sometimes', 'integer', 'min:0'],
            'credit_limit' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:active,inactive,blocked'],
            'notes' => ['nullable', 'string'],
        ]);

        $supplier->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully',
            'data' => new SupplierResource($supplier),
        ]);
    }

    /**
     * Paginated Supplier Ledger statement.
     */
    public function ledger(Request $request, Supplier $supplier): JsonResponse
    {
        $query = SupplierLedgerEntry::where('supplier_id', $supplier->id)->latest('id');

        $entries = $query->paginate($request->input('per_page', 30));

        return response()->json([
            'success' => true,
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'current_balance' => (float)$supplier->current_balance,
            ],
            'data' => SupplierLedgerResource::collection($entries)->response()->getData(true),
        ]);
    }

    /**
     * Record supplier payment.
     */
    public function recordPayment(SupplierPaymentRequest $request): JsonResponse
    {
        $entry = $this->purchaseService->recordSupplierPayment(
            supplierId: (int)$request->supplier_id,
            amount: (float)$request->amount,
            paymentMode: $request->payment_mode,
            referenceNo: $request->reference_number,
            notes: $request->notes,
            user: $request->user()
        );

        AuditLogService::log(
            action: 'supplier.payment',
            description: "Recorded payment of ₹{$request->amount} to supplier #{$request->supplier_id}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Supplier payment recorded and ledger updated',
            'data' => new SupplierLedgerResource($entry),
        ]);
    }
}
