@extends('layouts.app')

@section('title', "Generic Substitutes - {$product->name}")

@section('content')
<div class="flex-1 p-6 bg-slate-50 overflow-y-auto">
    <div class="max-w-3xl mx-auto space-y-4">
        
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <div>
                <a href="{{ route('inventory.index') }}" class="text-xs text-blue-600 hover:underline mb-1 inline-block">
                    &larr; Back to Inventory
                </a>
                <h1 class="text-lg font-bold text-slate-900">
                    Generic Equivalent Substitutes for: <span class="text-blue-700">{{ $product->name }}</span>
                </h1>
                <div class="text-xs text-slate-500">
                    Active Composition: <strong class="text-slate-800">{{ $product->generic_name }}</strong> (Strength: {{ $product->strength }})
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-2.5 px-4 font-bold">Substitute Brand Name</th>
                        <th class="py-2.5 px-4 font-bold">Manufacturer / Brand</th>
                        <th class="py-2.5 px-4 font-bold text-right">MRP (₹)</th>
                        <th class="py-2.5 px-4 font-bold text-right">Selling Price (₹)</th>
                        <th class="py-2.5 px-4 font-bold text-right">Available Stock</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                    @forelse($substitutes as $sub)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 font-bold text-slate-900">{{ $sub->name }}</td>
                        <td class="py-3 px-4 text-slate-500">{{ $sub->brand ?? '-' }}</td>
                        <td class="py-3 px-4 text-right font-mono text-slate-600">₹{{ number_format($sub->mrp, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">₹{{ number_format($sub->retail_price, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-800">
                            {{ $sub->batches->sum('quantity_available') }} units
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-10 text-slate-400">
                            No other brands found with exact matching active composition '{{ $product->generic_name }}'.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection
