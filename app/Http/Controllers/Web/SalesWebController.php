<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Services\AuditLogService;
use App\Services\SalesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SalesWebController extends Controller
{
    public function __construct(
        protected SalesService $salesService
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $branchId = session('active_branch_id', $user->branch_id);
        $search = $request->query('search');
        $date = $request->query('date');

        $query = SalesInvoice::where('organization_id', $user->organization_id)
            ->with(['customer', 'items.product', 'items.batch', 'payments'])
            ->latest('invoice_date');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($date) {
            $query->whereDate('invoice_date', $date);
        }

        $invoices = $query->paginate(25);
        $returns = SalesReturn::where('organization_id', $user->organization_id)->latest()->take(10)->get();

        return view('sales.index', compact('invoices', 'returns', 'search', 'date'));
    }

    public function show(SalesInvoice $invoice): View
    {
        $invoice->load(['items.product', 'items.batch', 'customer', 'doctor', 'payments', 'branch.organization', 'returns']);
        return view('sales.show', compact('invoice'));
    }

    public function returnSale(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.sales_invoice_item_id' => 'required|exists:sales_invoice_items,id',
            'items.*.return_quantity' => 'required|numeric|min:0.01',
            'refund_mode' => 'required|string|in:cash,credit_note,bank_transfer',
        ]);

        try {
            $user = Auth::user();
            $salesReturn = $this->salesService->processSalesReturn(
                invoice: $invoice,
                data: $request->all(),
                user: $user
            );

            AuditLogService::log(
                action: 'sales.return',
                description: "Processed sales return #{$salesReturn->return_number} for invoice #{$invoice->invoice_number}",
                entity: $salesReturn
            );

            return redirect()->route('sales.show', $invoice)
                ->with('success', "Sales return #{$salesReturn->return_number} processed and stock restored!");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
