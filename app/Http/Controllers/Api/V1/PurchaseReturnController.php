<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostPurchaseReturnRequest;
use App\Http\Resources\PurchaseReturnResource;
use App\Models\PurchaseReturn;
use App\Services\AuditLogService;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = PurchaseReturn::with(['supplier', 'items.product', 'items.batch'])->latest('id');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $returns = $query->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => PurchaseReturnResource::collection($returns)->response()->getData(true),
        ]);
    }

    public function show(PurchaseReturn $purchaseReturn): JsonResponse
    {
        $purchaseReturn->load(['supplier', 'items.product', 'items.batch']);

        return response()->json([
            'success' => true,
            'data' => new PurchaseReturnResource($purchaseReturn),
        ]);
    }

    public function store(PostPurchaseReturnRequest $request): JsonResponse
    {
        try {
            $purchaseReturn = $this->purchaseService->postPurchaseReturn(
                data: $request->validated(),
                user: $request->user()
            );

            AuditLogService::log(
                action: 'purchase_return.post',
                description: "Created Debit Note #{$purchaseReturn->debit_note_number} for ₹{$purchaseReturn->total_amount}",
                entity: $purchaseReturn
            );

            return response()->json([
                'success' => true,
                'message' => 'Purchase return approved, stock deducted, and Debit Note posted to supplier ledger',
                'data' => new PurchaseReturnResource($purchaseReturn),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
