@extends('layouts.app')

@section('title', 'GST & Tax Compliance (GSTR-1 & GSTR-3B)')

@section('content')
<div class="flex-1 flex flex-col p-4 md:p-6 bg-slate-50 overflow-y-auto">
    
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-slate-200 gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-receipt text-indigo-600"></i> GST Compliance & Filing Dashboard
            </h1>
            <p class="text-xs text-slate-500">
                Statutory Goods and Services Tax reporting: GSTR-1 tables, HSN summary, and GSTR-3B Input Tax Credit (ITC) reconciliation.
            </p>
        </div>

        <!-- Date Range Filter -->
        <form action="{{ route('reports.gst') }}" method="GET" class="flex items-center gap-2">
            <input type="date" name="start_date" value="{{ $startDate }}" class="bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-800 font-mono">
            <span class="text-xs text-slate-400">to</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-800 font-mono">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-3 py-1.5 rounded-xl text-xs transition">
                Generate
            </button>
        </form>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 my-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Taxable Sales</div>
            <div class="text-lg font-black text-slate-900 font-mono mt-1">
                ₹{{ number_format($gstr3b['outward_taxable_supplies']['taxable_value'] ?? 0, 2) }}
            </div>
            <div class="text-[11px] text-slate-500 mt-0.5">B2C Retail & B2B Wholesale</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Output GST Collected</div>
            <div class="text-lg font-black text-indigo-700 font-mono mt-1">
                ₹{{ number_format(($gstr3b['outward_taxable_supplies']['cgst'] ?? 0) + ($gstr3b['outward_taxable_supplies']['sgst'] ?? 0) + ($gstr3b['outward_taxable_supplies']['igst'] ?? 0), 2) }}
            </div>
            <div class="text-[11px] text-slate-500 mt-0.5">Gross Tax on Sales</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Eligible ITC (Purchases)</div>
            <div class="text-lg font-black text-emerald-700 font-mono mt-1">
                ₹{{ number_format(($gstr3b['eligible_itc']['cgst'] ?? 0) + ($gstr3b['eligible_itc']['sgst'] ?? 0) + ($gstr3b['eligible_itc']['igst'] ?? 0), 2) }}
            </div>
            <div class="text-[11px] text-emerald-600 mt-0.5">Input Tax Credit Available</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Net Cash Tax Payable</div>
            <div class="text-lg font-black text-slate-900 font-mono mt-1">
                ₹{{ number_format(($gstr3b['net_tax_payable']['cgst'] ?? 0) + ($gstr3b['net_tax_payable']['sgst'] ?? 0) + ($gstr3b['net_tax_payable']['igst'] ?? 0), 2) }}
            </div>
            <div class="text-[11px] text-slate-500 mt-0.5">Output Tax minus ITC</div>
        </div>
    </div>

    <!-- Section 1: GSTR-3B Table -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs space-y-3 mb-6">
        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-calculator text-indigo-600"></i> GSTR-3B Monthly Return Summary
        </h2>
        <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200">
                <tr>
                    <th class="py-2.5 px-3 font-bold">Nature of Supplies</th>
                    <th class="py-2.5 px-3 font-bold text-right">Taxable Value</th>
                    <th class="py-2.5 px-3 font-bold text-right">Integrated Tax (IGST)</th>
                    <th class="py-2.5 px-3 font-bold text-right">Central Tax (CGST)</th>
                    <th class="py-2.5 px-3 font-bold text-right">State Tax (SGST)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-mono">
                <tr>
                    <td class="py-2.5 px-3 font-bold text-slate-800 font-sans">3.1 (a) Outward Taxable Supplies</td>
                    <td class="py-2.5 px-3 text-right">₹{{ number_format($gstr3b['outward_taxable_supplies']['taxable_value'] ?? 0, 2) }}</td>
                    <td class="py-2.5 px-3 text-right">₹{{ number_format($gstr3b['outward_taxable_supplies']['igst'] ?? 0, 2) }}</td>
                    <td class="py-2.5 px-3 text-right">₹{{ number_format($gstr3b['outward_taxable_supplies']['cgst'] ?? 0, 2) }}</td>
                    <td class="py-2.5 px-3 text-right">₹{{ number_format($gstr3b['outward_taxable_supplies']['sgst'] ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td class="py-2.5 px-3 font-bold text-emerald-800 font-sans">4 (A) Eligible ITC on Purchases</td>
                    <td class="py-2.5 px-3 text-right">-</td>
                    <td class="py-2.5 px-3 text-right text-emerald-700">₹{{ number_format($gstr3b['eligible_itc']['igst'] ?? 0, 2) }}</td>
                    <td class="py-2.5 px-3 text-right text-emerald-700">₹{{ number_format($gstr3b['eligible_itc']['cgst'] ?? 0, 2) }}</td>
                    <td class="py-2.5 px-3 text-right text-emerald-700">₹{{ number_format($gstr3b['eligible_itc']['sgst'] ?? 0, 2) }}</td>
                </tr>
                <tr class="bg-slate-50 font-bold">
                    <td class="py-2.5 px-3 text-slate-900 font-sans">6.1 Net Tax Payable in Cash</td>
                    <td class="py-2.5 px-3 text-right">-</td>
                    <td class="py-2.5 px-3 text-right">₹{{ number_format($gstr3b['net_tax_payable']['igst'] ?? 0, 2) }}</td>
                    <td class="py-2.5 px-3 text-right">₹{{ number_format($gstr3b['net_tax_payable']['cgst'] ?? 0, 2) }}</td>
                    <td class="py-2.5 px-3 text-right">₹{{ number_format($gstr3b['net_tax_payable']['sgst'] ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Section 2: HSN-wise Summary of Outward Supplies -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs space-y-3">
        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-list-ol text-indigo-600"></i> HSN-wise Summary (Table 12 of GSTR-1)
        </h2>
        <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200">
                <tr>
                    <th class="py-2.5 px-3 font-bold">HSN Code</th>
                    <th class="py-2.5 px-3 font-bold">Description</th>
                    <th class="py-2.5 px-3 font-bold text-center">UQC</th>
                    <th class="py-2.5 px-3 font-bold text-right">Total Qty</th>
                    <th class="py-2.5 px-3 font-bold text-right">Taxable Value</th>
                    <th class="py-2.5 px-3 font-bold text-right">CGST</th>
                    <th class="py-2.5 px-3 font-bold text-right">SGST</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-mono">
                @forelse($hsnSummary as $hsn)
                <tr>
                    <td class="py-2.5 px-3 font-bold text-slate-900">{{ $hsn['hsn_code'] }}</td>
                    <td class="py-2.5 px-3 font-sans text-slate-600">{{ $hsn['description'] ?? 'Medicaments' }}</td>
                    <td class="py-2.5 px-3 text-center uppercase">{{ $hsn['uqc'] ?? 'NOS' }}</td>
                    <td class="py-2.5 px-3 text-right">{{ $hsn['total_quantity'] }}</td>
                    <td class="py-2.5 px-3 text-right">₹{{ number_format($hsn['taxable_value'] ?? 0, 2) }}</td>
                    <td class="py-2.5 px-3 text-right">₹{{ number_format($hsn['central_tax'] ?? $hsn['cgst_amount'] ?? 0, 2) }}</td>
                    <td class="py-2.5 px-3 text-right">₹{{ number_format($hsn['state_tax'] ?? $hsn['sgst_amount'] ?? 0, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-6 text-slate-400 font-sans">No outward supplies recorded for the period.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
