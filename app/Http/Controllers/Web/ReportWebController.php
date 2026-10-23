<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AccountingService;
use App\Services\GstService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportWebController extends Controller
{
    public function __construct(
        protected GstService $gstService,
        protected AccountingService $accountingService
    ) {}

    public function gst(Request $request): View
    {
        $user = Auth::user();
        $branchId = session('active_branch_id', $user->branch_id);
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->toDateString());

        $gstr1 = $this->gstService->getGstr1Report(
            organizationId: $user->organization_id,
            branchId: $branchId,
            fromDate: $startDate,
            toDate: $endDate
        );

        $gstr3b = $this->gstService->getGstr3bSummary(
            organizationId: $user->organization_id,
            branchId: $branchId,
            fromDate: $startDate,
            toDate: $endDate
        );

        $hsnSummary = $this->gstService->getHsnSummaryReport(
            organizationId: $user->organization_id,
            branchId: $branchId,
            fromDate: $startDate,
            toDate: $endDate
        );

        return view('reports.gst', compact('gstr1', 'gstr3b', 'hsnSummary', 'startDate', 'endDate'));
    }

    public function financials(Request $request): View
    {
        $user = Auth::user();
        $branchId = session('active_branch_id', $user->branch_id);
        $asOfDate = $request->query('as_of_date', Carbon::today()->toDateString());
        $startDate = $request->query('start_date', Carbon::now()->startOfYear()->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->toDateString());

        $trialBalance = $this->accountingService->getTrialBalance(
            organizationId: $user->organization_id,
            branchId: $branchId,
            asOfDate: $asOfDate
        );

        $profitAndLoss = $this->accountingService->getProfitAndLoss(
            organizationId: $user->organization_id,
            branchId: $branchId,
            fromDate: $startDate,
            toDate: $endDate
        );

        $balanceSheet = $this->accountingService->getBalanceSheet(
            organizationId: $user->organization_id,
            branchId: $branchId,
            asOfDate: $asOfDate
        );

        return view('reports.financials', compact('trialBalance', 'profitAndLoss', 'balanceSheet', 'asOfDate', 'startDate', 'endDate'));
    }
}
