<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apex Pharma ERP - Enterprise Pharmacy Management & High-Speed Retail Billing</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
    </style>
</head>
<body class="bg-white text-slate-800 antialiased selection:bg-emerald-500 selection:text-white">

    <!-- Top Notice Bar -->
    <div class="bg-slate-900 text-slate-300 text-xs py-2 px-4 border-b border-slate-800">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                    <i class="fa-solid fa-bolt text-[9px] mr-1"></i> LIVE ERP v2.4
                </span>
                <span class="hidden sm:inline text-slate-400">Indian GST Compliant • Marg-Style Fast Keyboard POS • Multi-Branch FEFO Engine</span>
            </div>
            <div class="flex items-center gap-4 text-[11px]">
                <a href="#demo-logins" class="text-emerald-400 hover:text-emerald-300 font-semibold flex items-center gap-1 transition">
                    <i class="fa-solid fa-key text-[10px]"></i> Demo Logins
                </a>
                <span class="text-slate-600">|</span>
                <a href="{{ route('login') }}" class="text-slate-300 hover:text-white font-medium transition">
                    <i class="fa-solid fa-arrow-right-to-bracket text-[10px] mr-1"></i> Member Sign In
                </a>
            </div>
        </div>
    </div>

    <!-- Main Navigation Header -->
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm transition-all">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <!-- Brand Logo -->
            <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                <div class="w-11 h-11 rounded-2xl bg-emerald-600 flex items-center justify-center text-white shadow-md shadow-emerald-500/30 group-hover:scale-105 transition">
                    <i class="fa-solid fa-notes-medical text-2xl"></i>
                </div>
                <div>
                    <div class="text-lg font-black tracking-tight text-slate-900 flex items-center gap-1.5">
                        <span>APEX PHARMA</span>
                        <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-1.5 py-0.5 rounded-md border border-emerald-300 uppercase">PRO ERP</span>
                    </div>
                    <div class="text-[11px] text-slate-500 font-medium">Healthcare Pharmacy & Retail Operating System</div>
                </div>
            </a>

            <!-- Navigation Links -->
            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-600">
                <a href="#features" class="hover:text-emerald-600 transition">Features</a>
                <a href="#pos-billing" class="hover:text-emerald-600 transition">Marg POS</a>
                <a href="#fefo-inventory" class="hover:text-emerald-600 transition">FEFO Inventory</a>
                <a href="#gst-reports" class="hover:text-emerald-600 transition">GST & Accounts</a>
                <a href="#demo-logins" class="hover:text-emerald-600 transition flex items-center gap-1.5 text-emerald-700">
                    <i class="fa-solid fa-users text-xs"></i> User Roles
                </a>
            </nav>

            <!-- Header Action Buttons -->
            <div class="flex items-center gap-3">
                <a href="#demo-logins" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition border border-slate-200">
                    <i class="fa-solid fa-laptop-medical text-emerald-600"></i>
                    <span>Quick Demo</span>
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-600/30 transition transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Login to ERP</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative pt-12 pb-20 md:pt-16 md:pb-28 overflow-hidden bg-gradient-to-b from-slate-50 via-white to-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold mb-6 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Built for Modern Retail Chemists & Wholesale Stockists
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 tracking-tight leading-[1.15] mb-6">
                    High-Speed Marg-Style Billing, <span class="text-emerald-600">FEFO Expiry Control</span> & AI Pharmacy ERP
                </h1>
                <p class="text-base sm:text-lg text-slate-600 leading-relaxed font-normal mb-8 max-w-2xl mx-auto">
                    Complete all-in-one pharmacy software tailored for Indian regulations: continuous barcode scanning, Schedule H drug compliance, 80mm thermal receipt printing, GSTR-1/3B filing, and AI-predicted patient refills.
                </p>

                <!-- Hero CTAs -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3.5 mb-12">
                    <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-600/30 transition transform hover:-translate-y-0.5">
                        <i class="fa-solid fa-cash-register text-base"></i>
                        <span>Launch POS Counter</span>
                    </a>
                    <a href="#demo-logins" class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-xl text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 shadow-sm transition">
                        <i class="fa-solid fa-id-badge text-slate-400"></i>
                        <span>Explore 5 User Roles</span>
                    </a>
                </div>

                <!-- Live Metrics Ticker -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-5 rounded-2xl bg-white border border-slate-200 shadow-lg shadow-slate-200/50">
                    <div class="text-center p-2 border-r border-slate-100 last:border-0">
                        <div class="text-2xl font-black text-slate-900 font-mono">0.8s</div>
                        <div class="text-xs text-slate-500 font-medium mt-0.5">Counter Checkout Speed</div>
                    </div>
                    <div class="text-center p-2 border-r border-slate-100 last:border-0">
                        <div class="text-2xl font-black text-emerald-600 font-mono">100%</div>
                        <div class="text-xs text-slate-500 font-medium mt-0.5">FEFO Expiry Adherence</div>
                    </div>
                    <div class="text-center p-2 border-r border-slate-100 last:border-0">
                        <div class="text-2xl font-black text-blue-600 font-mono">GSTR-1/3B</div>
                        <div class="text-xs text-slate-500 font-medium mt-0.5">Automated Tax Ledger</div>
                    </div>
                    <div class="text-center p-2">
                        <div class="text-2xl font-black text-purple-600 font-mono">AI-Driven</div>
                        <div class="text-xs text-slate-500 font-medium mt-0.5">Demand & Refill Forecast</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Key Modules Grid -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-xs font-black tracking-widest text-emerald-600 uppercase mb-2">COMPLETE WORKFLOW ENGINE</h2>
                <h3 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Everything Your Pharmacy Needs in One Platform</h3>
                <p class="text-sm text-slate-500 mt-3 font-medium">Replace multiple disconnected softwares with a unified enterprise grade system.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Feature 1: POS -->
                <div id="pos-billing" class="p-7 rounded-2xl bg-slate-50 border border-slate-200 hover:border-emerald-500/50 hover:bg-white hover:shadow-xl hover:shadow-slate-200/50 transition group">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl mb-5 group-hover:scale-110 transition">
                        <i class="fa-solid fa-cash-register"></i>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <h4 class="text-base font-bold text-slate-900">Marg-Style Keyboard POS</h4>
                        <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-1.5 py-0.5 rounded font-mono">F1-F9</span>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        Lightning fast keyboard shortcuts for mouse-free billing. Real-time barcode scan, multi-payment splits (Cash, UPI, Card), bill hold & resume, and cashier shift management.
                    </p>
                    <ul class="text-xs text-slate-500 space-y-1.5">
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600 text-[10px]"></i> 80mm ESC/POS Thermal Auto-Print</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-emerald-600 text-[10px]"></i> Schedule H/H1 Prescription Lock</li>
                    </ul>
                </div>

                <!-- Feature 2: FEFO Inventory -->
                <div id="fefo-inventory" class="p-7 rounded-2xl bg-slate-50 border border-slate-200 hover:border-blue-500/50 hover:bg-white hover:shadow-xl hover:shadow-slate-200/50 transition group">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-xl mb-5 group-hover:scale-110 transition">
                        <i class="fa-solid fa-pills"></i>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <h4 class="text-base font-bold text-slate-900">Smart FEFO Drug Inventory</h4>
                        <span class="text-[10px] bg-blue-100 text-blue-800 font-bold px-1.5 py-0.5 rounded font-mono">FEFO</span>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        Automatic First-Expiry-First-Out batch selection protects from selling expired drugs. Multi-pack conversion (Strips to Tablets), rack/shelf tagging, and generic salt substitute finder.
                    </p>
                    <ul class="text-xs text-slate-500 space-y-1.5">
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-blue-600 text-[10px]"></i> Near-Expiry &lt;90 Days Risk Alert</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-blue-600 text-[10px]"></i> Minimum Reorder Stock Warnings</li>
                    </ul>
                </div>

                <!-- Feature 3: Procurement -->
                <div class="p-7 rounded-2xl bg-slate-50 border border-slate-200 hover:border-purple-500/50 hover:bg-white hover:shadow-xl hover:shadow-slate-200/50 transition group">
                    <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl mb-5 group-hover:scale-110 transition">
                        <i class="fa-solid fa-truck-ramp-box"></i>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <h4 class="text-base font-bold text-slate-900">Procurement & Inward GRN</h4>
                        <span class="text-[10px] bg-purple-100 text-purple-800 font-bold px-1.5 py-0.5 rounded font-mono">B2B</span>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        Direct inward purchase invoice posting with batch numbers, manufacturing dates, and purchase rates. Vendor ledger tracking and duplicate invoice protection.
                    </p>
                    <ul class="text-xs text-slate-500 space-y-1.5">
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-purple-600 text-[10px]"></i> Automated Supplier Ledger Posting</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-purple-600 text-[10px]"></i> Purchase Return with Debit Notes</li>
                    </ul>
                </div>

                <!-- Feature 4: GST & Accounts -->
                <div id="gst-reports" class="p-7 rounded-2xl bg-slate-50 border border-slate-200 hover:border-amber-500/50 hover:bg-white hover:shadow-xl hover:shadow-slate-200/50 transition group">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-xl mb-5 group-hover:scale-110 transition">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <h4 class="text-base font-bold text-slate-900">Indian GST & Financial Ledger</h4>
                        <span class="text-[10px] bg-amber-100 text-amber-800 font-bold px-1.5 py-0.5 rounded font-mono">GSTR</span>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        Double-entry balanced accounting built in. Automated journal vouchers generated on every sale and purchase. Instant GSTR-1, GSTR-3B, Trial Balance, P&L, and Balance Sheet.
                    </p>
                    <ul class="text-xs text-slate-500 space-y-1.5">
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-amber-600 text-[10px]"></i> CGST, SGST, IGST & HSN Summaries</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-amber-600 text-[10px]"></i> Input Tax Credit (ITC) Reconciler</li>
                    </ul>
                </div>

                <!-- Feature 5: AI Analytics -->
                <div class="p-7 rounded-2xl bg-slate-50 border border-slate-200 hover:border-indigo-500/50 hover:bg-white hover:shadow-xl hover:shadow-slate-200/50 transition group">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-xl mb-5 group-hover:scale-110 transition">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <h4 class="text-base font-bold text-slate-900">AI Predictive Intelligence</h4>
                        <span class="text-[10px] bg-indigo-100 text-indigo-800 font-bold px-1.5 py-0.5 rounded font-mono">AI</span>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        Machine learning demand forecasting based on daily sales velocity. Proactively predicts drug stockouts, flags stagnant dead stock for liquidation, and tracks chronic patient refills.
                    </p>
                    <ul class="text-xs text-slate-500 space-y-1.5">
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-indigo-600 text-[10px]"></i> Automated Run-Rate Reorder Engine</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-indigo-600 text-[10px]"></i> WhatsApp Refill Reminder Engine</li>
                    </ul>
                </div>

                <!-- Feature 6: Security & Audit -->
                <div class="p-7 rounded-2xl bg-slate-50 border border-slate-200 hover:border-slate-500/50 hover:bg-white hover:shadow-xl hover:shadow-slate-200/50 transition group">
                    <div class="w-12 h-12 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center text-xl mb-5 group-hover:scale-110 transition">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <h4 class="text-base font-bold text-slate-900">Security & Tamper Audit</h4>
                        <span class="text-[10px] bg-slate-200 text-slate-800 font-bold px-1.5 py-0.5 rounded font-mono">SEC</span>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        Multi-tenant enterprise isolation, strict role permissions, and immutable audit logging capturing IP addresses, timestamps, and JSON diffs of before/after records.
                    </p>
                    <ul class="text-xs text-slate-500 space-y-1.5">
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-slate-700 text-[10px]"></i> Granular Role-Based Access Control</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-slate-700 text-[10px]"></i> Immutable Ledger Audit History</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Interactive User Roles Demo Credentials Section -->
    <section id="demo-logins" class="py-20 bg-slate-50 border-t border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold mb-3 border border-emerald-200">
                    <i class="fa-solid fa-key text-[10px]"></i> 1-CLICK INSTANT DEMO ACCOUNTS
                </div>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Test Every Role in Action</h2>
                <p class="text-sm text-slate-600 mt-2 font-medium">Click on any role to immediately test the system with full sample data.</p>
                <div class="mt-3 inline-block px-3 py-1 bg-white border border-slate-200 rounded-lg text-xs font-mono text-slate-500">
                    Common Password for all accounts: <span class="font-bold text-slate-900">Password123!</span>
                </div>
            </div>

            <!-- Role Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- 1. Super Admin -->
                <div class="bg-white border-2 border-purple-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition relative flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-purple-100 text-purple-800 border border-purple-200 flex items-center gap-1.5">
                                <i class="fa-solid fa-crown text-[10px]"></i> Super Admin
                            </span>
                            <span class="text-[10px] font-bold text-slate-400 font-mono">GLOBAL ACCESS</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-1">System Super Admin</h3>
                        <p class="text-xs text-slate-500 mb-4">Unrestricted access to all organizations, branches, audit trails, and global system health.</p>
                        
                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 mb-5 space-y-1 text-xs font-mono">
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Email:</span>
                                <span class="font-bold text-slate-900 select-all">admin@pharmacy.com</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Password:</span>
                                <span class="font-bold text-slate-900">Password123!</span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('login') }}?email=admin@pharmacy.com" class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 transition shadow-sm">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        <span>Login as Super Admin</span>
                    </a>
                </div>

                <!-- 2. Organization Owner -->
                <div class="bg-white border-2 border-amber-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition relative flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200 flex items-center gap-1.5">
                                <i class="fa-solid fa-user-tie text-[10px]"></i> Organization Owner
                            </span>
                            <span class="text-[10px] font-bold text-slate-400 font-mono">FULL ORG CONTROL</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-1">Apollo Care Owner</h3>
                        <p class="text-xs text-slate-500 mb-4">Complete authority over GST returns, financial statements, multi-branch operations, and analytics.</p>
                        
                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 mb-5 space-y-1 text-xs font-mono">
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Email:</span>
                                <span class="font-bold text-slate-900 select-all">owner@apollocare.com</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Password:</span>
                                <span class="font-bold text-slate-900">Password123!</span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('login') }}?email=owner@apollocare.com" class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 transition shadow-sm">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        <span>Login as Org Owner</span>
                    </a>
                </div>

                <!-- 3. Pharmacist -->
                <div class="bg-white border-2 border-emerald-300 rounded-2xl p-6 shadow-sm hover:shadow-md transition relative flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1.5">
                                <i class="fa-solid fa-user-doctor text-[10px]"></i> Pharmacist
                            </span>
                            <span class="text-[10px] font-bold text-emerald-600 font-mono">RECOMMENDED</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-1">Registered Pharmacist</h3>
                        <p class="text-xs text-slate-500 mb-4">Fast POS dispensing, Schedule H verification, generic salt substitution, and batch management.</p>
                        
                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 mb-5 space-y-1 text-xs font-mono">
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Email:</span>
                                <span class="font-bold text-slate-900 select-all">pharmacist@apollocare.com</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Password:</span>
                                <span class="font-bold text-slate-900">Password123!</span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('login') }}?email=pharmacist@apollocare.com" class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition shadow-sm">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        <span>Login as Pharmacist</span>
                    </a>
                </div>

                <!-- 4. Cashier -->
                <div class="bg-white border-2 border-blue-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition relative flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200 flex items-center gap-1.5">
                                <i class="fa-solid fa-cash-register text-[10px]"></i> POS Cashier
                            </span>
                            <span class="text-[10px] font-bold text-slate-400 font-mono">BILLING & SHIFTS</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-1">Front Counter Cashier</h3>
                        <p class="text-xs text-slate-500 mb-4">Keyboard POS billing, split payments, bill holding/resuming, and daily cash shift reconciliation.</p>
                        
                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 mb-5 space-y-1 text-xs font-mono">
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Email:</span>
                                <span class="font-bold text-slate-900 select-all">cashier@apollocare.com</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Password:</span>
                                <span class="font-bold text-slate-900">Password123!</span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('login') }}?email=cashier@apollocare.com" class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 transition shadow-sm">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        <span>Login as Cashier</span>
                    </a>
                </div>

                <!-- 5. Purchase Manager -->
                <div class="bg-white border-2 border-indigo-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition relative flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200 flex items-center gap-1.5">
                                <i class="fa-solid fa-truck text-[10px]"></i> Purchase Manager
                            </span>
                            <span class="text-[10px] font-bold text-slate-400 font-mono">PROCUREMENT & GRN</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-1">Vendor Procurement Head</h3>
                        <p class="text-xs text-slate-500 mb-4">Supplier bills, purchase order inwards, new batch creation, supplier payments and stock replenishment.</p>
                        
                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 mb-5 space-y-1 text-xs font-mono">
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Email:</span>
                                <span class="font-bold text-slate-900 select-all">purchaser@apollocare.com</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Password:</span>
                                <span class="font-bold text-slate-900">Password123!</span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('login') }}?email=purchaser@apollocare.com" class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        <span>Login as Purchase Mgr</span>
                    </a>
                </div>

                <!-- Live Server Setup Helper Card -->
                <div class="bg-white border-2 border-dashed border-slate-300 rounded-2xl p-6 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200 flex items-center gap-1.5">
                                <i class="fa-solid fa-server text-[10px]"></i> Live Deployment
                            </span>
                            <span class="text-[10px] font-bold text-emerald-600 font-mono">READY</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-1">Live Database Setup</h3>
                        <p class="text-xs text-slate-500 mb-4">Hit the live migration endpoint on your server to build tables and seed demo accounts with one click.</p>
                        
                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 mb-5 text-xs font-mono text-slate-700 break-all">
                            /setup-database
                        </div>
                    </div>

                    <a href="{{ url('/setup-database') }}" target="_blank" class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition border border-slate-200">
                        <i class="fa-solid fa-database"></i>
                        <span>Run /setup-database Hook</span>
                    </a>
                </div>

            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600 flex items-center justify-center text-white shadow-sm">
                        <i class="fa-solid fa-notes-medical text-lg"></i>
                    </div>
                    <div>
                        <span class="font-extrabold text-slate-900 tracking-tight text-sm">APEX PHARMA ERP</span>
                        <div class="text-[11px] text-slate-400">Enterprise Pharmacy Management & Retail Billing</div>
                    </div>
                </div>

                <div class="flex items-center gap-6 text-xs text-slate-500 font-medium">
                    <a href="#features" class="hover:text-emerald-600">Features</a>
                    <a href="#demo-logins" class="hover:text-emerald-600">Roles</a>
                    <a href="{{ route('login') }}" class="hover:text-emerald-600">Sign In</a>
                    <a href="{{ url('/setup-database') }}" class="hover:text-emerald-600">DB Setup</a>
                </div>

                <div class="text-xs text-slate-400">
                    &copy; {{ date('Y') }} Apex Pharma ERP. 100% Native Laravel PHP.
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
