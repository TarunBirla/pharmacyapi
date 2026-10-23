<?php

namespace App\Services;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use Illuminate\Support\Facades\DB;

class GstService
{
    /**
     * Generate complete GSTR-1 outward supplies return data.
     */
    public function getGstr1Report(int $organizationId, ?int $branchId, string $fromDate, string $toDate): array
    {
        $salesQuery = SalesInvoice::withoutGlobalScopes()
            ->with(['customer', 'items.product'])
            ->where('organization_id', $organizationId)
            ->where('status', 'posted')
            ->whereBetween('invoice_date', [$fromDate, $toDate]);

        if ($branchId) {
            $salesQuery->where('branch_id', $branchId);
        }

        $invoices = $salesQuery->get();

        $b2b = [];
        $b2cs = [];
        $b2cl = [];

        foreach ($invoices as $inv) {
            $customerGstin = $inv->customer?->gstin;
            $netAmount = (float)$inv->net_amount;
            $isInterState = (float)$inv->igst_amount > 0;

            if (!empty($customerGstin)) {
                // B2B: Supply to registered taxable person
                $b2b[] = [
                    'customer_gstin' => $customerGstin,
                    'customer_name' => $inv->customer?->name,
                    'invoice_number' => $inv->invoice_number,
                    'invoice_date' => $inv->invoice_date->format('Y-m-d'),
                    'invoice_value' => $netAmount,
                    'place_of_supply' => substr($customerGstin, 0, 2),
                    'reverse_charge' => 'N',
                    'taxable_value' => (float)$inv->taxable_amount,
                    'cgst_amount' => (float)$inv->cgst_amount,
                    'sgst_amount' => (float)$inv->sgst_amount,
                    'igst_amount' => (float)$inv->igst_amount,
                    'cess_amount' => 0.00,
                ];
            } else {
                // Unregistered buyer
                if ($isInterState && $netAmount > 250000) {
                    // B2CL: Large inter-state unregistered
                    $b2cl[] = [
                        'invoice_number' => $inv->invoice_number,
                        'invoice_date' => $inv->invoice_date->format('Y-m-d'),
                        'invoice_value' => $netAmount,
                        'place_of_supply' => 'Other State',
                        'taxable_value' => (float)$inv->taxable_amount,
                        'igst_amount' => (float)$inv->igst_amount,
                    ];
                } else {
                    // B2CS: Retail small supplies
                    $b2cs[] = [
                        'invoice_number' => $inv->invoice_number,
                        'invoice_date' => $inv->invoice_date->format('Y-m-d'),
                        'taxable_value' => (float)$inv->taxable_amount,
                        'cgst_amount' => (float)$inv->cgst_amount,
                        'sgst_amount' => (float)$inv->sgst_amount,
                        'igst_amount' => (float)$inv->igst_amount,
                        'net_amount' => $netAmount,
                    ];
                }
            }
        }

        // CDNR: Credit & Debit Notes (Sales Returns)
        $returnsQuery = SalesReturn::withoutGlobalScopes()
            ->with(['salesInvoice.customer'])
            ->where('organization_id', $organizationId)
            ->whereBetween('return_date', [$fromDate, $toDate]);

        if ($branchId) {
            $returnsQuery->where('branch_id', $branchId);
        }

        $returns = $returnsQuery->get();
        $cdnr = [];
        $cdnur = [];

        foreach ($returns as $ret) {
            $customerGstin = $ret->salesInvoice?->customer?->gstin;
            $taxAmt = (float)$ret->tax_amount;
            $cgst = round($taxAmt / 2, 2);
            $sgst = round($taxAmt - $cgst, 2);

            $noteData = [
                'credit_note_number' => $ret->credit_note_number,
                'credit_note_date' => $ret->return_date->format('Y-m-d'),
                'original_invoice_number' => $ret->salesInvoice?->invoice_number,
                'taxable_value' => (float)$ret->subtotal,
                'cgst_amount' => $cgst,
                'sgst_amount' => $sgst,
                'igst_amount' => 0.00,
                'total_note_value' => (float)$ret->total_amount,
            ];

            if (!empty($customerGstin)) {
                $cdnr[] = array_merge($noteData, ['customer_gstin' => $customerGstin]);
            } else {
                $cdnur[] = $noteData;
            }
        }

        // HSN Summary
        $hsnSummary = $this->getHsnSummaryReport($organizationId, $branchId, $fromDate, $toDate);

        // Document Issued Summary
        $docSummary = [
            'invoices' => [
                'type' => 'Tax Invoices',
                'from_serial' => $invoices->first()?->invoice_number ?? 'N/A',
                'to_serial' => $invoices->last()?->invoice_number ?? 'N/A',
                'total_count' => $invoices->count(),
                'cancelled_count' => SalesInvoice::withoutGlobalScopes()
                    ->where('organization_id', $organizationId)
                    ->where('status', 'cancelled')
                    ->whereBetween('invoice_date', [$fromDate, $toDate])
                    ->count(),
            ],
            'credit_notes' => [
                'type' => 'Credit Notes (Sales Returns)',
                'total_count' => $returns->count(),
            ]
        ];

        return [
            'return_period' => ['from' => $fromDate, 'to' => $toDate],
            'b2b_invoices' => $b2b,
            'b2cs_invoices' => $b2cs,
            'b2cl_invoices' => $b2cl,
            'credit_debit_notes_registered' => $cdnr,
            'credit_debit_notes_unregistered' => $cdnur,
            'hsn_summary' => $hsnSummary,
            'documents_summary' => $docSummary,
        ];
    }

