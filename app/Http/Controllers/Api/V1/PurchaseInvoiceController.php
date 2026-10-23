<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostPurchaseInvoiceRequest;
use App\Http\Resources\PurchaseInvoiceResource;
use App\Models\PurchaseInvoice;
use App\Services\AuditLogService;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseInvoiceController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = PurchaseInvoice::with(['supplier', 'warehouse', 'items.product'])->latest('id');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $invoices = $query->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => PurchaseInvoiceResource::collection($invoices)->response()->getData(true),
        ]);
    }

    public function show(PurchaseInvoice $purchaseInvoice): JsonResponse
    {
        $purchaseInvoice->load(['supplier', 'warehouse', 'items.product', 'items.batch']);

        return response()->json([
            'success' => true,
            'data' => new PurchaseInvoiceResource($purchaseInvoice),
        ]);
    }

    public function store(PostPurchaseInvoiceRequest $request): JsonResponse
    {
        try {
            $invoice = $this->purchaseService->postSupplierInvoice(
                data: $request->validated(),
                user: $request->user()
            );

            AuditLogService::log(
                action: 'purchase_invoice.post',
                description: "Posted purchase invoice #{$invoice->invoice_number} from {$invoice->supplier->name} for ₹{$invoice->net_amount}",
                entity: $invoice
            );

            return response()->json([
                'success' => true,
                'message' => 'Supplier invoice posted, batch inventory received, and supplier ledger updated',
                'data' => new PurchaseInvoiceResource($invoice),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
