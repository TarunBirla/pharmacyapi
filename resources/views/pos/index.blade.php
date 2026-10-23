@extends('layouts.app')

@section('title', 'Retail POS Counter Billing')

@section('content')
<div class="flex-1 flex flex-col md:flex-row h-full overflow-hidden bg-slate-100">

    <!-- LEFT: Medicine Search, Barcode Scanner & Product Catalog -->
    <div class="w-full md:w-7/12 flex flex-col h-full bg-white border-r border-slate-200">
        
        <!-- Search & Barcode Header -->
        <div class="p-3 border-b border-slate-200 bg-white flex flex-col sm:flex-row gap-2 items-center">
            <div class="relative flex-1 w-full">
                <i class="fa-solid fa-barcode absolute left-3 top-2.5 text-slate-400"></i>
                <input type="text" 
                       id="barcodeInput" 
                       placeholder="Scan barcode or press [F1]..." 
                       class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-4 py-2 text-xs font-mono text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition"
                       autofocus />
            </div>

            <div class="relative flex-1 w-full">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400"></i>
                <input type="text" 
                       id="productSearchInput" 
                       placeholder="Search medicine name, salt or brand [F2]..." 
                       class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-4 py-2 text-xs text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition" />
            </div>

            <button type="button" 
                    onclick="openShiftModal()" 
                    class="px-3 py-2 rounded-lg border text-xs font-bold transition flex items-center gap-1.5 shrink-0 {{ $currentShift ? 'bg-emerald-50 border-emerald-300 text-emerald-800' : 'bg-amber-50 border-amber-300 text-amber-800' }}">
                <i class="fa-solid fa-vault"></i>
                <span>{{ $currentShift ? 'Shift Open' : 'Open Shift' }}</span>
            </button>
        </div>

        <!-- Medicine List / Cards (FEFO Batches) -->
        <div class="flex-1 overflow-y-auto p-3 grid grid-cols-1 sm:grid-cols-2 gap-2.5" id="productsGrid">
            @forelse($products as $prod)
            @php
                $eligibleBatches = $prod->batches;
                $totalStock = $eligibleBatches->sum('quantity_available');
                $firstBatch = $eligibleBatches->first();
            @endphp
            <div class="product-card bg-white border border-slate-200 hover:border-emerald-500 rounded-xl p-3 shadow-xs hover:shadow-md transition cursor-pointer flex flex-col justify-between"
                 onclick="addToCart({{ json_encode($prod) }})"
                 data-name="{{ strtolower($prod->name) }}"
                 data-generic="{{ strtolower($prod->generic_name ?? '') }}"
                 data-barcode="{{ $prod->barcode }}">
                
                <div>
                    <div class="flex items-start justify-between gap-1 mb-1">
                        <div class="font-bold text-xs text-slate-900 leading-snug">{{ $prod->name }}</div>
                        @if($prod->prescription_type === 'schedule_h' || $prod->prescription_type === 'schedule_h1')
                            <span class="text-[9px] font-bold bg-rose-50 text-rose-700 border border-rose-200 px-1.5 py-0.2 rounded uppercase shrink-0">
                                Sched-H
                            </span>
                        @elseif($prod->prescription_type === 'schedule_x')
                            <span class="text-[9px] font-bold bg-purple-50 text-purple-700 border border-purple-200 px-1.5 py-0.2 rounded uppercase shrink-0">
                                Sched-X
                            </span>
                        @else
                            <span class="text-[9px] font-semibold bg-slate-100 text-slate-600 px-1.5 py-0.2 rounded uppercase shrink-0">
                                OTC
                            </span>
                        @endif
                    </div>

                    <div class="text-[11px] text-slate-500 truncate mb-1">
                        {{ $prod->generic_name ?? $prod->brand }}
                    </div>

                    <div class="text-[10px] text-slate-400 font-mono flex items-center gap-2">
                        <span>HSN: {{ $prod->hsn_code }}</span>
                        <span>GST: {{ $prod->gst_rate }}%</span>
                        @if($prod->rack_location)
                            <span class="text-emerald-700"><i class="fa-solid fa-location-dot"></i> {{ $prod->rack_location }}-{{ $prod->shelf_location }}</span>
                        @endif
                    </div>
                </div>

                <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] text-slate-400">MRP: </span>
                        <span class="text-xs font-bold text-slate-800 font-mono">₹{{ number_format($prod->mrp, 2) }}</span>
                    </div>

                    <div class="text-right">
                        @if($totalStock > 0)
                            <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded font-mono">
                                {{ $totalStock }} in stock
                            </span>
                        @else
                            <span class="text-xs font-semibold text-rose-500 bg-rose-50 px-2 py-0.5 rounded font-mono">
                                Out of Stock
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-2 text-center py-12 text-slate-400 text-xs">
                No active medicines in stock. Please seed inventory or add purchase invoices.
            </div>
            @endforelse
        </div>
    </div>

    <!-- RIGHT: Active Billing Cart & Checkout -->
    <div class="w-full md:w-5/12 flex flex-col h-full bg-slate-50">
        
        <!-- Cart Header: Customer & Doctor details -->
        <div class="p-3 bg-white border-b border-slate-200 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-cart-shopping text-emerald-600"></i> Current Bill Cart
                </span>
                
                <div class="flex items-center gap-2">
                    <!-- Held Bills Dropdown -->
                    @if($heldBills->count() > 0)
                    <button type="button" 
                            onclick="openHeldBillsModal()" 
                            class="px-2.5 py-1 bg-amber-50 border border-amber-300 text-amber-800 rounded-lg text-xs font-bold hover:bg-amber-100 transition flex items-center gap-1">
                        <i class="fa-solid fa-pause"></i>
                        <span>{{ $heldBills->count() }} Held</span>
                    </button>
                    @endif

                    <button type="button" 
                            onclick="holdCurrentBill()" 
                            class="px-2.5 py-1 bg-slate-100 border border-slate-200 text-slate-700 rounded-lg text-xs font-semibold hover:bg-slate-200 transition"
                            title="Hold bill [F8]">
                        Hold [F8]
                    </button>

                    <button type="button" 
                            onclick="clearCart()" 
                            class="px-2 py-1 text-slate-400 hover:text-rose-600 rounded text-xs transition"
                            title="Clear cart">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>

            <!-- Customer & Doctor selection -->
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div>
                    <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-0.5">Patient / Customer</label>
                    <select id="cartCustomerId" onchange="handleCustomerChange(this)" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2 py-1.5 text-xs text-slate-800 focus:outline-none focus:border-emerald-600">
                        <option value="">Walk-in Customer (Cash)</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" data-phone="{{ $c->phone }}">{{ $c->name }} ({{ $c->phone }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-0.5">Prescribing Doctor</label>
                    <select id="cartDoctorId" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-2 py-1.5 text-xs text-slate-800 focus:outline-none focus:border-emerald-600">
                        <option value="">Dr. Self / Direct Sale</option>
                        @foreach($doctors as $d)
                            <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->registration_number }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Schedule H Warning Banner (conditional) -->
            <div id="scheduleWarningBanner" class="hidden p-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                    <span class="font-semibold text-[11px]">Schedule H Antibiotic in Cart: Rx required</span>
                </div>
                <input type="text" id="rxNumberInput" placeholder="Rx / Doc Reg #" class="bg-white border border-rose-300 rounded px-2 py-0.5 text-xs w-32 focus:outline-none">
            </div>
        </div>

        <!-- Cart Items Table -->
        <div class="flex-1 overflow-y-auto p-3">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-200 sticky top-0 bg-slate-50">
                    <tr>
                        <th class="pb-2 font-semibold">Medicine & Batch</th>
                        <th class="pb-2 font-semibold text-center">Qty</th>
                        <th class="pb-2 font-semibold text-right">MRP</th>
                        <th class="pb-2 font-semibold text-right">Total</th>
                        <th class="pb-2 font-semibold text-center"></th>
                    </tr>
                </thead>
                <tbody id="cartTableBody" class="divide-y divide-slate-200 font-sans">
                    <!-- Dynamic cart items rendered here -->
                </tbody>
            </table>

            <div id="emptyCartMessage" class="py-16 text-center text-slate-400 text-xs">
                <i class="fa-solid fa-cart-flatbed text-3xl mb-2 text-slate-300 block"></i>
                Cart is empty. Scan barcode or click a medicine to add.
            </div>
        </div>

        <!-- Cart Summary & Checkout Footer -->
        <div class="p-3 bg-white border-t border-slate-200 space-y-2">
            <div class="space-y-1 text-xs">
                <div class="flex justify-between text-slate-500">
                    <span>Taxable Amount:</span>
                    <span class="font-mono text-slate-800" id="summaryTaxable">₹0.00</span>
                </div>
                <div class="flex justify-between text-slate-500">
                    <span>Total GST (CGST + SGST):</span>
                    <span class="font-mono text-slate-800" id="summaryGst">₹0.00</span>
                </div>
                <div class="flex justify-between text-slate-500">
                    <span>Item Discount:</span>
                    <span class="font-mono text-slate-800" id="summaryDiscount">₹0.00</span>
                </div>
                <div class="flex justify-between items-center text-slate-900 border-t border-slate-100 pt-1.5 font-bold">
                    <span class="text-sm">Net Payable:</span>
                    <span class="text-lg font-black text-emerald-600 font-mono" id="summaryNetPayable">₹0.00</span>
                </div>
            </div>

            <button type="button" 
                    id="checkoutBtn"
                    onclick="openPaymentModal()" 
                    disabled 
                    class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white font-bold py-3 rounded-xl text-xs shadow-md shadow-emerald-600/20 transition flex items-center justify-center gap-2 cursor-pointer disabled:cursor-not-allowed">
                <i class="fa-solid fa-credit-card"></i>
                <span>PAY & COMMIT INVOICE [F4]</span>
            </button>
        </div>
    </div>
