<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Apex Pharma ERP') - Pharmacy Management & POS</title>
    
    <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- Top White Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-sm">
        <div class="px-4 py-2.5 flex items-center justify-between">
            
            <!-- Left: Logo & Branch Info -->
            <div class="flex items-center space-x-4">
                <a href="{{ route('pos.index') }}" class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-lg bg-emerald-600 flex items-center justify-center text-white shadow-sm shadow-emerald-500/30">
                        <i class="fa-solid fa-notes-medical text-lg"></i>
                    </div>
                    <div>
                        <div class="text-sm font-extrabold tracking-tight text-slate-900 flex items-center gap-1.5">
                            <span>APEX PHARMA</span>
                            <span class="text-[10px] bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200 px-1.5 py-0.2 rounded uppercase">ERP</span>
                        </div>
                        <div class="text-[11px] text-slate-500 flex items-center gap-1">
                            <i class="fa-solid fa-store text-emerald-600 text-[10px]"></i>
                            <span>{{ auth()->user()->branch?->name ?? 'Main Retail Counter' }}</span>
                        </div>
                    </div>
                </a>

                <!-- Branch Switcher -->
                @php
                    $userBranches = auth()->user()->organization?->branches ?? collect();
                    $activeBranchId = session('active_branch_id', auth()->user()->branch_id);
                @endphp
                @if($userBranches->count() > 1)
                <form action="{{ route('branch.switch') }}" method="POST" class="hidden md:flex items-center bg-slate-100 border border-slate-200 rounded-lg px-2.5 py-1">
                    @csrf
                    <i class="fa-solid fa-building text-slate-400 text-xs mr-1.5"></i>
                    <select name="branch_id" onchange="this.form.submit()" class="bg-transparent text-xs text-slate-700 font-medium focus:outline-none cursor-pointer">
                        @foreach($userBranches as $b)
                            <option value="{{ $b->id }}" {{ $activeBranchId == $b->id ? 'selected' : '' }}>
                                {{ $b->name }} ({{ ucfirst($b->type) }})
                            </option>
                        @endforeach
                    </select>
                </form>
                @endif
            </div>

            <!-- Middle Navigation Links -->
            <nav class="hidden lg:flex items-center space-x-1 bg-slate-100/80 p-1 rounded-xl border border-slate-200">
                <a href="{{ route('pos.index') }}" 
                   class="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('pos.*') ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60' }}">
                    <i class="fa-solid fa-cash-register text-emerald-600"></i>
                    <span>POS Counter</span>
                    <span class="bg-slate-100 text-slate-500 text-[9px] px-1 py-0.5 rounded font-mono border border-slate-200">F1</span>
                </a>

                <a href="{{ route('inventory.index') }}" 
                   class="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('inventory.*') ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60' }}">
                    <i class="fa-solid fa-pills text-blue-500"></i>
                    <span>Medicines</span>
                    <span class="bg-slate-100 text-slate-500 text-[9px] px-1 py-0.5 rounded font-mono border border-slate-200">F2</span>
                </a>

                <a href="{{ route('procurement.index') }}" 
                   class="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('procurement.*') ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60' }}">
                    <i class="fa-solid fa-truck-ramp-box text-purple-500"></i>
                    <span>Purchases</span>
                    <span class="bg-slate-100 text-slate-500 text-[9px] px-1 py-0.5 rounded font-mono border border-slate-200">F3</span>
                </a>

                <a href="{{ route('sales.index') }}" 
                   class="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('sales.*') ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60' }}">
                    <i class="fa-solid fa-file-invoice text-amber-500"></i>
                    <span>Invoices</span>
                    <span class="bg-slate-100 text-slate-500 text-[9px] px-1 py-0.5 rounded font-mono border border-slate-200">F4</span>
                </a>

                <a href="{{ route('reports.gst') }}" 
                   class="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('reports.gst') ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60' }}">
                    <i class="fa-solid fa-receipt text-indigo-500"></i>
                    <span>GST (GSTR-1/3B)</span>
                </a>

                <a href="{{ route('reports.financials') }}" 
                   class="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('reports.financials') ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60' }}">
                    <i class="fa-solid fa-scale-balanced text-teal-600"></i>
                    <span>P&L / Balance</span>
                </a>

                <a href="{{ route('ai.index') }}" 
                   class="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('ai.*') ? 'bg-white text-purple-700 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60' }}">
                    <i class="fa-solid fa-wand-magic-sparkles text-purple-600"></i>
                    <span>AI Analytics</span>
                </a>

                <a href="{{ route('audit.index') }}" 
                   class="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request()->routeIs('audit.*') ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60' }}">
                    <i class="fa-solid fa-shield-halved text-slate-500"></i>
                    <span>Audit</span>
                </a>
            </nav>

            <!-- Right Controls: User Profile & Logout -->
            <div class="flex items-center space-x-3">
                <div class="flex items-center space-x-2 border-r border-slate-200 pr-3">
                    <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 font-bold text-xs">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="hidden sm:block text-left">
                        <div class="text-xs font-bold text-slate-900 leading-tight">{{ auth()->user()->name }}</div>
                        <div class="text-[10px] text-slate-500 uppercase font-semibold">
                            {{ auth()->user()->roles->pluck('name')->first() ?? 'Staff' }}
                        </div>
                    </div>
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Logout session" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition">
                        <i class="fa-solid fa-right-from-bracket text-sm"></i>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Global Flash Alerts -->
    @if(session('success'))
    <div class="max-w-7xl mx-auto w-full px-4 pt-3">
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2.5 rounded-xl flex items-center justify-between text-xs font-medium shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
    @endif

    @if(session('error') || $errors->any())
    <div class="max-w-7xl mx-auto w-full px-4 pt-3">
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-2.5 rounded-xl flex items-center justify-between text-xs font-medium shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-rose-600 text-sm"></i>
                <span>{{ session('error') ?? $errors->first() }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
    @endif

    <!-- Main Content Slot -->
    <main class="flex-1 flex flex-col overflow-hidden">
        @yield('content')
    </main>

    <!-- Global Scripts -->
    <script>
        // Global Keyboard Shortcuts (Marg-style)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F1') {
                e.preventDefault();
                window.location.href = "{{ route('pos.index') }}";
            } else if (e.key === 'F2') {
                e.preventDefault();
                window.location.href = "{{ route('inventory.index') }}";
            } else if (e.key === 'F3') {
                e.preventDefault();
                window.location.href = "{{ route('procurement.index') }}";
            } else if (e.key === 'F4') {
                e.preventDefault();
                window.location.href = "{{ route('sales.index') }}";
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
