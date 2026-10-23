<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy ERP - Database Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        pre, code { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-6 md:p-12 flex flex-col justify-center items-center">
    <div class="max-w-4xl w-full bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6 md:p-8 space-y-6">
        
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-800 pb-5">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-bold text-xl">
                    +
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-white">
                        Pharmacy ERP Live Deployment Setup
                    </h1>
                    <p class="text-xs text-slate-400">
                        Target Host: <span class="text-emerald-400 font-mono">{{ request()->getHost() }}</span>
                    </p>
                </div>
            </div>

            <div>
                @if($success)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                        SUCCESS 200 OK
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                        <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                        FAILED 500
                    </span>
                @endif
            </div>
        </div>

        <!-- Status Message Banner -->
        <div class="p-4 rounded-xl {{ $success ? 'bg-emerald-950/40 border border-emerald-800/60 text-emerald-200' : 'bg-rose-950/40 border border-rose-800/60 text-rose-200' }}">
            <div class="font-bold text-sm flex items-center gap-2">
                @if($success)
                    <span>Database tables created and seeded successfully!</span>
                @else
                    <span>Setup Error: {{ $message }}</span>
                @endif
            </div>
            @if(isset($error))
                <div class="mt-2 text-xs font-mono bg-rose-950 p-2.5 rounded border border-rose-900 text-rose-300">
                    {{ $error }}
                </div>
            @endif
        </div>

        <!-- Execution Logs -->
        <div>
            <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                Artisan Execution Output:
            </h2>
            <div class="bg-slate-950 border border-slate-800 rounded-xl p-4 overflow-x-auto max-h-80 overflow-y-auto">
                <pre class="text-xs text-emerald-400 leading-relaxed font-mono whitespace-pre-wrap">@foreach($logs as $log){{ $log }}

@endforeach</pre>
            </div>
        </div>

        <!-- Demo Accounts Table -->
        @if($success)
        <div class="border-t border-slate-800 pt-5">
            <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">
                Pre-configured Demo Logins (Password for all: <code class="text-amber-400 font-bold">Password123!</code>)
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <div class="bg-slate-950 p-3 rounded-lg border border-slate-800">
                    <div class="text-[10px] uppercase font-bold text-emerald-400">Super Admin</div>
                    <div class="text-xs text-slate-200 font-mono mt-0.5">superadmin@pharmaerp.local</div>
                </div>
                <div class="bg-slate-950 p-3 rounded-lg border border-slate-800">
                    <div class="text-[10px] uppercase font-bold text-amber-400">Org Owner</div>
                    <div class="text-xs text-slate-200 font-mono mt-0.5">owner@apexpharma.com</div>
                </div>
                <div class="bg-slate-950 p-3 rounded-lg border border-slate-800">
                    <div class="text-[10px] uppercase font-bold text-blue-400">Pharmacist</div>
                    <div class="text-xs text-slate-200 font-mono mt-0.5">pharmacist@apexpharma.com</div>
                </div>
                <div class="bg-slate-950 p-3 rounded-lg border border-slate-800">
                    <div class="text-[10px] uppercase font-bold text-teal-400">Cashier (POS)</div>
                    <div class="text-xs text-slate-200 font-mono mt-0.5">cashier@apexpharma.com</div>
                </div>
                <div class="bg-slate-950 p-3 rounded-lg border border-slate-800">
                    <div class="text-[10px] uppercase font-bold text-purple-400">Purchase Mgr</div>
                    <div class="text-xs text-slate-200 font-mono mt-0.5">purchase@apexpharma.com</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-800">
            <div class="flex items-center space-x-2">
                <a href="{{ url('/setup-database') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                    Run Normal Migrate Again
                </a>
                <a href="{{ url('/setup-database?fresh=1') }}" onclick="return confirm('Warning: This will DROP all tables and recreate clean from scratch. Proceed?')" class="bg-rose-950/60 hover:bg-rose-900 border border-rose-800/80 text-rose-300 px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                    Wipe & Recreate Fresh (?fresh=1)
                </a>
            </div>

            <div class="text-xs text-slate-400">
                API Base URL: <code class="text-emerald-400">{{ url('/api/v1') }}</code>
            </div>
        </div>
        @endif

    </div>
</body>
</html>