</div>

<!-- MODAL 1: Split Payment Modal -->
<div id="paymentModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-slate-200 rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="font-bold text-base text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-wallet text-emerald-600"></i> Split Tender Payment
            </div>
            <button onclick="closePaymentModal()" class="text-slate-400 hover:text-slate-700 text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 flex justify-between items-center">
            <span class="text-xs text-slate-500 font-semibold">Bill Net Amount:</span>
            <span class="text-xl font-black text-emerald-700 font-mono" id="modalBillAmount">₹0.00</span>
        </div>

        <!-- Payment Methods -->
        <div class="space-y-3 text-xs">
            <!-- Cash -->
            <div class="bg-white p-3 rounded-xl border border-slate-200 space-y-2">
                <div class="flex items-center justify-between">
                    <label class="font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-money-bill-wave text-emerald-600"></i> Cash Tender
                    </label>
                    <input type="number" id="payCash" step="0.01" oninput="calculateChange()" placeholder="0.00" class="w-32 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-right font-mono text-xs focus:outline-none focus:border-emerald-600">
                </div>
                <div class="flex justify-between text-[11px] text-slate-500 border-t border-slate-100 pt-1">
                    <span>Cash Return Change:</span>
                    <span class="font-bold font-mono text-emerald-700" id="cashChangeDisplay">₹0.00</span>
                </div>
            </div>

            <!-- UPI QR -->
            <div class="bg-white p-3 rounded-xl border border-slate-200 flex items-center justify-between">
                <label class="font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fa-solid fa-qrcode text-blue-600"></i> UPI (Instant QR)
                </label>
                <input type="number" id="payUpi" step="0.01" oninput="calculateChange()" placeholder="0.00" class="w-32 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-right font-mono text-xs focus:outline-none focus:border-emerald-600">
            </div>

            <!-- Card -->
            <div class="bg-white p-3 rounded-xl border border-slate-200 flex items-center justify-between">
                <label class="font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fa-solid fa-credit-card text-purple-600"></i> Debit / Credit Card
                </label>
                <input type="number" id="payCard" step="0.01" oninput="calculateChange()" placeholder="0.00" class="w-32 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-right font-mono text-xs focus:outline-none focus:border-emerald-600">
            </div>

            <!-- Customer Credit -->
            <div class="bg-white p-3 rounded-xl border border-slate-200 flex items-center justify-between">
                <label class="font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fa-solid fa-user-clock text-amber-600"></i> Customer Credit (Khata)
                </label>
                <input type="number" id="payCredit" step="0.01" oninput="calculateChange()" placeholder="0.00" class="w-32 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-right font-mono text-xs focus:outline-none focus:border-emerald-600">
            </div>
        </div>

        <div class="flex justify-between items-center text-xs pt-2">
            <span class="text-slate-500">Total Tendered: <strong id="totalTenderedDisplay" class="font-mono text-slate-900">₹0.00</strong></span>
            <span id="tenderBalanceDisplay" class="font-bold text-rose-600">Remaining: ₹0.00</span>
        </div>

        <button type="button" 
                id="submitPaymentBtn" 
                onclick="submitCheckout()" 
                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl text-xs shadow transition flex items-center justify-center gap-2">
            <i class="fa-solid fa-circle-check"></i>
            <span>Confirm Payment & Print Receipt</span>
        </button>
    </div>
