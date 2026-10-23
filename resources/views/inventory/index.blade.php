@extends('layouts.app')

@section('title', 'Medicine Master & Batch Inventory')

@section('content')
<div class="flex-1 flex flex-col p-4 md:p-6 bg-slate-50 overflow-hidden">
    
    <!-- Top Action Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-slate-200 gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-pills text-blue-600"></i> Medicine Master & Batch Inventory
            </h1>
            <p class="text-xs text-slate-500">
                Centralized drug formulary, Indian drug schedules (H/X/OTC), HSN tax codes, and FEFO inventory ledger.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <button onclick="openAddMedicineModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3.5 py-2 rounded-xl text-xs shadow-sm shadow-emerald-600/20 transition flex items-center gap-1.5">
                <i class="fa-solid fa-plus"></i> Add New Medicine
            </button>
        </div>
    </div>

    <!-- Navigation Tabs & Search Filter -->
    <div class="flex flex-col sm:flex-row items-center justify-between py-3 gap-3">
        <!-- Sub Tabs -->
        <div class="flex bg-slate-200/70 p-1 rounded-xl text-xs font-semibold w-full sm:w-auto">
            <a href="{{ route('inventory.index', ['tab' => 'catalog']) }}" 
               class="px-3.5 py-1.5 rounded-lg transition {{ $tab === 'catalog' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                <i class="fa-solid fa-boxes-stacked mr-1"></i> Full Catalog ({{ $products->total() }})
            </a>
            <a href="{{ route('inventory.index', ['tab' => 'low_stock']) }}" 
               class="px-3.5 py-1.5 rounded-lg transition {{ $tab === 'low_stock' ? 'bg-white text-rose-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                <i class="fa-solid fa-triangle-exclamation mr-1 text-rose-500"></i> Low Stock ({{ $lowStockProducts->count() }})
            </a>
            <a href="{{ route('inventory.index', ['tab' => 'expiring']) }}" 
               class="px-3.5 py-1.5 rounded-lg transition {{ $tab === 'expiring' ? 'bg-white text-amber-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                <i class="fa-solid fa-clock-rotate-left mr-1 text-amber-500"></i> Expiring &lt;90 Days ({{ $expiringBatches->count() }})
            </a>
        </div>

        <!-- Search Bar -->
        <form action="{{ route('inventory.index') }}" method="GET" class="relative w-full sm:w-72">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
            <input type="text" 
                   name="search" 
                   value="{{ $search }}" 
                   placeholder="Search medicine, salt, HSN..." 
                   class="w-full bg-white border border-slate-200 rounded-xl pl-9 pr-4 py-1.5 text-xs text-slate-900 focus:outline-none focus:border-blue-600 transition" />
        </form>
    </div>

    <!-- MAIN DATA TABLE -->
    <div class="flex-1 bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden flex flex-col">
        @if($tab === 'catalog')
        <div class="flex-1 overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200 sticky top-0">
                    <tr>
                        <th class="py-2.5 px-4 font-bold">Medicine Name & Salt</th>
                        <th class="py-2.5 px-4 font-bold">HSN / GST</th>
                        <th class="py-2.5 px-4 font-bold">Classification</th>
                        <th class="py-2.5 px-4 font-bold text-right">Cost Rate</th>
                        <th class="py-2.5 px-4 font-bold text-right">MRP</th>
                        <th class="py-2.5 px-4 font-bold text-right">Current Stock</th>
                        <th class="py-2.5 px-4 font-bold">Rack/Shelf</th>
                        <th class="py-2.5 px-4 font-bold text-center">Substitutes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                    @forelse($products as $prod)
                    @php $stock = $prod->batches->sum('quantity_available'); @endphp
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-2.5 px-4">
                            <div class="font-bold text-slate-900">{{ $prod->name }}</div>
                            <div class="text-[11px] text-slate-400 truncate">{{ $prod->generic_name ?? $prod->brand }}</div>
                        </td>
                        <td class="py-2.5 px-4 font-mono text-slate-600">
                            <div>{{ $prod->hsn_code }}</div>
                            <div class="text-[10px] text-slate-400">{{ $prod->gst_rate }}% GST</div>
                        </td>
                        <td class="py-2.5 px-4">
                            @if(in_array($prod->prescription_type, ['schedule_h', 'schedule_h1']))
                                <span class="bg-rose-50 text-rose-700 border border-rose-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase">Schedule H</span>
                            @elseif($prod->prescription_type === 'schedule_x')
                                <span class="bg-purple-50 text-purple-700 border border-purple-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase">Schedule X</span>
                            @else
                                <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px] font-semibold uppercase">OTC</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-4 text-right font-mono text-slate-600">₹{{ number_format($prod->purchase_rate, 2) }}</td>
                        <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900">₹{{ number_format($prod->mrp, 2) }}</td>
                        <td class="py-2.5 px-4 text-right font-mono">
                            <span class="px-2 py-0.5 rounded font-bold {{ $stock <= $prod->reorder_level ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-700' }}">
                                {{ $stock }} units
                            </span>
                        </td>
                        <td class="py-2.5 px-4 font-mono text-slate-600">
                            {{ $prod->rack_location ? $prod->rack_location . '-' . $prod->shelf_location : '-' }}
                        </td>
                        <td class="py-2.5 px-4 text-center">
                            @if($prod->generic_name)
                            <button onclick="viewSubstitutes({{ $prod->id }})" class="text-blue-600 hover:text-blue-800 text-[11px] font-bold">
                                <i class="fa-solid fa-code-compare mr-1"></i> Generic Alts
                            </button>
                            @else
                            <span class="text-slate-300">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-12 text-slate-400">No medicines found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-slate-100">
            {{ $products->links() }}
        </div>

        @elseif($tab === 'low_stock')
        <div class="flex-1 overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-rose-50 text-rose-800 uppercase tracking-wider border-b border-rose-200">
                    <tr>
                        <th class="py-2.5 px-4 font-bold">Medicine</th>
                        <th class="py-2.5 px-4 font-bold">Reorder Threshold</th>
                        <th class="py-2.5 px-4 font-bold text-right">Available Stock</th>
                        <th class="py-2.5 px-4 font-bold text-right">Shortage</th>
                        <th class="py-2.5 px-4 font-bold text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                    @forelse($lowStockProducts as $lp)
                    @php 
                        $curStock = $lp->batches->sum('quantity_available'); 
                        $shortage = max(0, $lp->reorder_level - $curStock);
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 font-bold text-slate-900">{{ $lp->name }} ({{ $lp->dosage_form }})</td>
                        <td class="py-3 px-4 font-mono text-slate-600">{{ $lp->reorder_level }} units</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-rose-600">{{ $curStock }} units</td>
                        <td class="py-3 px-4 text-right font-mono text-rose-700 font-bold">-{{ $shortage }} units</td>
                        <td class="py-3 px-4 text-center">
                            <a href="{{ route('procurement.index', ['tab' => 'invoices']) }}" class="bg-blue-50 text-blue-700 hover:bg-blue-100 px-3 py-1 rounded-lg text-xs font-bold">
                                + Create PO
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 text-slate-400">All medicines have healthy stock above reorder thresholds.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @elseif($tab === 'expiring')
        <div class="flex-1 overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-amber-50 text-amber-800 uppercase tracking-wider border-b border-amber-200">
                    <tr>
                        <th class="py-2.5 px-4 font-bold">Medicine & Batch Number</th>
                        <th class="py-2.5 px-4 font-bold">Expiry Date</th>
                        <th class="py-2.5 px-4 font-bold text-right">Remaining Days</th>
                        <th class="py-2.5 px-4 font-bold text-right">Available Qty</th>
                        <th class="py-2.5 px-4 font-bold text-right">Cost Exposure</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                    @forelse($expiringBatches as $eb)
                    @php 
                        $daysLeft = \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($eb->expiry_date), false);
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900">{{ $eb->product->name }}</div>
                            <div class="text-[11px] text-slate-500 font-mono">Batch: {{ $eb->batch_number }}</div>
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-700 font-bold">{{ $eb->expiry_date }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold {{ $daysLeft <= 30 ? 'text-rose-600' : 'text-amber-600' }}">
                            {{ $daysLeft > 0 ? $daysLeft . ' days left' : 'EXPIRED' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-800">{{ $eb->quantity_available }} units</td>
                        <td class="py-3 px-4 text-right font-mono text-slate-900">₹{{ number_format($eb->quantity_available * $eb->purchase_cost, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 text-slate-400">No batches expiring within the next 90 days.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<!-- MODAL: Add New Medicine Form -->
<div id="addMedicineModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-slate-200 rounded-2xl max-w-2xl w-full p-6 space-y-4 shadow-2xl overflow-y-auto max-h-[90vh]">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="font-bold text-base text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-prescription-bottle-medical text-emerald-600"></i> Register New Drug in Catalog
            </div>
            <button onclick="closeAddMedicineModal()" class="text-slate-400 hover:text-slate-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('inventory.products.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2 sm:col-span-1">
                    <label class="block font-bold text-slate-700 mb-1">Brand Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Augmentin 625 Duo" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-medium">
                </div>

                <div class="col-span-2 sm:col-span-1">
                    <label class="block font-bold text-slate-700 mb-1">Generic Salt / Composition</label>
                    <input type="text" name="generic_name" placeholder="e.g. Amoxicillin + Clavulanic Acid" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-medium">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Dosage Form</label>
                    <select name="dosage_form" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2">
                        <option value="tablet">Tablet</option>
                        <option value="capsule">Capsule</option>
                        <option value="syrup">Syrup</option>
                        <option value="injection">Injection</option>
                        <option value="ointment">Ointment</option>
                        <option value="drops">Eye/Ear Drops</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Strength</label>
                    <input type="text" name="strength" placeholder="e.g. 500mg, 10ml" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Barcode / EAN</label>
                    <input type="text" name="barcode" placeholder="Scan or enter barcode" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-mono">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">HSN Code *</label>
                    <input type="text" name="hsn_code" value="3004" required class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-mono">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Applicable GST Rate (%)</label>
                    <select name="gst_rate" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-mono">
                        <option value="12">12% (Standard Formulations)</option>
                        <option value="5">5% (Life Saving / Vaccines)</option>
                        <option value="18">18% (Supplements / Cosmetics)</option>
                        <option value="0">0% (Exempted)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Prescription Classification</label>
                    <select name="prescription_type" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2">
                        <option value="otc">OTC (Over the Counter)</option>
                        <option value="schedule_h">Schedule H (Antibiotic / Rx)</option>
                        <option value="schedule_h1">Schedule H1 (High Alert Rx)</option>
                        <option value="schedule_x">Schedule X (Psychotropic / Narcotic)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Purchase Rate (₹) *</label>
                    <input type="number" step="0.01" name="purchase_rate" required placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-mono">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Retail Selling Price (₹) *</label>
                    <input type="number" step="0.01" name="retail_price" required placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-mono">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">MRP (₹) *</label>
                    <input type="number" step="0.01" name="mrp" required placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-mono">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Reorder Level (Safety Stock)</label>
                    <input type="number" name="reorder_level" value="20" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-mono">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Strip Pack Unit</label>
                    <input type="text" name="package_unit" value="Strip" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Loose Unit / Pack Conversion</label>
                    <div class="flex gap-2">
                        <input type="text" name="loose_unit" value="Tablet" class="w-1/2 bg-slate-50 border border-slate-200 rounded-lg p-2">
                        <input type="number" name="conversion_factor" value="10" placeholder="Factor" class="w-1/2 bg-slate-50 border border-slate-200 rounded-lg p-2 font-mono">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Rack Location</label>
                    <input type="text" name="rack_location" placeholder="e.g. A2" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Shelf Location</label>
                    <input type="text" name="shelf_location" placeholder="e.g. S1" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" onclick="closeAddMedicineModal()" class="px-4 py-2 rounded-xl border border-slate-200 font-bold text-slate-600 hover:bg-slate-100">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow">
                    Save Medicine
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddMedicineModal() { document.getElementById('addMedicineModal').classList.remove('hidden'); }
    function closeAddMedicineModal() { document.getElementById('addMedicineModal').classList.add('hidden'); }
    function viewSubstitutes(productId) {
        window.location.href = `/inventory?search=&tab=catalog#prod-${productId}`;
    }
</script>
@endsection
