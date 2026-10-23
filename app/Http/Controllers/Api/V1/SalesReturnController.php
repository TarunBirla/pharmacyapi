<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostSalesReturnRequest;
use App\Http\Resources\SalesReturnResource;
use App\Models\SalesReturn;
use App\Services\AuditLogService;
use App\Services\SalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesReturnController extends Controller
{
    public function __construct(
        protected SalesService $salesService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = SalesReturn::with(['customer', 'items.product', 'items.batch'])->latest('id');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $returns = $query->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => SalesReturnResource::collection($returns)->response()->getData(true),
        ]);
    }

    public function show(SalesReturn $salesReturn): JsonResponse
    {
        $salesReturn->load(['customer', 'invoice', 'items.product', 'items.batch']);

        return response()->json([
            'success' => true,
            'data' => new SalesReturnResource($salesReturn),
        ]);
    }

    public function store(PostSalesReturnRequest $request): JsonResponse
    {
        try {
            $salesReturn = $this->salesService->postSalesReturn(
                data: $request->validated(),
                user: $request->user()
            );

            AuditLogService::log(
                action: 'sales_return.post',
                description: "Processed sales return #{$salesReturn->credit_note_number} for ₹{$salesReturn->total_amount}",
                entity: $salesReturn
            );

            return response()->json([
                'success' => true,
                'message' => 'Sales return approved, stock restored according to condition, and credit note created',
                'data' => new SalesReturnResource($salesReturn),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
