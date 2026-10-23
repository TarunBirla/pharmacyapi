@extends('layouts.app')

@section('title', 'AI Predictive Intelligence & Clinical Analytics')

@section('content')
<div class="flex-1 flex flex-col p-4 md:p-6 bg-slate-50 overflow-hidden">
    
    <!-- Top Action Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-slate-200 gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-wand-magic-sparkles text-purple-600"></i> AI Predictive Intelligence & Clinical Adherence
            </h1>
            <p class="text-xs text-slate-500">
                Machine learning velocity calculations, dead stock exposure mitigation, and proactive chronic patient refill pipelines.
            </p>
        </div>
    </div>

    <!-- Sub Tabs -->
    <div class="flex py-3">
        <div class="flex bg-slate-200/70 p-1 rounded-xl text-xs font-semibold">
            <a href="{{ route('ai.index', ['tab' => 'demand']) }}" 
               class="px-3.5 py-1.5 rounded-lg transition {{ $tab === 'demand' ? 'bg-white text-purple-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                <i class="fa-solid fa-chart-line-up mr-1 text-purple-600"></i> Demand Forecast & Run Rate
            </a>
            <a href="{{ route('ai.index', ['tab' => 'expiry']) }}" 
               class="px-3.5 py-1.5 rounded-lg transition {{ $tab === 'expiry' ? 'bg-white text-purple-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                <i class="fa-solid fa-triangle-exclamation mr-1 text-rose-500"></i> Expiry Financial Exposure
            </a>
            <a href="{{ route('ai.index', ['tab' => 'refills']) }}" 
               class="px-3.5 py-1.5 rounded-lg transition {{ $tab === 'refills' ? 'bg-white text-purple-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                <i class="fa-solid fa-calendar-check mr-1 text-emerald-600"></i> Patient Refill Outreach
            </a>
        </div>
    </div>

    <!-- MAIN DATA TABLE -->
    <div class="flex-1 bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden flex flex-col">
        @if($tab === 'demand')
        <div class="flex-1 overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200 sticky top-0">
                    <tr>
                        <th class="py-2.5 px-4 font-bold">Medicine Name & Formulation</th>
                        <th class="py-2.5 px-4 font-bold text-right">Daily Run Rate</th>
                        <th class="py-2.5 px-4 font-bold text-right">Physical Stock</th>
                        <th class="py-2.5 px-4 font-bold text-right">Stockout In</th>
                        <th class="py-2.5 px-4 font-bold text-right">Suggested Reorder Qty</th>
                        <th class="py-2.5 px-4 font-bold text-center">Urgency</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                    @forelse($demandForecasts as $df)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900">{{ $df['product_name'] }}</div>
                            <div class="text-[11px] text-slate-400">{{ $df['generic_name'] ?? 'Formulation' }}</div>
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-purple-700 font-bold">
                            {{ $df['daily_run_rate'] }} units/day
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-slate-800 font-bold">
                            {{ $df['current_stock'] }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold">
                            @if($df['projected_stockout_days'] !== null)
                                <span class="{{ $df['projected_stockout_days'] <= 5 ? 'text-rose-600' : 'text-amber-600' }}">
                                    {{ $df['projected_stockout_days'] }} days
                                </span>
                            @else
                                <span class="text-slate-400">Zero Run Rate</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-black text-emerald-700 text-sm">
                            +{{ $df['recommended_reorder_qty'] }} units
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $df['risk_level'] === 'critical' ? 'bg-rose-50 text-rose-700 border border-rose-200' : ($df['risk_level'] === 'high' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200') }}">
                                {{ $df['risk_level'] }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-12 text-slate-400">No products require restocking at current velocity.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @elseif($tab === 'expiry')
        <div class="flex-1 overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-2.5 px-4 font-bold">Medicine / Batch</th>
                        <th class="py-2.5 px-4 font-bold">Expiry Date</th>
                        <th class="py-2.5 px-4 font-bold text-right">Days Left</th>
                        <th class="py-2.5 px-4 font-bold text-right">At-Risk Qty</th>
                        <th class="py-2.5 px-4 font-bold text-right">Cost Exposure (₹)</th>
                        <th class="py-2.5 px-4 font-bold text-center">Recommended AI Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                    @forelse($expiryRisks as $er)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900">{{ $er['product_name'] }}</div>
                            <div class="text-[11px] text-slate-400 font-mono">Batch: {{ $er['batch_number'] }}</div>
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-700 font-bold">{{ $er['expiry_date'] }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-rose-600">{{ $er['days_to_expiry'] }} days</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-800">{{ $er['current_stock'] }} units</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-rose-700">₹{{ number_format($er['cost_exposure'] ?? 0, 2) }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2.5 py-1 rounded bg-slate-100 text-purple-800 border border-slate-200 text-[11px] font-bold capitalize">
                                {{ str_replace('_', ' ', $er['suggested_action'] ?? 'Liquidate Discount') }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-12 text-slate-400">No near-expiry batches detected.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @elseif($tab === 'refills')
        <div class="flex-1 overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-2.5 px-4 font-bold">Patient / Customer</th>
                        <th class="py-2.5 px-4 font-bold">Maintenance Drug</th>
                        <th class="py-2.5 px-4 font-bold text-center">Daily Dosage</th>
                        <th class="py-2.5 px-4 font-bold text-right">Predicted Refill</th>
                        <th class="py-2.5 px-4 font-bold text-center">Channel</th>
                        <th class="py-2.5 px-4 font-bold text-center">Outreach Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                    @forelse($patientRefills as $pr)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900">{{ $pr->customer->name ?? 'Patient' }}</div>
                            <div class="text-[11px] text-slate-400 font-mono">{{ $pr->customer->phone ?? '+91 98765 43210' }}</div>
                        </td>
                        <td class="py-3 px-4 font-bold text-slate-800">{{ $pr->product->name ?? 'Chronic Medicine' }}</td>
                        <td class="py-3 px-4 text-center font-mono text-purple-700 font-bold">{{ $pr->daily_dosage ?? 1 }} dose/day</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-amber-600">{{ $pr->predicted_refill_date }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold uppercase px-2 py-0.5 rounded">
                                <i class="fa-brands fa-whatsapp text-emerald-600 mr-0.5"></i> WhatsApp
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($pr->status === 'pending')
                            <form action="{{ route('ai.refills.status', $pr->id) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="status" value="sent">
                                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold px-3 py-1 rounded-lg text-xs shadow-2xs">
                                    <i class="fa-solid fa-paper-plane mr-1"></i> Send Refill Alert
                                </button>
                            </form>
                            @else
                            <span class="text-emerald-700 font-bold text-xs">
                                <i class="fa-solid fa-circle-check text-emerald-600"></i> Dispatched
                            </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-12 text-slate-400">No upcoming chronic patient refills due.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
