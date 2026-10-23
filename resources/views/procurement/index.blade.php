@extends('layouts.app')

@section('title', 'Purchases & Supplier Procurement')

@section('content')
<div class="flex-1 flex flex-col p-4 md:p-6 bg-slate-50 overflow-hidden">
    
    <!-- Top Action Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-slate-200 gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-truck-ramp-box text-purple-600"></i> Purchasing & Supplier Management
            </h1>
            <p class="text-xs text-slate-500">
                Purchase orders, supplier tax bills, goods receipt batch validation, and automated accounts payable ledger.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <button onclick="openSupplierModal()" class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-1.5 shadow-2xs">
                <i class="fa-solid fa-user-plus text-purple-600"></i> Register Supplier
            </button>
            <button onclick="openInvoiceModal()" class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-3.5 py-2 rounded-xl text-xs shadow-sm shadow-purple-600/20 transition flex items-center gap-1.5">
                <i class="fa-solid fa-file-circle-plus"></i> Inward Supplier Bill
            </button>
        </div>
    </div>

    <!-- Sub Tabs -->
    <div class="flex py-3">
        <div class="flex bg-slate-200/70 p-1 rounded-xl text-xs font-semibold">
            <a href="{{ route('procurement.index', ['tab' => 'invoices']) }}" 
               class="px-3.5 py-1.5 rounded-lg transition {{ $tab === 'invoices' ? 'bg-white text-purple-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                <i class="fa-solid fa-file-invoice mr-1"></i> Supplier Invoices
            </a>
            <a href="{{ route('procurement.index', ['tab' => 'suppliers']) }}" 
               class="px-3.5 py-1.5 rounded-lg transition {{ $tab === 'suppliers' ? 'bg-white text-purple-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                <i class="fa-solid fa-building mr-1"></i> Supplier Directory ({{ $suppliers->count() }})
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT TABLE -->
    <div class="flex-1 bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden flex flex-col">
        @if($tab === 'invoices')
        <div class="flex-1 overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200 sticky top-0">
                    <tr>
                        <th class="py-2.5 px-4 font-bold">Invoice / Supplier</th>
                        <th class="py-2.5 px-4 font-bold">Invoice Date</th>
                        <th class="py-2.5 px-4 font-bold">Payment Due</th>
                        <th class="py-2.5 px-4 font-bold text-right">Taxable</th>
                        <th class="py-2.5 px-4 font-bold text-right">GST (ITC)</th>
                        <th class="py-2.5 px-4 font-bold text-right">Net Payable</th>
                        <th class="py-2.5 px-4 font-bold text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                    @forelse($invoices as $inv)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900 font-mono">#{{ $inv->supplier_invoice_number }}</div>
                            <div class="text-[11px] text-slate-500 font-sans">{{ $inv->supplier->name }} (GSTIN: {{ $inv->supplier->gstin ?? 'N/A' }})</div>
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-600">{{ $inv->invoice_date }}</td>
                        <td class="py-3 px-4 font-mono text-slate-600">{{ $inv->due_date ?? '-' }}</td>
                        <td class="py-3 px-4 text-right font-mono text-slate-600">₹{{ number_format($inv->taxable_amount, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono text-emerald-700">₹{{ number_format($inv->cgst_amount + $inv->sgst_amount + $inv->igst_amount, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">₹{{ number_format($inv->net_amount, 2) }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $inv->payment_status === 'paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $inv->payment_status ?? 'posted' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-12 text-slate-400">No purchase invoices recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-slate-100">
            {{ $invoices->links() }}
        </div>

        @elseif($tab === 'suppliers')
        <div class="flex-1 overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-2.5 px-4 font-bold">Supplier Name</th>
                        <th class="py-2.5 px-4 font-bold">Contact / Phone</th>
                        <th class="py-2.5 px-4 font-bold">GSTIN & License</th>
                        <th class="py-2.5 px-4 font-bold text-center">Bills Inwarded</th>
                        <th class="py-2.5 px-4 font-bold text-right">Outstanding Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                    @forelse($suppliers as $sup)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900">{{ $sup->name }}</div>
                            <div class="text-[11px] text-slate-400">{{ $sup->city ?? 'Central Depot' }}</div>
                        </td>
                        <td class="py-3 px-4">
                            <div>{{ $sup->contact_person ?? '-' }}</div>
                            <div class="text-[11px] text-slate-500 font-mono">{{ $sup->phone ?? '-' }}</div>
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-600">
                            <div>GST: {{ $sup->gstin ?? 'Unregistered' }}</div>
                            <div class="text-[10px] text-slate-400">DL: {{ $sup->drug_license_number ?? '-' }}</div>
                        </td>
                        <td class="py-3 px-4 text-center font-mono font-bold text-slate-700">
                            {{ $sup->purchase_invoices_count }} bills
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-purple-700">
                            ₹{{ number_format($sup->current_balance ?? 0, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 text-slate-400">No registered suppliers.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<!-- MODAL 1: Add Supplier -->
<div id="supplierModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-slate-200 rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="font-bold text-base text-slate-900">Register Pharmaceutical Distributor</div>
            <button onclick="closeSupplierModal()" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('procurement.suppliers.store') }}" method="POST" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 mb-1">Company / Supplier Name *</label>
                <input type="text" name="name" required placeholder="e.g. Cipla Distribution Hub" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-medium">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">GSTIN Number</label>
                    <input type="text" name="gstin" placeholder="27AAACG1234F1Z1" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-mono uppercase">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Drug License (DL) No</label>
                    <input type="text" name="drug_license_number" placeholder="20B/21B-DL-998877" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Contact Person</label>
                    <input type="text" name="contact_person" placeholder="Manager Name" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Phone Number</label>
                    <input type="text" name="phone" placeholder="+91 98765 00000" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-mono">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" onclick="closeSupplierModal()" class="px-4 py-2 rounded-xl border border-slate-200 font-bold text-slate-600">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold">Save Supplier</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: Inward Purchase Invoice -->
<div id="invoiceModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-slate-200 rounded-2xl max-w-2xl w-full p-6 space-y-4 shadow-2xl overflow-y-auto max-h-[90vh]">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="font-bold text-base text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-invoice-dollar text-purple-600"></i> Inward Supplier Tax Invoice & Stock
            </div>
            <button onclick="closeInvoiceModal()" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('procurement.invoices.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Select Supplier *</label>
                    <select name="supplier_id" required class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2">
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Supplier Invoice Number *</label>
                    <input type="text" name="supplier_invoice_number" required placeholder="e.g. CIP-2026-8891" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Invoice Date *</label>
                    <input type="date" name="invoice_date" value="{{ date('Y-m-d') }}" required class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Due Date</label>
                    <input type="date" name="due_date" value="{{ date('Y-m-d', strtotime('+30 days')) }}" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-mono">
                </div>
            </div>

            <!-- Single Item Fast Entry -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                <div class="font-bold text-slate-700 uppercase text-[10px]">Inwarded Batch Item Details</div>
                <div class="grid grid-cols-3 gap-2">
                    <div class="col-span-3 sm:col-span-1">
                        <label class="block text-[10px] text-slate-500 mb-0.5">Medicine *</label>
                        <select name="items[0][product_id]" required class="w-full bg-white border border-slate-200 rounded-lg p-1.5">
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-500 mb-0.5">Batch Number *</label>
                        <input type="text" name="items[0][batch_number]" required placeholder="BTH-882" class="w-full bg-white border border-slate-200 rounded-lg p-1.5 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-500 mb-0.5">Expiry Date *</label>
                        <input type="date" name="items[0][expiry_date]" value="{{ date('Y-m-d', strtotime('+1 year')) }}" required class="w-full bg-white border border-slate-200 rounded-lg p-1.5 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-500 mb-0.5">Billed Quantity *</label>
                        <input type="number" step="1" name="items[0][billed_quantity]" value="100" required class="w-full bg-white border border-slate-200 rounded-lg p-1.5 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-500 mb-0.5">Purchase Rate (₹) *</label>
                        <input type="number" step="0.01" name="items[0][purchase_rate]" value="40.00" required class="w-full bg-white border border-slate-200 rounded-lg p-1.5 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-500 mb-0.5">Retail MRP (₹) *</label>
                        <input type="number" step="0.01" name="items[0][mrp]" value="75.00" required class="w-full bg-white border border-slate-200 rounded-lg p-1.5 font-mono">
                    </div>
                    <input type="hidden" name="items[0][gst_rate]" value="12">
                    <input type="hidden" name="items[0][free_quantity]" value="0">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" onclick="closeInvoiceModal()" class="px-4 py-2 rounded-xl border border-slate-200 font-bold text-slate-600">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold">Commit Invoice & Stock</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openSupplierModal() { document.getElementById('supplierModal').classList.remove('hidden'); }
    function closeSupplierModal() { document.getElementById('supplierModal').classList.add('hidden'); }
    function openInvoiceModal() { document.getElementById('invoiceModal').classList.remove('hidden'); }
    function closeInvoiceModal() { document.getElementById('invoiceModal').classList.add('hidden'); }
</script>
@endsection
