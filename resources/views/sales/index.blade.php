@extends('layouts.app')

@section('title', 'Sales Invoices & Counter Billing History')

@section('content')
<div class="flex-1 flex flex-col p-4 md:p-6 bg-slate-50 overflow-hidden">
    
    <!-- Top Action Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-slate-200 gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-invoice text-amber-600"></i> Sales Register & Invoices
            </h1>
            <p class="text-xs text-slate-500">
                Audit trail of posted POS receipts, customer split payments, tax components, and sales returns.
            </p>
        </div>

        <!-- Filter Form -->
        <form action="{{ route('sales.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
            <input type="date" name="date" value="{{ $date }}" class="bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-800 font-mono focus:outline-none">
            <input type="text" name="search" value="{{ $search }}" placeholder="Invoice # or customer..." class="bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-800 focus:outline-none w-48">
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold px-3 py-1.5 rounded-xl text-xs transition">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            @if($search || $date)
                <a href="{{ route('sales.index') }}" class="text-slate-400 hover:text-slate-700 text-xs px-2">Clear</a>
            @endif
        </form>
    </div>

    <!-- MAIN DATA TABLE -->
    <div class="flex-1 bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden flex flex-col mt-4">
        <div class="flex-1 overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200 sticky top-0">
                    <tr>
                        <th class="py-2.5 px-4 font-bold">Invoice Number</th>
                        <th class="py-2.5 px-4 font-bold">Date & Time</th>
                        <th class="py-2.5 px-4 font-bold">Customer</th>
                        <th class="py-2.5 px-4 font-bold">Items Dispensed</th>
                        <th class="py-2.5 px-4 font-bold text-right">Taxable</th>
                        <th class="py-2.5 px-4 font-bold text-right">GST</th>
                        <th class="py-2.5 px-4 font-bold text-right">Net Bill</th>
                        <th class="py-2.5 px-4 font-bold text-center">Payment</th>
                        <th class="py-2.5 px-4 font-bold text-center">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                    @forelse($invoices as $inv)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3 px-4">
                            <a href="{{ route('sales.show', $inv) }}" class="font-bold text-blue-600 hover:underline font-mono">
                                #{{ $inv->invoice_number }}
                            </a>
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-600">
                            <div>{{ $inv->invoice_date }}</div>
                            <div class="text-[10px] text-slate-400">{{ $inv->created_at->format('H:i A') }}</div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900">{{ $inv->customer->name ?? 'Walk-in Cash' }}</div>
                            <div class="text-[10px] text-slate-400 font-mono">{{ $inv->customer->phone ?? '' }}</div>
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-700">
                            {{ $inv->items->count() }} items ({{ $inv->items->sum('quantity') }} units)
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-slate-600">₹{{ number_format($inv->taxable_amount, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono text-slate-600">₹{{ number_format($inv->cgst_amount + $inv->sgst_amount, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-black text-emerald-700 text-sm">₹{{ number_format($inv->net_amount, 2) }}</td>
                        <td class="py-3 px-4 text-center">
                            @foreach($inv->payments as $p)
                                <span class="bg-slate-100 text-slate-700 font-mono text-[10px] px-1.5 py-0.5 rounded uppercase">
                                    {{ $p->payment_method }}
                                </span>
                            @endforeach
                        </td>
                        <td class="py-3 px-4 text-center">
                            <a href="{{ route('pos.receipt', $inv) }}" target="_blank" class="text-emerald-700 hover:text-emerald-900 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1">
                                <i class="fa-solid fa-print"></i> Print
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-12 text-slate-400">No sales invoices match the selected filter.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-slate-100">
            {{ $invoices->links() }}
        </div>
    </div>
</div>
@endsection
