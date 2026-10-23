<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseOrder::with(['supplier', 'items.product', 'items.unit'])->latest('id');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => PurchaseOrderResource::collection($orders)->response()->getData(true),
        ]);
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->load(['supplier', 'items.product', 'items.unit', 'invoices']);

        return response()->json([
            'success' => true,
            'data' => new PurchaseOrderResource($purchaseOrder),
        ]);
    }

    public function store(CreatePurchaseOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $po = DB::transaction(function () use ($data, $user) {
            $subtotal = 0;
            $taxTotal = 0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $qty = (float)$item['quantity_ordered'];
                $cost = (float)$item['unit_cost'];
                $disc = (float)($item['discount_percent'] ?? 0);
                $taxRate = (float)($item['tax_rate'] ?? 12.00);

                $gross = $qty * $cost;
                $discAmt = round(($gross * $disc) / 100, 2);
                $taxable = $gross - $discAmt;
                $taxAmt = round(($taxable * $taxRate) / 100, 2);
                $total = $taxable + $taxAmt;

                $subtotal += $taxable;
                $taxTotal += $taxAmt;

                $itemsData[] = [
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'],
                    'quantity_ordered' => $qty,
                    'quantity_received' => 0,
                    'unit_cost' => $cost,
                    'discount_percent' => $disc,
                    'discount_amount' => $discAmt,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmt,
                    'total_amount' => $total,
                ];
            }

            $order = PurchaseOrder::create([
                'organization_id' => $user->organization_id,
                'branch_id' => $user->branch_id,
                'supplier_id' => $data['supplier_id'],
                'po_number' => 'PO-' . strtoupper(Str::random(8)),
                'order_date' => $data['order_date'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total_amount' => $subtotal + $taxTotal,
                'status' => 'approved',
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            foreach ($itemsData as $item) {
                $order->items()->create($item);
            }

            return $order;
        });

        AuditLogService::log(
            action: 'purchase_order.create',
            description: "Created PO #{$po->po_number} for supplier #{$po->supplier_id}",
            entity: $po
        );

        return response()->json([
            'success' => true,
            'message' => 'Purchase order created successfully',
            'data' => new PurchaseOrderResource($po->load(['supplier', 'items.product', 'items.unit'])),
        ], 201);
    }
}
