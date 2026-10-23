<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\GstService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GstReportController extends Controller
{
    public function __construct(
        protected GstService $gstService
    ) {}

    public function gstr1(Request $request): JsonResponse
    {
        $user = $request->user();
        $fromDate = $request->query('from_date', date('Y-m-01'));
        $toDate = $request->query('to_date', date('Y-m-d'));
        $branchId = $request->query('branch_id', $user->branch_id);

        $report = $this->gstService->getGstr1Report(
            organizationId: $user->organization_id,
            branchId: $branchId ? (int)$branchId : null,
            fromDate: $fromDate,
            toDate: $toDate
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function gstr3b(Request $request): JsonResponse
    {
        $user = $request->user();
        $fromDate = $request->query('from_date', date('Y-m-01'));
        $toDate = $request->query('to_date', date('Y-m-d'));
        $branchId = $request->query('branch_id', $user->branch_id);

        $report = $this->gstService->getGstr3bSummary(
            organizationId: $user->organization_id,
            branchId: $branchId ? (int)$branchId : null,
            fromDate: $fromDate,
            toDate: $toDate
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function hsnSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        $fromDate = $request->query('from_date', date('Y-m-01'));
        $toDate = $request->query('to_date', date('Y-m-d'));
        $branchId = $request->query('branch_id', $user->branch_id);

        $summary = $this->gstService->getHsnSummaryReport(
            organizationId: $user->organization_id,
            branchId: $branchId ? (int)$branchId : null,
            fromDate: $fromDate,
            toDate: $toDate
        );

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}
