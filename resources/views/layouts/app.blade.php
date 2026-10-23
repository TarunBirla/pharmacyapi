<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Apex Pharma ERP') - Pharmacy Management & POS</title>
    
    <!-- PWA Manifest & App Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#059669">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ApexPharma">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #f8fafc; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-800 antialiased selection:bg-emerald-500 selection:text-white">

@php
    $authUser = auth()->user();
    $userRoleSlug = $authUser?->roles->pluck('slug')->first() ?? 'staff';
    $userRoleName = $authUser?->roles->pluck('name')->first() ?? 'Staff';
    $isSuperAdmin = $authUser?->hasRole('super_admin') ?? false;
    $isOwner = $isSuperAdmin || ($authUser?->hasRole('organization_owner') ?? false);
    $isPharmacist = $isOwner || ($authUser?->hasRole('pharmacist') ?? false);
    $isCashier = $isOwner || ($authUser?->hasRole('cashier') ?? false);
    $isPurchaser = $isOwner || ($authUser?->hasRole('purchase_manager') ?? false);

    $userBranches = $isSuperAdmin ? \App\Models\Branch::all() : ($authUser?->organization?->branches ?? collect());
    $activeBranchId = session('active_branch_id', $authUser?->branch_id);
    $activeBranch = $userBranches->firstWhere('id', $activeBranchId) ?? $authUser?->branch ?? \App\Models\Branch::first();

    // Cashier shift status
    $activeShift = \App\Models\CashierShift::where('user_id', $authUser?->id)
        ->where('status', 'open')
        ->latest('started_at')
        ->first();
