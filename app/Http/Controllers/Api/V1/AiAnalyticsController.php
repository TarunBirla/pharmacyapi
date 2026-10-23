<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AiInventoryAnalyticsService;
use App\Services\AiInvoiceOcrService;
use App\Services\AiPatientRefillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAnalyticsController extends Controller
{
    public function __construct(
        protected AiInventoryAnalyticsService $inventoryAnalytics,
        protected AiInvoiceOcrService $invoiceOcr,
        protected AiPatientRefillService $patientRefill
    ) {}

    public function demandForecast(Request $request): JsonResponse
    {
        $user = $request->user();
        $branchId = $request->query('branch_id', $user->branch_id);
        $lookback = (int)$request->query('lookback_days', 30);

        $forecasts = $this->inventoryAnalytics->generateDemandForecast(
            organizationId: $user->organization_id,
            branchId: $branchId ? (int)$branchId : null,
            lookbackDays: $lookback
        );

        return response()->json([
            'success' => true,
            'message' => 'AI Demand Forecast generated successfully',
            'data' => $forecasts,
        ]);
    }

    public function expiryRisk(Request $request): JsonResponse
    {
        $user = $request->user();
        $branchId = $request->query('branch_id', $user->branch_id);
        $days = (int)$request->query('threshold_days', 90);

        $risks = $this->inventoryAnalytics->generateExpiryRiskAnalysis(
            organizationId: $user->organization_id,
            branchId: $branchId ? (int)$branchId : null,
            thresholdDays: $days
        );

        return response()->json([
            'success' => true,
            'message' => 'Expiry Risk and Liquidation Analysis generated',
            'data' => $risks,
        ]);
    }

    public function deadStock(Request $request): JsonResponse
    {
        $user = $request->user();
        $branchId = $request->query('branch_id', $user->branch_id);
        $days = (int)$request->query('inactive_days', 60);

        $deadStock = $this->inventoryAnalytics->getDeadStockReport(
            organizationId: $user->organization_id,
            branchId: $branchId ? (int)$branchId : null,
            inactiveDays: $days
        );

        return response()->json([
            'success' => true,
            'data' => $deadStock,
        ]);
    }

    public function parseInvoiceOcr(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'supplier_name' => 'nullable|string',
            'supplier_gstin' => 'nullable|string',
        ]);

        $user = $request->user();
        $parsed = $this->invoiceOcr->parseSupplierInvoice(
            payload: $request->all(),
            organizationId: $user->organization_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Supplier invoice parsed and matched with medicine master',
            'data' => $parsed,
        ]);
    }

    public function patientRefills(Request $request): JsonResponse
    {
        $user = $request->user();
        $branchId = $request->query('branch_id', $user->branch_id);

        $refills = $this->patientRefill->detectRefillOpportunities(
            organizationId: $user->organization_id,
            branchId: $branchId ? (int)$branchId : null
        );

        return response()->json([
            'success' => true,
            'data' => $refills,
        ]);
    }

    public function updateRefillStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,contacted,refilled,cancelled',
            'notes' => 'nullable|string|max:255',
        ]);

        $updated = $this->patientRefill->updateStatus(
            id: $id,
            status: $validated['status'],
            notes: $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Refill status updated',
            'data' => $updated,
        ]);
    }
}
