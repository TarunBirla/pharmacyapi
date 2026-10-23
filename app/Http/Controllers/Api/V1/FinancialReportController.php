<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    public function trialBalance(Request $request): JsonResponse
    {
        $user = $request->user();
        $asOfDate = $request->query('as_of_date', date('Y-m-d'));
        $branchId = $request->query('branch_id', $user->branch_id);

        $trialBalance = $this->accountingService->getTrialBalance(
            organizationId: $user->organization_id,
            branchId: $branchId ? (int)$branchId : null,
            asOfDate: $asOfDate
        );

        return response()->json([
            'success' => true,
            'data' => $trialBalance,
        ]);
    }

    public function profitAndLoss(Request $request): JsonResponse
    {
        $user = $request->user();
        $fromDate = $request->query('from_date', date('Y-01-01'));
        $toDate = $request->query('to_date', date('Y-m-d'));
        $branchId = $request->query('branch_id', $user->branch_id);

        $pl = $this->accountingService->getProfitAndLoss(
            organizationId: $user->organization_id,
            branchId: $branchId ? (int)$branchId : null,
            fromDate: $fromDate,
            toDate: $toDate
        );

        return response()->json([
            'success' => true,
            'data' => $pl,
        ]);
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $user = $request->user();
        $asOfDate = $request->query('as_of_date', date('Y-m-d'));
        $branchId = $request->query('branch_id', $user->branch_id);

        $bs = $this->accountingService->getBalanceSheet(
            organizationId: $user->organization_id,
            branchId: $branchId ? (int)$branchId : null,
            asOfDate: $asOfDate
        );

        return response()->json([
            'success' => true,
            'data' => $bs,
        ]);
    }
}
