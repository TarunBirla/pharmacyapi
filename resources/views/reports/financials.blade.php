@extends('layouts.app')

@section('title', 'Financial Statements & Balance Sheet')

@section('content')
<div class="flex-1 flex flex-col p-4 md:p-6 bg-slate-50 overflow-y-auto">
    
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-slate-200 gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-scale-balanced text-teal-600"></i> Financial Accounting & Profit Reports
            </h1>
            <p class="text-xs text-slate-500">
                Double-entry standard general ledger statements: Trial Balance, Income Statement (P&L), and Balance Sheet.
            </p>
        </div>

        <form action="{{ route('reports.financials') }}" method="GET" class="flex items-center gap-2">
            <input type="date" name="as_of_date" value="{{ $asOfDate }}" class="bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-800 font-mono">
            <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-bold px-3 py-1.5 rounded-xl text-xs transition">
                As of Date
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-4">
        
        <!-- PROFIT & LOSS STATEMENT -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-emerald-600"></i> Income Statement (Profit & Loss)
                </h2>
                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded uppercase">Current Period</span>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <div class="font-bold text-slate-400 uppercase text-[10px] tracking-wider mb-1">Operating Revenue</div>
                    <div class="flex justify-between py-1 border-b border-slate-50 font-mono">
                        <span class="font-sans text-slate-700">Gross Sales (Retail & Wholesale)</span>
                        <span class="font-bold text-slate-900">₹{{ number_format($profitAndLoss['revenue'] ?? 0, 2) }}</span>
                    </div>
                </div>

                <div>
                    <div class="font-bold text-slate-400 uppercase text-[10px] tracking-wider mb-1">Cost of Goods Sold (COGS)</div>
                    <div class="flex justify-between py-1 border-b border-slate-50 font-mono">
                        <span class="font-sans text-slate-700">Cost of Batches Dispensed</span>
                        <span class="text-rose-600 font-bold">-₹{{ number_format($profitAndLoss['cogs'] ?? 0, 2) }}</span>
                    </div>
                </div>

                <div class="flex justify-between py-2 bg-emerald-50/60 rounded-xl px-3 font-mono font-bold text-emerald-900 border border-emerald-100">
                    <span class="font-sans">Gross Profit (Margin):</span>
                    <span>₹{{ number_format($profitAndLoss['gross_profit'] ?? 0, 2) }}</span>
                </div>

                <div>
                    <div class="font-bold text-slate-400 uppercase text-[10px] tracking-wider mb-1">Operating Expenses</div>
                    <div class="flex justify-between py-1 border-b border-slate-50 font-mono">
                        <span class="font-sans text-slate-700">Overheads & Miscellaneous</span>
                        <span class="text-slate-600">₹{{ number_format($profitAndLoss['operating_expenses'] ?? 0, 2) }}</span>
                    </div>
                </div>

                <div class="flex justify-between py-2.5 bg-slate-900 text-white rounded-xl px-4 font-mono font-black text-sm shadow-sm">
                    <span class="font-sans">NET OPERATING PROFIT:</span>
                    <span class="text-emerald-400">₹{{ number_format($profitAndLoss['net_profit'] ?? 0, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- BALANCE SHEET -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-building-columns text-teal-600"></i> Balance Sheet Summary
                </h2>
                <span class="text-[10px] font-bold text-teal-700 bg-teal-50 px-2 py-0.5 rounded uppercase">As of {{ $asOfDate }}</span>
            </div>

            <div class="space-y-4 text-xs font-mono">
                <!-- Assets -->
                <div class="space-y-1">
                    <div class="font-bold text-slate-400 uppercase text-[10px] tracking-wider font-sans">Total Assets</div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="font-sans text-slate-600">Cash in Hand & Bank Accounts</span>
                        <span>₹{{ number_format($balanceSheet['assets']['cash_and_bank'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="font-sans text-slate-600">Inventory Valuation (Holding Batches)</span>
                        <span>₹{{ number_format($balanceSheet['assets']['inventory'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="font-sans text-slate-600">Accounts Receivable (Customer Credit)</span>
                        <span>₹{{ number_format($balanceSheet['assets']['accounts_receivable'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 font-bold text-slate-900 bg-slate-50 px-2 rounded">
                        <span class="font-sans">Total Assets:</span>
                        <span>₹{{ number_format($balanceSheet['assets']['total_assets'] ?? 0, 2) }}</span>
                    </div>
                </div>

                <!-- Liabilities -->
                <div class="space-y-1">
                    <div class="font-bold text-slate-400 uppercase text-[10px] tracking-wider font-sans">Liabilities & Equity</div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="font-sans text-slate-600">Accounts Payable (Supplier Payables)</span>
                        <span>₹{{ number_format($balanceSheet['liabilities']['accounts_payable'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="font-sans text-slate-600">Duties & Taxes (Net GST Payable)</span>
                        <span>₹{{ number_format($balanceSheet['liabilities']['gst_payable'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 font-bold text-slate-900 bg-slate-50 px-2 rounded">
                        <span class="font-sans">Total Liabilities & Equity:</span>
                        <span>₹{{ number_format($balanceSheet['liabilities']['total_liabilities_and_equity'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- TRIAL BALANCE TABLE -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-list-check text-teal-600"></i> Standard Trial Balance Ledger
            </h2>
            <span class="text-xs font-bold font-mono {{ ($trialBalance['is_balanced'] ?? true) ? 'text-emerald-700 bg-emerald-50' : 'text-rose-700 bg-rose-50' }} px-2.5 py-1 rounded-full border">
                {{ ($trialBalance['is_balanced'] ?? true) ? '✓ BALANCED (Debits = Credits)' : '⚠ UNBALANCED' }}
            </span>
        </div>

        <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200">
                <tr>
                    <th class="py-2.5 px-3 font-bold">Account Code</th>
                    <th class="py-2.5 px-3 font-bold">Account Name</th>
                    <th class="py-2.5 px-3 font-bold">Category</th>
                    <th class="py-2.5 px-3 font-bold text-right">Debit Balance (₹)</th>
                    <th class="py-2.5 px-3 font-bold text-right">Credit Balance (₹)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-mono">
                @forelse($trialBalance['accounts'] ?? [] as $acc)
                <tr>
                    <td class="py-2 px-3 font-bold text-slate-800">{{ $acc['account_code'] ?? $acc['code'] ?? '' }}</td>
                    <td class="py-2 px-3 font-sans text-slate-900">{{ $acc['account_name'] ?? $acc['name'] ?? '' }}</td>
                    <td class="py-2 px-3 font-sans text-slate-400 capitalize">{{ $acc['account_type'] ?? $acc['category'] ?? '' }}</td>
                    <td class="py-2 px-3 text-right">{{ ($acc['debit'] ?? 0) > 0 ? number_format($acc['debit'], 2) : '-' }}</td>
                    <td class="py-2 px-3 text-right">{{ ($acc['credit'] ?? 0) > 0 ? number_format($acc['credit'], 2) : '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-6 text-center text-slate-400 font-sans">No ledger entries.</td></tr>
                @endforelse
                <tr class="bg-slate-50 font-bold border-t-2 border-slate-300">
                    <td colspan="3" class="py-2.5 px-3 font-sans text-slate-900">TOTAL:</td>
                    <td class="py-2.5 px-3 text-right text-emerald-700">₹{{ number_format($trialBalance['total_debit'] ?? 0, 2) }}</td>
                    <td class="py-2.5 px-3 text-right text-emerald-700">₹{{ number_format($trialBalance['total_credit'] ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

</div>
@endsection
