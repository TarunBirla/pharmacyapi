@extends('layouts.app')

@section('title', "Invoice #{$invoice->invoice_number}")

@section('content')
<div class="flex-1 p-4 md:p-6 bg-slate-50 overflow-y-auto">
    <div class="max-w-4xl mx-auto space-y-4">
        
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <div class="flex items-center gap-2">
                <a href="{{ route('sales.index') }}" class="text-slate-400 hover:text-slate-700 text-sm">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
                <h1 class="text-lg font-bold text-slate-900 font-mono">Invoice #{{ $invoice->invoice_number }}</h1>
            </div>

            <div class="flex items-center gap-2">
                <button onclick="openReturnModal()" class="bg-rose-50 border border-rose-200 hover:bg-rose-100 text-rose-700 font-bold px-3 py-1.5 rounded-xl text-xs transition">
                    <i class="fa-solid fa-rotate-left mr-1"></i> Process Return
                </button>
                <a href="{{ route('pos.receipt', $invoice) }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3.5 py-1.5 rounded-xl text-xs shadow transition">
                    <i class="fa-solid fa-print mr-1"></i> Print Receipt
                </a>
            </div>
        </div>

        <!-- Invoice Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-6">
            <!-- Metadata Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs pb-4 border-b border-slate-100">
                <div>
                    <div class="text-[10px] text-slate-400 uppercase font-semibold">Date & Time</div>
                    <div class="font-bold text-slate-800 font-mono mt-0.5">{{ $invoice->invoice_date }} {{ $invoice->created_at->format('H:i') }}</div>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 uppercase font-semibold">Customer / Patient</div>
                    <div class="font-bold text-slate-800 mt-0.5">{{ $invoice->customer->name ?? 'Walk-in Cash Customer' }}</div>
                    <div class="text-[11px] text-slate-500 font-mono">{{ $invoice->customer->phone ?? '' }}</div>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 uppercase font-semibold">Prescribing Doctor</div>
                    <div class="font-bold text-slate-800 mt-0.5">{{ $invoice->doctor->name ?? 'Self / Direct' }}</div>
                    <div class="text-[11px] text-slate-500 font-mono">{{ $invoice->doctor->registration_number ?? '' }}</div>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 uppercase font-semibold">Rx Number</div>
                    <div class="font-bold text-slate-800 font-mono mt-0.5">{{ $invoice->prescription_number ?? 'N/A' }}</div>
                </div>
            </div>

            <!-- Items Table -->
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-2.5 px-3 font-bold">Item Name</th>
                        <th class="py-2.5 px-3 font-bold">Batch & Expiry</th>
                        <th class="py-2.5 px-3 font-bold text-center">Qty</th>
                        <th class="py-2.5 px-3 font-bold text-right">Unit Price</th>
                        <th class="py-2.5 px-3 font-bold text-right">GST Rate</th>
                        <th class="py-2.5 px-3 font-bold text-right">Total Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                    @foreach($invoice->items as $item)
                    <tr>
                        <td class="py-3 px-3 font-bold text-slate-900">{{ $item->product->name }}</td>
                        <td class="py-3 px-3 font-mono text-slate-600">
                            <div>Batch: {{ $item->batch->batch_number ?? 'B1' }}</div>
                            <div class="text-[10px] text-slate-400">Exp: {{ $item->batch?->expiry_date }}</div>
                        </td>
                        <td class="py-3 px-3 text-center font-mono font-bold text-slate-800">{{ $item->quantity }}</td>
                        <td class="py-3 px-3 text-right font-mono text-slate-600">₹{{ number_format($item->unit_price, 2) }}</td>
                        <td class="py-3 px-3 text-right font-mono text-slate-600">{{ $item->gst_rate }}%</td>
                        <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">₹{{ number_format($item->total_amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Totals & Payments -->
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 pt-4 border-t border-slate-100 text-xs">
                <div>
                    <div class="font-bold text-slate-700 mb-1">Payment Tendered:</div>
                    @foreach($invoice->payments as $p)
                        <div class="font-mono text-slate-600">
                            <span class="uppercase font-semibold">{{ $p->payment_method }}:</span> ₹{{ number_format($p->amount, 2) }}
                        </div>
                    @endforeach
                </div>

                <div class="w-64 space-y-1.5 font-mono">
                    <div class="flex justify-between text-slate-500">
                        <span>Taxable Value:</span>
                        <span>₹{{ number_format($invoice->taxable_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-500">
                        <span>CGST:</span>
                        <span>₹{{ number_format($invoice->cgst_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-500">
                        <span>SGST:</span>
                        <span>₹{{ number_format($invoice->sgst_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-900 font-black text-sm border-t border-slate-200 pt-1.5">
                        <span>Net Paid:</span>
                        <span class="text-emerald-700">₹{{ number_format($invoice->net_amount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Return Modal -->
<div id="returnModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
            <div class="font-bold text-sm text-slate-900">Process Sales Return & Restock</div>
            <button onclick="closeReturnModal()" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('sales.return', $invoice) }}" method="POST" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 mb-1">Return Reason</label>
                <input type="text" name="reason" value="Patient prescription change / excess medicine" required class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2">
            </div>

            <div class="space-y-2">
                <label class="block font-bold text-slate-700">Select Item & Return Quantity</label>
                @foreach($invoice->items as $idx => $it)
                <div class="p-2 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="font-bold text-slate-800">{{ $it->product->name }}</div>
                        <div class="text-[10px] text-slate-500">Sold: {{ $it->quantity }} units</div>
                    </div>
                    <input type="hidden" name="items[{{ $idx }}][sales_invoice_item_id]" value="{{ $it->id }}">
                    <input type="number" step="1" min="0" max="{{ $it->quantity }}" name="items[{{ $idx }}][return_quantity]" value="1" class="w-20 bg-white border border-slate-200 rounded p-1 text-right font-mono font-bold">
                </div>
                @endforeach
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Refund Mode</label>
                <select name="refund_mode" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-semibold">
                    <option value="cash">Cash Refund</option>
                    <option value="credit_note">Issue Credit Note</option>
                    <option value="bank_transfer">Bank / UPI Transfer</option>
                </select>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" onclick="closeReturnModal()" class="px-4 py-2 rounded-xl border border-slate-200 font-bold text-slate-600">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold">Confirm Return & Restock</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openReturnModal() { document.getElementById('returnModal').classList.remove('hidden'); }
    function closeReturnModal() { document.getElementById('returnModal').classList.add('hidden'); }
</script>
@endsection
