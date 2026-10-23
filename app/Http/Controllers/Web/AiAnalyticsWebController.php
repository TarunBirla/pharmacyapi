<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AiInventoryAnalyticsService;
use App\Services\AiPatientRefillService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AiAnalyticsWebController extends Controller
{
    public function __construct(
        protected AiInventoryAnalyticsService $inventoryAnalytics,
        protected AiPatientRefillService $patientRefill
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $tab = $request->query('tab', 'demand');
        $branchId = session('active_branch_id', $user->branch_id);

        $demandForecasts = $this->inventoryAnalytics->generateDemandForecast(
            organizationId: $user->organization_id,
            branchId: $branchId,
            lookbackDays: 30
        );

        $expiryRisks = $this->inventoryAnalytics->generateExpiryRiskAnalysis(
            organizationId: $user->organization_id,
            branchId: $branchId,
            thresholdDays: 90
        );

        $deadStock = $this->inventoryAnalytics->getDeadStockReport(
            organizationId: $user->organization_id,
            branchId: $branchId,
            inactiveDays: 60
        );

        $patientRefills = $this->patientRefill->detectRefillOpportunities(
            organizationId: $user->organization_id,
            branchId: $branchId
        );

        return view('ai.index', compact('demandForecasts', 'expiryRisks', 'deadStock', 'patientRefills', 'tab'));
    }

    public function updateRefillStatus(Request $request, int $id): RedirectResponse
    {
        $status = $request->input('status', 'sent');
        $this->patientRefill->updateStatus($id, $status);

        return redirect()->route('ai.index', ['tab' => 'refills'])
            ->with('success', "Patient refill reminder marked as '{$status}'.");
    }
}