</div>

<!-- MODAL 2: Cashier Shift Modal -->
<div id="shiftModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-slate-200 rounded-2xl max-w-sm w-full p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
            <div class="font-bold text-sm text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-cash-register text-emerald-600"></i> Cashier Drawer Shift
            </div>
            <button onclick="closeShiftModal()" class="text-slate-400 hover:text-slate-700 text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        @if($currentShift)
            <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-xs space-y-1">
                <div class="font-bold text-emerald-800">Active Shift: #{{ $currentShift->id }}</div>
                <div class="text-slate-600">Opened: {{ $currentShift->started_at }}</div>
                <div class="text-slate-600">Opening Cash: ₹{{ number_format($currentShift->opening_cash, 2) }}</div>
            </div>

            <form id="closeShiftForm" onsubmit="handleCloseShift(event)" class="space-y-3 text-xs">
                <input type="hidden" id="shiftIdInput" value="{{ $currentShift->id }}">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Physical Cash Counted in Drawer (₹)</label>
                    <input type="number" id="closingCashInput" step="0.01" required placeholder="0.00" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Shift Closing Notes</label>
                    <textarea id="closingNotesInput" rows="2" placeholder="Variance or closing remarks..." class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2"></textarea>
                </div>
                <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 rounded-xl">
                    Close Shift & Reconcile
                </button>
            </form>
        @else
            <form id="startShiftForm" onsubmit="handleStartShift(event)" class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Opening Cash Float (₹)</label>
                    <input type="number" id="openingFloatInput" step="0.01" value="2000.00" required class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 font-mono">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Opening Remarks</label>
                    <input type="text" id="openingNotesInput" placeholder="Morning counter shift" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2">
                </div>
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-xl">
                    Start Counter Shift
                </button>
            </form>
        @endif
    </div>
