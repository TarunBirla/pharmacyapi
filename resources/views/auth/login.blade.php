<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apex Pharma ERP - Secure Member Sign In</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between p-4 selection:bg-emerald-500 selection:text-white">

    <!-- Top Simple Header -->
    <div class="max-w-xl w-full mx-auto flex items-center justify-between py-2">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-emerald-700 transition">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back to Home</span>
        </a>
        <div class="text-[11px] font-semibold text-slate-400">
            Enterprise Pharmacy ERP v2.4
        </div>
    </div>

    <div class="max-w-md w-full mx-auto my-auto py-6">
        <!-- Logo and Brand -->
        <div class="text-center mb-6">
            <a href="{{ url('/') }}" class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-500/30 mb-3 hover:scale-105 transition transform">
                <i class="fa-solid fa-notes-medical text-2xl"></i>
            </a>
            <h1 class="text-2xl font-black tracking-tight text-slate-900">APEX PHARMA ERP</h1>
            <p class="text-xs text-slate-500 mt-1 font-medium">Marg-Style POS Billing & Enterprise Pharmacy Operating System</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-xl shadow-slate-200/60 p-7 space-y-6">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Sign in to your account</h2>
                <p class="text-xs text-slate-500 mt-0.5">Enter your credentials to access the POS terminal & management modules</p>
            </div>

            @if(session('info'))
            <div class="p-3.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-info shrink-0"></i>
                <span>{{ session('info') }}</span>
            </div>
            @endif

            @if(session('success'))
            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-check shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
            @endif

            @if($errors->any())
            <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                <span>{{ $errors->first() }}</span>
            </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4" id="loginForm">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Email Address
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                        <input type="email" 
                               name="email" 
                               id="loginEmail" 
                               value="{{ request('email', old('email', 'pharmacist@apollocare.com')) }}" 
                               required 
                               placeholder="e.g. pharmacist@apollocare.com"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition" />
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Password
                        </label>
                        <span class="text-[11px] text-slate-400 font-mono">Default: Password123!</span>
                    </div>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                        <input type="password" 
                               name="password" 
                               id="loginPassword" 
                               value="Password123!" 
                               required 
                               placeholder="••••••••"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition" />
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center gap-2 text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span>Remember this terminal</span>
                    </label>
                </div>

                <button type="submit" 
                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs shadow-md shadow-emerald-600/25 transition flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    <span>Sign In to Terminal</span>
                </button>
            </form>

            <!-- Quick Demo Credentials Picker (All 5 Roles) -->
            <div class="border-t border-slate-100 pt-5">
                <div class="flex items-center justify-between mb-2.5">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        1-Click Role Login
                    </span>
                    <span class="text-[10px] text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                        Click to Switch
                    </span>
                </div>
                
                <div class="space-y-1.5">
                    <!-- Super Admin -->
                    <button type="button" 
                            onclick="fillDemo('admin@pharmacy.com')" 
                            class="w-full bg-slate-50 hover:bg-purple-50 border border-slate-200 hover:border-purple-300 p-2 rounded-xl transition text-left flex items-center justify-between group">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center text-[10px] font-bold">
                                <i class="fa-solid fa-crown"></i>
                            </span>
                            <div>
                                <div class="text-xs font-bold text-slate-800 group-hover:text-purple-700">Super Admin</div>
                                <div class="text-[10px] text-slate-500 font-mono">admin@pharmacy.com</div>
                            </div>
                        </div>
                        <span class="text-[10px] text-purple-600 font-semibold px-2 py-0.5 bg-purple-100 rounded">Fill</span>
                    </button>

                    <!-- Org Owner -->
                    <button type="button" 
                            onclick="fillDemo('owner@apollocare.com')" 
                            class="w-full bg-slate-50 hover:bg-amber-50 border border-slate-200 hover:border-amber-300 p-2 rounded-xl transition text-left flex items-center justify-between group">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-[10px] font-bold">
                                <i class="fa-solid fa-user-tie"></i>
                            </span>
                            <div>
                                <div class="text-xs font-bold text-slate-800 group-hover:text-amber-700">Organization Owner</div>
                                <div class="text-[10px] text-slate-500 font-mono">owner@apollocare.com</div>
                            </div>
                        </div>
                        <span class="text-[10px] text-amber-600 font-semibold px-2 py-0.5 bg-amber-100 rounded">Fill</span>
                    </button>

                    <!-- Pharmacist -->
                    <button type="button" 
                            onclick="fillDemo('pharmacist@apollocare.com')" 
                            class="w-full bg-slate-50 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 p-2 rounded-xl transition text-left flex items-center justify-between group">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold">
                                <i class="fa-solid fa-user-doctor"></i>
                            </span>
                            <div>
                                <div class="text-xs font-bold text-slate-800 group-hover:text-emerald-700">Registered Pharmacist</div>
                                <div class="text-[10px] text-slate-500 font-mono">pharmacist@apollocare.com</div>
                            </div>
                        </div>
                        <span class="text-[10px] text-emerald-600 font-semibold px-2 py-0.5 bg-emerald-100 rounded">Fill</span>
                    </button>

                    <!-- Cashier -->
                    <button type="button" 
                            onclick="fillDemo('cashier@apollocare.com')" 
                            class="w-full bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-300 p-2 rounded-xl transition text-left flex items-center justify-between group">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-[10px] font-bold">
                                <i class="fa-solid fa-cash-register"></i>
                            </span>
                            <div>
                                <div class="text-xs font-bold text-slate-800 group-hover:text-blue-700">POS Cashier</div>
                                <div class="text-[10px] text-slate-500 font-mono">cashier@apollocare.com</div>
                            </div>
                        </div>
                        <span class="text-[10px] text-blue-600 font-semibold px-2 py-0.5 bg-blue-100 rounded">Fill</span>
                    </button>

                    <!-- Purchase Manager -->
                    <button type="button" 
                            onclick="fillDemo('purchaser@apollocare.com')" 
                            class="w-full bg-slate-50 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-300 p-2 rounded-xl transition text-left flex items-center justify-between group">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-[10px] font-bold">
                                <i class="fa-solid fa-truck"></i>
                            </span>
                            <div>
                                <div class="text-xs font-bold text-slate-800 group-hover:text-indigo-700">Purchase Manager</div>
                                <div class="text-[10px] text-slate-500 font-mono">purchaser@apollocare.com</div>
                            </div>
                        </div>
                        <span class="text-[10px] text-indigo-600 font-semibold px-2 py-0.5 bg-indigo-100 rounded">Fill</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-xs text-slate-400">
            &copy; {{ date('Y') }} Apex Pharma ERP. All Rights Reserved.
        </div>
    </div>

    <div></div>

    <script>
        function fillDemo(email) {
            document.getElementById('loginEmail').value = email;
            document.getElementById('loginPassword').value = 'Password123!';
        }
    </script>
</body>
</html>
