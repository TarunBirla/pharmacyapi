@extends('layouts.app')

@section('title', 'Security Audit Trail & Compliance Ledger')

@section('content')
<div class="flex-1 flex flex-col p-4 md:p-6 bg-slate-50 overflow-hidden">
    
    <!-- Top Action Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-slate-200 gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-slate-700"></i> System Security Audit Trail
            </h1>
            <p class="text-xs text-slate-500">
                Immutable ISO 27001 compliant activity ledger tracking POS counter transactions, inventory adjustments, and sensitive records.
            </p>
        </div>

        <form action="{{ route('audit.index') }}" method="GET" class="relative w-64">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
            <input type="text" name="action" value="{{ $action }}" placeholder="Filter action (e.g. pos, purchase)..." class="w-full bg-white border border-slate-200 rounded-xl pl-9 pr-4 py-1.5 text-xs text-slate-900 focus:outline-none focus:border-slate-700">
        </form>
    </div>

    <!-- MAIN DATA TABLE -->
    <div class="flex-1 bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden flex flex-col mt-4">
        <div class="flex-1 overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider border-b border-slate-200 sticky top-0">
                    <tr>
                        <th class="py-2.5 px-4 font-bold">Timestamp</th>
                        <th class="py-2.5 px-4 font-bold">Actor / User</th>
                        <th class="py-2.5 px-4 font-bold">Action</th>
                        <th class="py-2.5 px-4 font-bold">Target Entity</th>
                        <th class="py-2.5 px-4 font-bold">Summary Description</th>
                        <th class="py-2.5 px-4 font-bold">IP Address</th>
                        <th class="py-2.5 px-4 font-bold text-right">Payload</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3 px-4 font-mono text-slate-500 whitespace-nowrap">
                            {{ $log->created_at->format('d M Y, H:i:s') }}
                        </td>
                        <td class="py-3 px-4 font-bold text-slate-800 whitespace-nowrap">
                            {{ $log->user->name ?? 'User #' . $log->user_id }}
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded font-mono text-[10px] font-bold uppercase {{ str_contains($log->action, 'create') || str_contains($log->action, 'checkout') ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-600 whitespace-nowrap">
                            {{ class_basename($log->entity_type) }} #{{ $log->entity_id }}
                        </td>
                        <td class="py-3 px-4 text-slate-700 max-w-xs truncate">
                            {{ $log->description ?? '-' }}
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-400 whitespace-nowrap">
                            {{ $log->ip_address ?? '127.0.0.1' }}
                        </td>
                        <td class="py-3 px-4 text-right">
                            @if($log->new_values || $log->old_values)
                            <button onclick="showDiff({{ json_encode($log) }})" class="text-blue-600 hover:text-blue-800 font-bold text-xs">
                                Inspect Diff
                            </button>
                            @else
                            <span class="text-slate-300">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-12 text-slate-400">No audit records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-slate-100">
            {{ $logs->links() }}
        </div>
    </div>
</div>

<!-- Diff Modal -->
<div id="diffModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white border border-slate-200 rounded-2xl max-w-xl w-full p-6 space-y-4 shadow-2xl overflow-y-auto max-h-[85vh]">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
            <div class="font-bold text-sm text-slate-900" id="diffModalTitle">Audit Log Diff</div>
            <button onclick="closeDiffModal()" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="space-y-3 font-mono text-xs">
            <div id="oldValuesBox" class="hidden">
                <div class="font-bold text-rose-700 text-[11px] mb-1">Old State (Prior Values)</div>
                <pre id="oldValuesContent" class="bg-rose-50 border border-rose-200 p-3 rounded-xl text-rose-900 text-[11px] overflow-x-auto"></pre>
            </div>

            <div id="newValuesBox" class="hidden">
                <div class="font-bold text-emerald-700 text-[11px] mb-1">Committed State (New Values)</div>
                <pre id="newValuesContent" class="bg-emerald-50 border border-emerald-200 p-3 rounded-xl text-emerald-900 text-[11px] overflow-x-auto"></pre>
            </div>
        </div>
    </div>
</div>

<script>
    function showDiff(log) {
        document.getElementById('diffModalTitle').innerText = `Log #${log.id} - ${log.action}`;
        const oldBox = document.getElementById('oldValuesBox');
        const newBox = document.getElementById('newValuesBox');

        if (log.old_values) {
            oldBox.classList.remove('hidden');
            document.getElementById('oldValuesContent').innerText = JSON.stringify(log.old_values, null, 2);
        } else {
            oldBox.classList.add('hidden');
        }

        if (log.new_values) {
            newBox.classList.remove('hidden');
            document.getElementById('newValuesContent').innerText = JSON.stringify(log.new_values, null, 2);
        } else {
            newBox.classList.add('hidden');
        }

        document.getElementById('diffModal').classList.remove('hidden');
    }

    function closeDiffModal() {
        document.getElementById('diffModal').classList.add('hidden');
    }
</script>
@endsection