@endphp

    <div class="flex h-screen overflow-hidden bg-slate-50">

        <!-- Mobile Sidebar Overlay Backdrop -->
        <div id="sidebarBackdrop" 
             onclick="toggleSidebar()" 
             class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 hidden md:hidden transition-opacity"></div>

        <!-- Left Enterprise Sidebar -->
        <aside id="mainSidebar" 
               class="fixed md:static inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 flex flex-col justify-between transform -translate-x-full md:translate-x-0 transition-transform duration-200 ease-in-out shadow-lg md:shadow-none">
            
            <!-- Sidebar Top: Brand & Tenant Info -->
            <div class="flex flex-col flex-1 min-h-0">
                <!-- Brand Header -->
                <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                    <a href="{{ route('pos.index') }}" class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-emerald-600 flex items-center justify-center text-white shadow-md shadow-emerald-500/25 shrink-0">
                            <i class="fa-solid fa-notes-medical text-lg"></i>
                        </div>
                        <div>
                            <div class="text-sm font-black tracking-tight text-slate-900 flex items-center gap-1.5">
                                <span>APEX PHARMA</span>
                                <span class="text-[9px] bg-emerald-100 text-emerald-800 font-bold px-1 py-0.2 rounded border border-emerald-300">ERP</span>
                            </div>
                            <div class="text-[10px] text-slate-400 font-medium truncate max-w-[140px]">
                                {{ $authUser?->organization?->name ?? 'Apex Healthcare' }}
                            </div>
                        </div>
                    </a>
                    <button onclick="toggleSidebar()" class="md:hidden text-slate-400 hover:text-slate-600 p-1">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- User Role Profile Pill (Clickable to /profile) -->
                <a href="{{ route('profile.index') }}" 
                   title="View your Role Profile & PWA Settings"
                   class="p-3 mx-3 mt-3 bg-slate-50 hover:bg-emerald-50/60 border border-slate-200 hover:border-emerald-300 rounded-xl flex items-center gap-2.5 transition group">
                    <div class="w-9 h-9 rounded-lg bg-emerald-700 group-hover:bg-emerald-600 text-white font-bold text-xs flex items-center justify-center shadow-sm shrink-0 transition">
                        {{ strtoupper(substr($authUser?->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-bold text-slate-900 group-hover:text-emerald-900 truncate leading-tight flex items-center justify-between">
                            <span>{{ $authUser?->name }}</span>
                            <i class="fa-solid fa-chevron-right text-[9px] text-slate-400 group-hover:text-emerald-600 transition"></i>
                        </div>
                        <div class="text-[10px] flex items-center gap-1 mt-0.5">
                            @if($isSuperAdmin)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded bg-purple-100 text-purple-700 font-bold border border-purple-200 text-[9px]">
                                    <i class="fa-solid fa-crown text-[8px]"></i> Super Admin
                                </span>
                            @elseif($authUser?->hasRole('organization_owner'))
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 font-bold border border-amber-200 text-[9px]">
                                    <i class="fa-solid fa-user-tie text-[8px]"></i> Owner
                                </span>
                            @elseif($authUser?->hasRole('pharmacist'))
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800 font-bold border border-emerald-200 text-[9px]">
                                    <i class="fa-solid fa-user-doctor text-[8px]"></i> Pharmacist
                                </span>
                            @elseif($authUser?->hasRole('cashier'))
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded bg-blue-100 text-blue-800 font-bold border border-blue-200 text-[9px]">
                                    <i class="fa-solid fa-cash-register text-[8px]"></i> Cashier
                                </span>
                            @elseif($authUser?->hasRole('purchase_manager'))
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded bg-indigo-100 text-indigo-800 font-bold border border-indigo-200 text-[9px]">
                                    <i class="fa-solid fa-truck text-[8px]"></i> Purchase Mgr
                                </span>
                            @else
                                <span class="text-slate-500 font-semibold">{{ $userRoleName }}</span>
                            @endif
                        </div>
                    </div>
                </a>

                <!-- Navigation Links (Role Wise) -->
                <nav class="flex-1 px-3 py-3 space-y-4 overflow-y-auto text-xs font-semibold">

                    <!-- Section: Billing & Sales -->
                    @if($isCashier || $isPharmacist)
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2 mb-1.5">
                            Billing & Counter
                        </div>
                        <div class="space-y-1">
                            <a href="{{ route('pos.index') }}" 
                               class="flex items-center justify-between px-2.5 py-2 rounded-xl transition {{ request()->routeIs('pos.*') ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                <div class="flex items-center gap-2.5">
                                    <i class="fa-solid fa-cash-register w-4 text-center {{ request()->routeIs('pos.*') ? 'text-white' : 'text-emerald-600' }}"></i>
                                    <span>POS Billing Counter</span>
                                </div>
                                <span class="text-[9px] font-mono px-1 py-0.5 rounded {{ request()->routeIs('pos.*') ? 'bg-emerald-700 text-white' : 'bg-slate-200/70 text-slate-500' }}">F1</span>
                            </a>

                            <a href="{{ route('sales.index') }}" 
                               class="flex items-center justify-between px-2.5 py-2 rounded-xl transition {{ request()->routeIs('sales.*') ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                <div class="flex items-center gap-2.5">
                                    <i class="fa-solid fa-file-invoice-dollar w-4 text-center {{ request()->routeIs('sales.*') ? 'text-white' : 'text-amber-500' }}"></i>
                                    <span>Sales Invoices</span>
                                </div>
                                <span class="text-[9px] font-mono px-1 py-0.5 rounded {{ request()->routeIs('sales.*') ? 'bg-emerald-700 text-white' : 'bg-slate-200/70 text-slate-500' }}">F4</span>
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Section: Inventory & Pharmacy -->
                    @if($isPharmacist || $isPurchaser)
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2 mb-1.5">
                            Medicine Inventory
                        </div>
                        <div class="space-y-1">
                            <a href="{{ route('inventory.index') }}" 
                               class="flex items-center justify-between px-2.5 py-2 rounded-xl transition {{ request()->routeIs('inventory.index') && !request()->has('tab') ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                <div class="flex items-center gap-2.5">
                                    <i class="fa-solid fa-pills w-4 text-center {{ request()->routeIs('inventory.index') && !request()->has('tab') ? 'text-white' : 'text-blue-500' }}"></i>
                                    <span>Medicine Catalog</span>
                                </div>
                                <span class="text-[9px] font-mono px-1 py-0.5 rounded {{ request()->routeIs('inventory.index') && !request()->has('tab') ? 'bg-emerald-700 text-white' : 'bg-slate-200/70 text-slate-500' }}">F2</span>
                            </a>

                            <a href="{{ route('inventory.index') }}?tab=expiring" 
                               class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl transition {{ request()->query('tab') == 'expiring' ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                <i class="fa-solid fa-clock-rotate-left w-4 text-center {{ request()->query('tab') == 'expiring' ? 'text-white' : 'text-rose-500' }}"></i>
                                <span>Near Expiry Batches</span>
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Section: Procurement & Suppliers -->
                    @if($isPurchaser || $isOwner)
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2 mb-1.5">
                            Purchasing & Vendors
                        </div>
                        <div class="space-y-1">
                            <a href="{{ route('procurement.index') }}" 
                               class="flex items-center justify-between px-2.5 py-2 rounded-xl transition {{ request()->routeIs('procurement.index') && !request()->has('tab') ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                <div class="flex items-center gap-2.5">
                                    <i class="fa-solid fa-truck-ramp-box w-4 text-center {{ request()->routeIs('procurement.index') && !request()->has('tab') ? 'text-white' : 'text-purple-500' }}"></i>
                                    <span>Inward Purchase Bills</span>
                                </div>
                                <span class="text-[9px] font-mono px-1 py-0.5 rounded {{ request()->routeIs('procurement.index') && !request()->has('tab') ? 'bg-emerald-700 text-white' : 'bg-slate-200/70 text-slate-500' }}">F3</span>
                            </a>

                            <a href="{{ route('procurement.index') }}?tab=suppliers" 
                               class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl transition {{ request()->query('tab') == 'suppliers' ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                <i class="fa-solid fa-boxes-packing w-4 text-center {{ request()->query('tab') == 'suppliers' ? 'text-white' : 'text-indigo-500' }}"></i>
                                <span>Supplier Directory</span>
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Section: GST & Financial Accounts -->
                    @if($isOwner)
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2 mb-1.5">
                            Tax & Financials
                        </div>
                        <div class="space-y-1">
                            <a href="{{ route('reports.gst') }}" 
                               class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl transition {{ request()->routeIs('reports.gst') ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                <i class="fa-solid fa-receipt w-4 text-center {{ request()->routeIs('reports.gst') ? 'text-white' : 'text-indigo-500' }}"></i>
                                <span>GST (GSTR-1 & 3B)</span>
                            </a>

                            <a href="{{ route('reports.financials') }}" 
                               class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl transition {{ request()->routeIs('reports.financials') ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                <i class="fa-solid fa-scale-balanced w-4 text-center {{ request()->routeIs('reports.financials') ? 'text-white' : 'text-teal-600' }}"></i>
                                <span>P&L / Balance Sheet</span>
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Section: AI Intelligence -->
                    @if($isPharmacist || $isPurchaser)
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2 mb-1.5">
                            Intelligence
                        </div>
                        <div class="space-y-1">
                            <a href="{{ route('ai.index') }}" 
                               class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl transition {{ request()->routeIs('ai.*') ? 'bg-purple-600 text-white shadow-sm shadow-purple-600/30 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                <i class="fa-solid fa-wand-magic-sparkles w-4 text-center {{ request()->routeIs('ai.*') ? 'text-white' : 'text-purple-600' }}"></i>
                                <span>AI Demand & Refills</span>
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Section: Security & System Audit -->
                    @if($isOwner)
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2 mb-1.5">
                            System & Security
                        </div>
                        <div class="space-y-1">
                            <a href="{{ route('audit.index') }}" 
                               class="flex items-center gap-2.5 px-2.5 py-2 rounded-xl transition {{ request()->routeIs('audit.*') ? 'bg-slate-900 text-white shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                <i class="fa-solid fa-shield-halved w-4 text-center {{ request()->routeIs('audit.*') ? 'text-white' : 'text-slate-500' }}"></i>
                                <span>Security Audit Trail</span>
                            </a>
                        </div>
                    </div>
                    @endif

                    <!-- Section: My Account & PWA App -->
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2 mb-1.5">
                            My Account & App
                        </div>
                        <div class="space-y-1">
                            <a href="{{ route('profile.index') }}" 
                               class="flex items-center justify-between px-2.5 py-2 rounded-xl transition {{ request()->routeIs('profile.*') ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                <div class="flex items-center gap-2.5">
                                    <i class="fa-solid fa-id-card-clip w-4 text-center {{ request()->routeIs('profile.*') ? 'text-white' : 'text-emerald-600' }}"></i>
                                    <span>My Role Profile</span>
                                </div>
                                <span class="text-[9px] font-mono px-1 py-0.5 rounded {{ request()->routeIs('profile.*') ? 'bg-emerald-700 text-white' : 'bg-slate-200/70 text-slate-500' }}">PRO</span>
                            </a>

                            <button type="button" 
                                    onclick="installPwaApp()" 
                                    class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl text-emerald-800 bg-emerald-50/70 hover:bg-emerald-100 border border-emerald-200 transition text-left">
                                <div class="flex items-center gap-2.5">
                                    <i class="fa-solid fa-download w-4 text-center text-emerald-600"></i>
                                    <span class="font-bold">Download App</span>
                                </div>
                                <span class="text-[9px] bg-emerald-600 text-white font-mono px-1.5 py-0.5 rounded font-extrabold shadow-sm">PWA</span>
                            </button>
                        </div>
                    </div>

                </nav>
            </div>

            <!-- Sidebar Bottom: Shift Status & Logout -->
            <div class="p-3 border-t border-slate-200 bg-white space-y-2">
                <!-- Shift Status -->
                <div class="px-2.5 py-1.5 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between text-[11px]">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full {{ $activeShift ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                        <span class="font-bold text-slate-700">Cash Shift:</span>
                    </div>
                    <span class="font-bold {{ $activeShift ? 'text-emerald-700' : 'text-slate-500' }}">
                        {{ $activeShift ? 'Open' : 'Closed' }}
                    </span>
                </div>

                <!-- Logout Form -->
                <form action="{{ route('logout') }}" method="POST" class="w-full">
                    @csrf
                    <button type="submit" 
                            class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Logout Session</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main View Area (Header + Body) -->
        <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
            
            <!-- Top Navbar -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-4 sm:px-6 shrink-0 z-30 shadow-sm">
                
                <!-- Left: Hamburger & Current Section Title -->
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" 
                            class="md:hidden p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>

                    <div class="hidden sm:flex items-center gap-2 text-xs">
                        <span class="font-semibold text-slate-400">Section:</span>
                        <span class="font-bold text-slate-800 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                            @yield('title', 'POS Counter')
                        </span>
                    </div>
                </div>

                <!-- Right: Branch Switcher & Quick Actions -->
                <div class="flex items-center gap-3">
                    
                    <!-- Branch Selector -->
                    @if($userBranches->count() > 1 || $isSuperAdmin)
                    <form action="{{ route('branch.switch') }}" method="POST" class="flex items-center bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 shadow-sm">
                        @csrf
                        <i class="fa-solid fa-building text-slate-400 text-xs mr-2"></i>
                        <select name="branch_id" onchange="this.form.submit()" class="bg-transparent text-xs text-slate-700 font-bold focus:outline-none cursor-pointer">
                            @foreach($userBranches as $b)
                                <option value="{{ $b->id }}" {{ ($activeBranchId == $b->id) ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                    @else
                    <div class="hidden sm:flex items-center gap-1.5 text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5">
                        <i class="fa-solid fa-store text-emerald-600 text-xs"></i>
                        <span class="font-bold">{{ $activeBranch?->name ?? 'Main Dispensary' }}</span>
                    </div>
                    @endif

                    <!-- Download App (PWA) Button -->
                    <button type="button" 
                            onclick="installPwaApp()" 
                            title="Install Apex Pharma ERP on Desktop or Mobile"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 shadow-sm transition">
                        <i class="fa-solid fa-download text-emerald-600"></i>
                        <span class="hidden md:inline">Download App</span>
                        <span class="text-[9px] bg-emerald-600 text-white font-mono px-1.5 py-0.5 rounded font-black shadow-sm">PWA</span>
                    </button>

                    <!-- User Quick Menu (Links to /profile) -->
                    <a href="{{ route('profile.index') }}" 
                       title="View My Role Profile & Permissions"
                       class="flex items-center gap-2 pl-2 border-l border-slate-200 hover:opacity-85 transition group">
                        <div class="w-8 h-8 rounded-xl bg-slate-100 group-hover:bg-emerald-50 border border-slate-200 group-hover:border-emerald-300 flex items-center justify-center text-slate-700 group-hover:text-emerald-700 font-bold text-xs transition">
                            {{ strtoupper(substr($authUser?->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="hidden lg:block text-left">
                            <div class="text-xs font-bold text-slate-800 group-hover:text-emerald-700 truncate max-w-[120px] leading-tight">{{ $authUser?->name }}</div>
                            <div class="text-[10px] text-slate-400 font-semibold">{{ $userRoleName }}</div>
                        </div>
                    </a>

                    <!-- Top Navbar Logout Button -->
                    <form action="{{ route('logout') }}" method="POST" class="ml-1">
                        @csrf
                        <button type="submit" title="Logout session" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition">
                            <i class="fa-solid fa-power-off text-sm"></i>
                        </button>
                    </form>

                </div>
            </header>

            <!-- Global Flash Alerts -->
            @if(session('success'))
            <div class="px-4 pt-3 shrink-0">
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2 rounded-xl flex items-center justify-between text-xs font-semibold shadow-sm">
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
            <div class="px-4 pt-3 shrink-0">
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-2 rounded-xl flex items-center justify-between text-xs font-semibold shadow-sm">
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

            <!-- Main Dynamic Content Slot -->
            <main class="flex-1 flex flex-col min-h-0 overflow-y-auto">
                @yield('content')
            </main>

        </div>
    </div>

    <!-- Universal PWA Installation Guide Modal -->
    <div id="pwaGuideModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-download"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Download & Install Apex Pharma App</h3>
                        <p class="text-[11px] text-slate-500">Progressive Web Application (PWA)</p>
                    </div>
                </div>
                <button onclick="closePwaModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <div class="space-y-3 text-xs text-slate-600">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                        <i class="fa-brands fa-chrome text-amber-500"></i>
                        <i class="fa-brands fa-edge text-blue-500"></i>
                        <span>Windows PC / Mac (Chrome, Edge):</span>
                    </div>
                    <p class="leading-relaxed">Click the <strong>Install</strong> icon in the address bar (the monitor with down arrow <i class="fa-solid fa-download text-slate-400"></i> or <strong>(+)</strong>) or click the <strong>Install App</strong> button in this ERP.</p>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                        <i class="fa-brands fa-android text-emerald-500"></i>
                        <span>Android Phones & Tablets:</span>
                    </div>
                    <p class="leading-relaxed">Tap the 3-dots menu <i class="fa-solid fa-ellipsis-vertical text-slate-400"></i> in Chrome and select <strong>"Install App"</strong> or <strong>"Add to Home screen"</strong>.</p>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                        <i class="fa-brands fa-apple text-slate-800"></i>
                        <span>Apple iOS (iPhone / iPad - Safari):</span>
                    </div>
                    <p class="leading-relaxed">Tap the Share button <i class="fa-solid fa-arrow-up-from-bracket text-slate-400"></i> in Safari, scroll down, and select <strong>"Add to Home Screen"</strong>.</p>
                </div>
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="closePwaModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                    Close
                </button>
                <button type="button" onclick="triggerNativePwaInstall()" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition">
                    Try Install Prompt Now
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer, Hotkeys & PWA Service Worker Scripts -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
                backdrop.classList.add('hidden');
            }
        }

        // Global Keyboard Shortcuts (Marg-style navigation)
        document.addEventListener('keydown', function(e) {
            // Only trigger if not typing inside an input/textarea
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName) && e.key !== 'F1' && e.key !== 'F2' && e.key !== 'F3' && e.key !== 'F4') {
                return;
            }
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

        // -------------------------------------------------------------
        // Progressive Web App (PWA) Handler & Service Worker
        // -------------------------------------------------------------
        let deferredPwaPrompt = null;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPwaPrompt = e;
            window.deferredPrompt = e;
            console.log('Apex Pharma PWA install prompt captured.');
        });

        window.addEventListener('appinstalled', () => {
            console.log('Apex Pharma PWA was installed successfully!');
            deferredPwaPrompt = null;
            window.deferredPrompt = null;
        });

        function installPwaApp() {
            if (deferredPwaPrompt || window.deferredPrompt) {
                const promptEvent = deferredPwaPrompt || window.deferredPrompt;
                promptEvent.prompt();
                promptEvent.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted PWA installation');
                    }
                    deferredPwaPrompt = null;
                    window.deferredPrompt = null;
                });
            } else {
                // If browser prompt is not ready or user already has app, show install guide modal
                const modal = document.getElementById('pwaGuideModal');
                if (modal) {
                    modal.classList.remove('hidden');
                }
            }
        }

        function triggerNativePwaInstall() {
            if (deferredPwaPrompt || window.deferredPrompt) {
                installPwaApp();
                closePwaModal();
            } else {
                alert('Native prompt not available yet in this browser session. Please follow the instructions above (Address Bar Install Icon / 3-dots > Install App).');
            }
        }

        function closePwaModal() {
            const modal = document.getElementById('pwaGuideModal');
            if (modal) modal.classList.add('hidden');
        }

        // Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then((registration) => {
                    console.log('ServiceWorker registered with scope:', registration.scope);
                }).catch((error) => {
                    console.log('ServiceWorker registration note:', error);
                });
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