</div>

<!-- MODAL 3: Held Bills List Modal -->
<div id="heldBillsModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
            <div class="font-bold text-sm text-slate-900">Held Bills Queue</div>
            <button onclick="closeHeldBillsModal()" class="text-slate-400 hover:text-slate-700 text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="divide-y divide-slate-100 text-xs max-h-60 overflow-y-auto">
            @forelse($heldBills as $hb)
            <div class="py-2.5 flex items-center justify-between">
                <div>
                    <div class="font-bold text-slate-800 font-mono">{{ $hb->reference_code }}</div>
                    <div class="text-slate-500 text-[11px]">{{ $hb->customer_name }} • ₹{{ number_format($hb->total_amount, 2) }}</div>
                </div>
                <button onclick="resumeBill('{{ $hb->reference_code }}')" class="bg-emerald-50 text-emerald-700 border border-emerald-300 hover:bg-emerald-100 px-3 py-1 rounded-lg text-xs font-bold">
                    Resume [F9]
                </button>
            </div>
            @empty
            <div class="py-6 text-center text-slate-400">No held bills in queue.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let cart = [];
    let netPayable = 0;

    // Search filter
    document.getElementById('productSearchInput').addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => {
            const name = card.getAttribute('data-name');
            const generic = card.getAttribute('data-generic');
            if (name.includes(query) || generic.includes(query)) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    });

    // Barcode scanner auto-enter
    document.getElementById('barcodeInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const barcode = this.value.trim();
            if (!barcode) return;
            const matchCard = document.querySelector(`.product-card[data-barcode="${barcode}"]`);
            if (matchCard) {
                matchCard.click();
                this.value = '';
            } else {
                alert(`Medicine with barcode ${barcode} not found in inventory.`);
            }
        }
    });

    // Keyboard Hotkeys
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F1') {
            e.preventDefault();
            document.getElementById('barcodeInput').focus();
        } else if (e.key === 'F2') {
            e.preventDefault();
            document.getElementById('productSearchInput').focus();
        } else if (e.key === 'F4') {
            e.preventDefault();
            if (cart.length > 0) openPaymentModal();
        } else if (e.key === 'F8') {
            e.preventDefault();
            holdCurrentBill();
        } else if (e.key === 'Escape') {
            closePaymentModal();
            closeShiftModal();
            closeHeldBillsModal();
        }
    });

    function addToCart(product) {
        if (!product.batches || product.batches.length === 0) {
            alert(`No stock available for ${product.name}`);
            return;
        }

        // FEFO Selection: first batch has earliest expiry
        const batch = product.batches[0];

        const existingItem = cart.find(i => i.product_id === product.id && i.batch_id === batch.id);
        if (existingItem) {
            if (existingItem.quantity + 1 > batch.quantity_available) {
                alert(`Cannot exceed available batch stock of ${batch.quantity_available} units.`);
                return;
            }
            existingItem.quantity += 1;
        } else {
            cart.push({
                product_id: product.id,
                batch_id: batch.id,
                name: product.name,
                batch_number: batch.batch_number,
                expiry_date: batch.expiry_date,
                mrp: parseFloat(product.mrp),
                unit_price: parseFloat(product.retail_price || product.mrp),
                gst_rate: parseFloat(product.gst_rate || 0),
                prescription_type: product.prescription_type,
                discount_percent: 0,
                quantity: 1,
                max_stock: batch.quantity_available
            });
        }

        renderCart();
    }

    function renderCart() {
        const tbody = document.getElementById('cartTableBody');
        const emptyMsg = document.getElementById('emptyCartMessage');
        const checkoutBtn = document.getElementById('checkoutBtn');
        const scheduleBanner = document.getElementById('scheduleWarningBanner');

        tbody.innerHTML = '';

        if (cart.length === 0) {
            emptyMsg.classList.remove('hidden');
            checkoutBtn.disabled = true;
            scheduleBanner.classList.add('hidden');
            document.getElementById('summaryTaxable').innerText = '₹0.00';
            document.getElementById('summaryGst').innerText = '₹0.00';
            document.getElementById('summaryDiscount').innerText = '₹0.00';
            document.getElementById('summaryNetPayable').innerText = '₹0.00';
            netPayable = 0;
            return;
        }

        emptyMsg.classList.add('hidden');
        checkoutBtn.disabled = false;

        let totalTaxable = 0;
        let totalGst = 0;
        let totalDiscount = 0;
        let grandTotal = 0;
        let hasScheduleH = false;

        cart.forEach((item, index) => {
            if (item.prescription_type === 'schedule_h' || item.prescription_type === 'schedule_x') {
                hasScheduleH = true;
            }

            const itemGross = item.quantity * item.unit_price;
            const itemDisc = (itemGross * item.discount_percent) / 100;
            const itemNet = itemGross - itemDisc;
            
            // Tax calculation
            const taxFactor = item.gst_rate / (100 + item.gst_rate);
            const itemTax = itemNet * taxFactor;
            const itemTaxable = itemNet - itemTax;

            totalTaxable += itemTaxable;
            totalGst += itemTax;
            totalDiscount += itemDisc;
            grandTotal += itemNet;

            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-100/50';
            tr.innerHTML = `
                <td class="py-2">
                    <div class="font-bold text-slate-800">${item.name}</div>
                    <div class="text-[10px] text-slate-500 font-mono">B: ${item.batch_number} • Exp: ${item.expiry_date}</div>
                </td>
                <td class="py-2 text-center">
                    <input type="number" min="1" max="${item.max_stock}" value="${item.quantity}" 
                           onchange="updateQuantity(${index}, this.value)" 
                           class="w-14 bg-white border border-slate-200 rounded px-1.5 py-0.5 text-center font-mono font-bold text-xs" />
                </td>
                <td class="py-2 text-right font-mono">₹${item.mrp.toFixed(2)}</td>
                <td class="py-2 text-right font-mono font-bold text-slate-800">₹${itemNet.toFixed(2)}</td>
                <td class="py-2 text-center">
                    <button onclick="removeFromCart(${index})" class="text-slate-400 hover:text-rose-600">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        if (hasScheduleH) {
            scheduleBanner.classList.remove('hidden');
        } else {
            scheduleBanner.classList.add('hidden');
        }

        netPayable = grandTotal;
        document.getElementById('summaryTaxable').innerText = `₹${totalTaxable.toFixed(2)}`;
        document.getElementById('summaryGst').innerText = `₹${totalGst.toFixed(2)}`;
        document.getElementById('summaryDiscount').innerText = `₹${totalDiscount.toFixed(2)}`;
        document.getElementById('summaryNetPayable').innerText = `₹${grandTotal.toFixed(2)}`;
    }

    function updateQuantity(index, val) {
        const q = parseFloat(val);
        if (q <= 0 || isNaN(q)) {
            removeFromCart(index);
            return;
        }
        if (q > cart[index].max_stock) {
            alert(`Maximum stock for batch is ${cart[index].max_stock}`);
            cart[index].quantity = cart[index].max_stock;
        } else {
            cart[index].quantity = q;
        }
        renderCart();
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function clearCart() {
        if (cart.length > 0 && confirm('Clear current cart?')) {
            cart = [];
            renderCart();
        }
    }

    // Payment Modal Functions
    function openPaymentModal() {
        if (cart.length === 0) return;
        document.getElementById('modalBillAmount').innerText = `₹${netPayable.toFixed(2)}`;
        document.getElementById('payCash').value = netPayable.toFixed(2);
        document.getElementById('payUpi').value = '';
        document.getElementById('payCard').value = '';
        document.getElementById('payCredit').value = '';
        calculateChange();
        document.getElementById('paymentModal').classList.remove('hidden');
        document.getElementById('payCash').focus();
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').classList.add('hidden');
    }

    function calculateChange() {
        const cash = parseFloat(document.getElementById('payCash').value || 0);
        const upi = parseFloat(document.getElementById('payUpi').value || 0);
        const card = parseFloat(document.getElementById('payCard').value || 0);
        const credit = parseFloat(document.getElementById('payCredit').value || 0);

        const totalTendered = cash + upi + card + credit;
        document.getElementById('totalTenderedDisplay').innerText = `₹${totalTendered.toFixed(2)}`;

        const diff = totalTendered - netPayable;
        const changeDisplay = document.getElementById('cashChangeDisplay');
        const balanceDisplay = document.getElementById('tenderBalanceDisplay');
        const submitBtn = document.getElementById('submitPaymentBtn');

        if (diff >= 0) {
            changeDisplay.innerText = `₹${(cash >= diff ? diff : 0).toFixed(2)}`;
            balanceDisplay.innerText = 'Paid in Full';
            balanceDisplay.className = 'font-bold text-emerald-600';
            submitBtn.disabled = false;
        } else {
            changeDisplay.innerText = '₹0.00';
            balanceDisplay.innerText = `Remaining: ₹${Math.abs(diff).toFixed(2)}`;
            balanceDisplay.className = 'font-bold text-rose-600';
            submitBtn.disabled = true;
        }
    }

    async function submitCheckout() {
        const payments = [];
        const cash = parseFloat(document.getElementById('payCash').value || 0);
        const upi = parseFloat(document.getElementById('payUpi').value || 0);
        const card = parseFloat(document.getElementById('payCard').value || 0);
        const credit = parseFloat(document.getElementById('payCredit').value || 0);

        if (cash > 0) payments.push({ payment_method: 'cash', amount: cash });
        if (upi > 0) payments.push({ payment_method: 'upi', amount: upi });
        if (card > 0) payments.push({ payment_method: 'card', amount: card });
        if (credit > 0) payments.push({ payment_method: 'credit', amount: credit });

        const customerId = document.getElementById('cartCustomerId').value;
        const doctorId = document.getElementById('cartDoctorId').value;
        const rxNumber = document.getElementById('rxNumberInput')?.value || null;

        const payload = {
            customer_id: customerId || null,
            doctor_id: doctorId || null,
            prescription_number: rxNumber,
            items: cart.map(i => ({
                product_id: i.product_id,
                batch_id: i.batch_id,
                quantity: i.quantity,
                unit_price: i.unit_price,
                discount_percent: i.discount_percent,
                gst_rate: i.gst_rate
            })),
            payments: payments
        };

        const submitBtn = document.getElementById('submitPaymentBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> Committing Sale...';

        try {
            const res = await fetch("{{ route('pos.checkout') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (data.success) {
                closePaymentModal();
                cart = [];
                renderCart();
                // Open printable receipt in new popup window
                window.open(`/pos/receipt/${data.invoice_id}`, '_blank', 'width=450,height=700');
            } else {
                alert(`Checkout failed: ${data.message}`);
            }
        } catch (err) {
            alert('Checkout error. Please verify network and cashier shift.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Confirm Payment & Print Receipt';
        }
    }

    // Bill Hold / Resume
    async function holdCurrentBill() {
        if (cart.length === 0) {
            alert('Cart is empty.');
            return;
        }

        const customerSelect = document.getElementById('cartCustomerId');
        const customerName = customerSelect.options[customerSelect.selectedIndex].text;

        try {
            const res = await fetch("{{ route('pos.hold') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    customer_name: customerName,
                    total_amount: netPayable,
                    cart_payload: { items: cart }
                })
            });

            const data = await res.json();
            if (data.success) {
                alert(`Bill held under code: ${data.reference_code}`);
                cart = [];
                renderCart();
                location.reload();
            }
        } catch {
            alert('Failed to hold bill.');
        }
    }

    async function resumeBill(refCode) {
        try {
            const res = await fetch(`/pos/resume/${refCode}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success && data.data.cart_payload?.items) {
                cart = data.data.cart_payload.items;
                closeHeldBillsModal();
                renderCart();
            }
        } catch {
            alert('Failed to resume bill.');
        }
    }

    // Shift Handlers
    function openShiftModal() { document.getElementById('shiftModal').classList.remove('hidden'); }
    function closeShiftModal() { document.getElementById('shiftModal').classList.add('hidden'); }
    function openHeldBillsModal() { document.getElementById('heldBillsModal').classList.remove('hidden'); }
    function closeHeldBillsModal() { document.getElementById('heldBillsModal').classList.add('hidden'); }

    async function handleStartShift(e) {
        e.preventDefault();
        const floatVal = document.getElementById('openingFloatInput').value;
        const notes = document.getElementById('openingNotesInput').value;

        const res = await fetch("{{ route('pos.shifts.start') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ opening_float: floatVal, notes: notes })
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    }

    async function handleCloseShift(e) {
        e.preventDefault();
        const shiftId = document.getElementById('shiftIdInput').value;
        const counted = document.getElementById('closingCashInput').value;
        const notes = document.getElementById('closingNotesInput').value;

        const res = await fetch("{{ route('pos.shifts.close') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ shift_id: shiftId, closing_cash_counted: counted, closing_notes: notes })
        });
        const data = await res.json();
        if (data.success) {
            alert('Shift closed successfully!');
            location.reload();
        } else {
            alert(data.message);
        }
    }
</script>
@endpush
