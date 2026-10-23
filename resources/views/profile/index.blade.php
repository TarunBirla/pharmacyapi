@extends('layouts.app')

@section('title', 'User Role Profile & PWA App')

@section('content')
<div class="p-4 md:p-6 space-y-6 max-w-7xl mx-auto w-full">

    <!-- Top Breadcrumb & Page Title -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pb-4 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('pos.index') }}" class="hover:text-emerald-600 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-800 font-bold">Role Profile & PWA Download</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fa-solid fa-id-card-clip text-emerald-600"></i>
                <span>User Role Profile & App Settings</span>
            </h1>
        </div>

        <!-- Quick PWA Install Header Button -->
        <button type="button" 
                onclick="installPwaApp()" 
                id="headerPwaBtn"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-600/25 transition transform hover:-translate-y-0.5">
            <i class="fa-solid fa-download"></i>
            <span>Download & Install PWA App</span>
        </button>
    </div>

    <!-- 1. PWA APP DOWNLOAD & INSTALLATION HIGHLIGHT BANNER -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-900 via-slate-900 to-emerald-950 text-white p-6 sm:p-8 shadow-xl border border-emerald-800/50">
        <!-- Background Glow & Medical Pattern -->
        <div class="absolute right-0 top-0 bottom-0 w-1/3 opacity-10 flex items-center justify-center pointer-events-none">
            <i class="fa-solid fa-laptop-medical text-[220px]"></i>
        </div>

        <div class="relative z-10 max-w-3xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold mb-3 border border-emerald-500/30">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>PROGRESSIVE WEB APP (PWA) READY</span>
            </div>

            <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-white mb-2">
                Download & Install Apex Pharma ERP on Desktop & Mobile
            </h2>
            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed mb-6 font-normal">
                Run Apex Pharma ERP as a dedicated native desktop application with fullscreen Marg-speed keyboard billing (<kbd class="font-mono bg-slate-800 px-1.5 py-0.5 rounded text-emerald-300 border border-slate-700">F1</kbd>-<kbd class="font-mono bg-slate-800 px-1.5 py-0.5 rounded text-emerald-300 border border-slate-700">F9</kbd>), continuous USB barcode scanning, and offline counter resilience without opening a browser tab.
            </p>

            <!-- Actions and Status -->
            <div class="flex flex-wrap items-center gap-3.5 mb-6">
                <!-- Trigger Button -->
                <button type="button" 
                        onclick="installPwaApp()" 
                        id="mainPwaInstallBtn"
                        class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded-xl text-xs font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 shadow-lg shadow-emerald-500/30 transition transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-download text-sm"></i>
                    <span id="pwaBtnText">Install to PC / Mobile Screen</span>
                </button>

                <!-- Help Button -->
                <button type="button" 
                        onclick="document.getElementById('pwaGuideModal').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 px-5 py-3.5 rounded-xl text-xs font-bold text-slate-200 bg-slate-800/80 hover:bg-slate-800 border border-slate-700 transition">
                    <i class="fa-solid fa-circle-question"></i>
                    <span>Installation Guide</span>
                </button>

                <!-- PWA Service Worker Status Pill -->
                <div class="flex items-center gap-2 text-xs bg-slate-800/60 border border-slate-700/80 px-3.5 py-2.5 rounded-xl text-slate-300 font-mono">
                    <i class="fa-solid fa-shield-halved text-emerald-400 text-xs"></i>
                    <span id="pwaStatusText">Service Worker: Active & Precached</span>
                </div>
            </div>

            <!-- Compatibility Badges -->
            <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-400 font-medium">
                <span class="text-slate-300 font-bold">Compatible With:</span>
                <span class="inline-flex items-center gap-1 bg-slate-800/90 px-2.5 py-1 rounded-lg border border-slate-700/60 text-slate-300">
                    <i class="fa-brands fa-windows text-blue-400"></i> Windows 10/11
                </span>
                <span class="inline-flex items-center gap-1 bg-slate-800/90 px-2.5 py-1 rounded-lg border border-slate-700/60 text-slate-300">
                    <i class="fa-brands fa-apple text-slate-300"></i> macOS & iPadOS
                </span>
                <span class="inline-flex items-center gap-1 bg-slate-800/90 px-2.5 py-1 rounded-lg border border-slate-700/60 text-slate-300">
                    <i class="fa-brands fa-android text-emerald-400"></i> Android Tablet & Phone
                </span>
                <span class="inline-flex items-center gap-1 bg-slate-800/90 px-2.5 py-1 rounded-lg border border-slate-700/60 text-slate-300">
                    <i class="fa-brands fa-chrome text-amber-400"></i> Chrome / Edge / Brave
                </span>
            </div>
        </div>
    </div>

    <!-- 2. USER ROLE IDENTITY & STATS GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 1 Column: Role Badge & Statutory Details -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
            <div>
                <!-- User Avatar & Core Role Info -->
                <div class="flex items-center gap-3.5 pb-5 border-b border-slate-100">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-black text-xl flex items-center justify-center shadow-md shadow-emerald-600/30 shrink-0">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <h2 class="text-base font-extrabold text-slate-900 leading-tight truncate">{{ $user->name }}</h2>
                        </div>
                        <div class="text-xs text-slate-500 font-mono truncate mt-0.5">{{ $user->email }}</div>
                        
                        <!-- Role Pill -->
                        <div class="mt-2">
                            @if($user->hasRole('super_admin'))
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                    <i class="fa-solid fa-crown text-[10px]"></i> Super Administrator
                                </span>
                            @elseif($user->hasRole('organization_owner'))
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                    <i class="fa-solid fa-user-tie text-[10px]"></i> Organization Owner
                                </span>
                            @elseif($user->hasRole('pharmacist'))
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <i class="fa-solid fa-user-doctor text-[10px]"></i> Registered Pharmacist
                                </span>
                            @elseif($user->hasRole('cashier'))
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                    <i class="fa-solid fa-cash-register text-[10px]"></i> POS Cashier
                                </span>
                            @elseif($user->hasRole('purchase_manager'))
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-xs font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                                    <i class="fa-solid fa-truck text-[10px]"></i> Purchase Manager
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Statutory Licences & Metadata -->
                <div class="py-4 space-y-3 text-xs">
                    <div class="flex items-center justify-between text-slate-600">
                        <span class="text-slate-400">Organization:</span>
                        <span class="font-bold text-slate-900 truncate max-w-[180px]">{{ $user->organization?->name ?? 'Apex Healthcare & Pharma' }}</span>
                    </div>

                    <div class="flex items-center justify-between text-slate-600">
                        <span class="text-slate-400">Active Branch:</span>
                        <span class="font-bold text-slate-900 truncate max-w-[180px]">{{ $user->branch?->name ?? 'Main Retail Dispensary' }}</span>
                    </div>

                    <div class="flex items-center justify-between text-slate-600">
                        <span class="text-slate-400">Phone Number:</span>
                        <span class="font-mono text-slate-800 font-semibold">{{ $user->phone ?? '+91 Not Set' }}</span>
                    </div>

                    @if($user->pharmacist_reg_number)
                    <div class="flex items-center justify-between text-slate-600">
                        <span class="text-slate-400">Pharmacy Reg No:</span>
                        <span class="font-mono text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 text-[11px]">
                            {{ $user->pharmacist_reg_number }}
                        </span>
                    </div>
                    @endif

                    <div class="flex items-center justify-between text-slate-600">
                        <span class="text-slate-400">GSTIN:</span>
                        <span class="font-mono text-slate-700 font-semibold">{{ $user->organization?->gstin ?? '29AAACA1234A1Z5' }}</span>
                    </div>

                    <div class="flex items-center justify-between text-slate-600">
                        <span class="text-slate-400">Drug License 20B/21B:</span>
                        <span class="font-mono text-slate-700 font-semibold text-[11px]">{{ $user->branch?->dl_number_20b ?? 'KA-BNG-20B-99881' }}</span>
                    </div>
                </div>
            </div>

            <!-- User Quick Stats Cards -->
            <div class="pt-4 border-t border-slate-100 grid grid-cols-3 gap-2 text-center">
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-base font-black text-slate-900 font-mono">{{ $userStats['total_sales_count'] }}</div>
                    <div class="text-[10px] text-slate-500 font-medium">Invoices</div>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-base font-black text-emerald-700 font-mono">{{ $userStats['total_shifts_count'] }}</div>
                    <div class="text-[10px] text-slate-500 font-medium">Shifts</div>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-base font-black text-purple-700 font-mono">{{ $userStats['total_audit_actions'] }}</div>
                    <div class="text-[10px] text-slate-500 font-medium">Actions</div>
                </div>
            </div>
        </div>

        <!-- Right 2 Columns: Editable Profile & Security Forms -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Card A: Edit Profile Details -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Personal & Pharmacist Information</h3>
                        <p class="text-xs text-slate-500">Update your account name, mobile number, and state pharmacy registration number.</p>
                    </div>
                    <span class="text-[11px] text-slate-400 font-mono">User ID: #{{ $user->id }}</span>
                </div>

                <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Full Name
                            </label>
                            <input type="text" 
                                   name="name" 
                                   value="{{ old('name', $user->name) }}" 
                                   required 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Email Address <span class="text-slate-400 font-normal lowercase">(read-only login ID)</span>
                            </label>
                            <input type="email" 
                                   value="{{ $user->email }}" 
                                   disabled 
                                   class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-500 cursor-not-allowed font-mono" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Contact Phone Number
                            </label>
                            <input type="text" 
                                   name="phone" 
                                   value="{{ old('phone', $user->phone) }}" 
                                   placeholder="+91 9876543210" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Pharmacist Council Reg No.
                            </label>
                            <input type="text" 
                                   name="pharmacist_reg_number" 
                                   value="{{ old('pharmacist_reg_number', $user->pharmacist_reg_number) }}" 
                                   placeholder="e.g. KA-PHARM-2024-8890" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition font-mono" />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" 
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Profile Changes</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Card B: Security & Password Update -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Security & Password</h3>
                        <p class="text-xs text-slate-500">Ensure your account uses a strong password (minimum 8 characters).</p>
                    </div>
                    <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                        <i class="fa-solid fa-lock text-[10px]"></i> 256-bit BCrypt
                    </span>
                </div>

                <form action="{{ route('profile.password') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Current Password
                            </label>
                            <input type="password" 
                                   name="current_password" 
                                   required 
                                   placeholder="••••••••" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                New Password
                            </label>
                            <input type="password" 
                                   name="password" 
                                   required 
                                   placeholder="Minimum 8 characters" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Confirm New Password
                            </label>
                            <input type="password" 
                                   name="password_confirmation" 
                                   required 
                                   placeholder="Re-enter password" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition" />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" 
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-slate-800 bg-slate-100 hover:bg-slate-200 border border-slate-300 transition">
                            <i class="fa-solid fa-key"></i>
                            <span>Update Password</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <!-- 3. ROLE PERMISSIONS MATRIX & AUDIT LOG -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Role Permissions Breakdown -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-user-shield text-purple-600"></i>
                    <h3 class="text-sm font-bold text-slate-900">Role Permissions Matrix</h3>
                </div>
                <span class="text-[11px] font-mono text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-200 font-bold">
                    {{ $user->hasRole('super_admin') ? 'All 100% Granted' : $userPermissions->count() . ' Granted' }}
                </span>
            </div>

            @if($user->hasRole('super_admin'))
                <div class="p-3.5 rounded-xl bg-purple-50 border border-purple-200 text-purple-900 text-xs mb-3 flex items-center gap-2.5">
                    <i class="fa-solid fa-crown text-purple-600 text-base"></i>
                    <span><strong>Super Administrator:</strong> You have unrestricted system-wide authority across all branches, organizations, databases, and financial statements.</span>
                </div>
            @endif

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                @php
                    $standardPermissions = [
                        ['slug' => 'sales.view', 'label' => 'View Sales'],
                        ['slug' => 'sales.create', 'label' => 'Fast POS Billing'],
                        ['slug' => 'returns.create', 'label' => 'Sales Returns'],
                        ['slug' => 'inventory.view', 'label' => 'View Stock'],
                        ['slug' => 'batches.view', 'label' => 'FEFO Expiries'],
                        ['slug' => 'products.create', 'label' => 'Add Medicines'],
                        ['slug' => 'purchase.view', 'label' => 'View Purchases'],
                        ['slug' => 'purchase.create', 'label' => 'Inward Bills'],
                        ['slug' => 'reports.gst.view', 'label' => 'GSTR-1 & 3B'],
                        ['slug' => 'reports.profit.view', 'label' => 'P&L Statements'],
                        ['slug' => 'branches.manage', 'label' => 'Branch Switch'],
                        ['slug' => 'audit_logs.view', 'label' => 'Audit Security'],
                    ];
                @endphp

                @foreach($standardPermissions as $perm)
                    @php
                        $isGranted = $user->hasRole('super_admin') || $user->hasRole('organization_owner') || $user->hasPermission($perm['slug']);
                    @endphp
                    <div class="flex items-center gap-2 p-2 rounded-lg border {{ $isGranted ? 'bg-emerald-50/70 border-emerald-200 text-emerald-900' : 'bg-slate-50 border-slate-200 text-slate-400 opacity-60' }}">
                        <i class="fa-solid {{ $isGranted ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-slate-400' }} text-xs"></i>
                        <span class="font-semibold truncate">{{ $perm['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Recent Audit History -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-blue-600"></i>
                    <h3 class="text-sm font-bold text-slate-900">Your Recent Activity Log</h3>
                </div>
                <span class="text-[11px] text-slate-400 font-mono">Immutable Log</span>
            </div>

            <div class="space-y-2.5 max-h-[220px] overflow-y-auto pr-1">
                @forelse($recentAuditLogs as $log)
                <div class="flex items-start justify-between p-2 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                    <div class="min-w-0 flex-1 pr-2">
                        <div class="font-bold text-slate-800 truncate font-mono text-[11px]">
                            {{ $log->action }}
                        </div>
                        <div class="text-[11px] text-slate-500 truncate">
                            {{ $log->description ?: 'System operation recorded' }}
                        </div>
                    </div>
                    <span class="text-[10px] text-slate-400 whitespace-nowrap font-mono">
                        {{ $log->created_at->diffForHumans() }}
                    </span>
                </div>
                @empty
                <div class="p-6 text-center text-xs text-slate-400">
                    <i class="fa-solid fa-shield text-xl mb-1 text-slate-300"></i>
                    <div>No recent activity logged yet for this account.</div>
                </div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