    /**
     * Generate GSTR-3B Monthly Return summary.
     */
    public function getGstr3bSummary(int $organizationId, ?int $branchId, string $fromDate, string $toDate): array
    {
        // 1. Table 3.1: Details of Outward Supplies
        $salesQuery = SalesInvoice::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('status', 'posted')
            ->whereBetween('invoice_date', [$fromDate, $toDate]);

        if ($branchId) {
            $salesQuery->where('branch_id', $branchId);
        }

        $outwardTaxable = (float)$salesQuery->sum('taxable_amount');
        $outwardCgst = (float)$salesQuery->sum('cgst_amount');
        $outwardSgst = (float)$salesQuery->sum('sgst_amount');
        $outwardIgst = (float)$salesQuery->sum('igst_amount');

        // Subtract Sales Returns
        $returnQuery = SalesReturn::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->whereBetween('return_date', [$fromDate, $toDate]);

        if ($branchId) {
            $returnQuery->where('branch_id', $branchId);
        }

        $returnTaxable = (float)$returnQuery->sum('subtotal');
        $returnTax = (float)$returnQuery->sum('tax_amount');
        $returnCgst = round($returnTax / 2, 2);
        $returnSgst = round($returnTax - $returnCgst, 2);
        $returnIgst = 0.00;

        $netOutwardTaxable = round(max(0, $outwardTaxable - $returnTaxable), 2);
        $netOutwardCgst = round(max(0, $outwardCgst - $returnCgst), 2);
        $netOutwardSgst = round(max(0, $outwardSgst - $returnSgst), 2);
        $netOutwardIgst = round(max(0, $outwardIgst - $returnIgst), 2);

        // 2. Table 4: Eligible Input Tax Credit (ITC) from Inward Purchases
        $purchaseQuery = PurchaseInvoice::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('status', 'posted')
            ->whereBetween('invoice_date', [$fromDate, $toDate]);

        if ($branchId) {
            $purchaseQuery->where('branch_id', $branchId);
        }

        $itcCgst = (float)$purchaseQuery->sum('cgst_amount');
        $itcSgst = (float)$purchaseQuery->sum('sgst_amount');
        $itcIgst = (float)$purchaseQuery->sum('igst_amount');

        // Subtract Purchase Returns (Debit Notes)
        $purchaseReturnQuery = PurchaseReturn::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->whereBetween('return_date', [$fromDate, $toDate]);

        if ($branchId) {
            $purchaseReturnQuery->where('branch_id', $branchId);
        }

        $itcReversalTax = (float)$purchaseReturnQuery->sum('tax_amount');
        $itcReversalCgst = round($itcReversalTax / 2, 2);
        $itcReversalSgst = round($itcReversalTax - $itcReversalCgst, 2);
        $itcReversalIgst = 0.00;

        $netItcCgst = round(max(0, $itcCgst - $itcReversalCgst), 2);
        $netItcSgst = round(max(0, $itcSgst - $itcReversalSgst), 2);
        $netItcIgst = round(max(0, $itcIgst - $itcReversalIgst), 2);

        // 3. Tax Payable after ITC utilization
        $taxPayableCgst = round(max(0, $netOutwardCgst - $netItcCgst), 2);
        $taxPayableSgst = round(max(0, $netOutwardSgst - $netItcSgst), 2);
        $taxPayableIgst = round(max(0, $netOutwardIgst - $netItcIgst), 2);

        return [
            'period' => ['from' => $fromDate, 'to' => $toDate],
            'table_3_1_outward_supplies' => [
                'nature_of_supplies' => '(a) Outward taxable supplies (other than zero rated, nil rated and exempted)',
                'total_taxable_value' => $netOutwardTaxable,
                'integrated_tax' => $netOutwardIgst,
                'central_tax' => $netOutwardCgst,
                'state_tax' => $netOutwardSgst,
                'cess' => 0.00,
            ],
            'table_4_eligible_itc' => [
                'all_other_itc' => [
                    'central_tax' => $netItcCgst,
                    'state_tax' => $netItcSgst,
                    'integrated_tax' => $netItcIgst,
                ],
                'itc_reversal' => [
                    'central_tax' => $itcReversalCgst,
                    'state_tax' => $itcReversalSgst,
                    'integrated_tax' => $itcReversalIgst,
                ],
                'net_itc_available' => [
                    'central_tax' => $netItcCgst,
                    'state_tax' => $netItcSgst,
                    'integrated_tax' => $netItcIgst,
                ],
            ],
            'net_tax_liability_payable_in_cash' => [
                'central_tax' => $taxPayableCgst,
                'state_tax' => $taxPayableSgst,
                'integrated_tax' => $taxPayableIgst,
                'total_payable' => round($taxPayableCgst + $taxPayableSgst + $taxPayableIgst, 2),
            ]
        ];
    }

    /**
     * Generate HSN Summary of Outward Supplies.
     */
    public function getHsnSummaryReport(int $organizationId, ?int $branchId, string $fromDate, string $toDate): array
    {
        $query = DB::table('sales_invoice_items')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->join('products', 'products.id', '=', 'sales_invoice_items.product_id')
            ->where('sales_invoices.organization_id', $organizationId)
            ->where('sales_invoices.status', 'posted')
            ->whereBetween('sales_invoices.invoice_date', [$fromDate, $toDate]);

        if ($branchId) {
            $query->where('sales_invoices.branch_id', $branchId);
        }

        $results = $query->select(
            'products.hsn_code',
            'products.name as description',
            DB::raw('SUM(sales_invoice_items.quantity) as total_quantity'),
            DB::raw('SUM(sales_invoice_items.total_amount) as total_value'),
            DB::raw('SUM(sales_invoice_items.taxable_value) as total_taxable'),
            DB::raw('SUM(sales_invoice_items.taxable_value * (products.gst_rate / 200)) as cgst_amount'),
            DB::raw('SUM(sales_invoice_items.taxable_value * (products.gst_rate / 200)) as sgst_amount'),
            'products.gst_rate'
        )
        ->groupBy('products.hsn_code', 'products.name', 'products.gst_rate')
        ->get();

        return $results->map(function ($row) {
            return [
                'hsn_code' => $row->hsn_code ?: '3004',
                'description' => $row->description,
                'uqc' => 'NOS',
                'total_quantity' => (float)$row->total_quantity,
                'total_value' => round((float)$row->total_value, 2),
                'taxable_value' => round((float)$row->total_taxable, 2),
                'gst_rate' => (float)$row->gst_rate,
                'central_tax' => round((float)$row->cgst_amount, 2),
                'state_tax' => round((float)$row->sgst_amount, 2),
                'integrated_tax' => 0.00,
                'cess' => 0.00,
            ];
        })->toArray();
    }
}
