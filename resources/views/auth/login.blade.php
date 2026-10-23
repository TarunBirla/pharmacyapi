<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apex Pharma ERP - Login</title>
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
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">
        <!-- Logo and Brand -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-500/25 mb-3">
                <i class="fa-solid fa-notes-medical text-2xl"></i>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900">APEX PHARMA ERP</h1>
            <p class="text-xs text-slate-500 mt-1 font-medium">Enterprise Pharmacy Management & Retail POS Billing</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-xl shadow-slate-200/50 p-7 space-y-6">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Sign in to your account</h2>
                <p class="text-xs text-slate-500 mt-0.5">Enter your credentials to access the POS terminal</p>
            </div>

            @if($errors->any())
            <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                <span>{{ $errors->first() }}</span>
            </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
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
                               value="{{ old('email', 'pharmacist@apollocare.com') }}" 
                               required 
                               placeholder="e.g. pharmacist@apollocare.com"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Password
                    </label>
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
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span>Remember this device</span>
                    </label>
                </div>

                <button type="submit" 
                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs shadow-md shadow-emerald-600/20 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    <span>Secure Sign In</span>
                </button>
            </form>

            <!-- Quick Demo Credentials Picker -->
            <div class="border-t border-slate-100 pt-5">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2.5 text-center">
                    Quick Demo Logins (Click to Fill)
                </div>
                <div class="grid grid-cols-2 gap-2 text-left">
                    <button type="button" 
                            onclick="fillDemo('pharmacist@apexpharma.com')" 
                            class="bg-slate-50 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 p-2.5 rounded-xl transition text-xs">
                        <div class="font-bold text-emerald-700 flex items-center gap-1">
                            <i class="fa-solid fa-user-doctor text-[10px]"></i> Pharmacist
                        </div>
                        <div class="text-[10px] text-slate-500 font-mono truncate">pharmacist@apexpharma.com</div>
                    </button>

                    <button type="button" 
                            onclick="fillDemo('cashier@apexpharma.com')" 
                            class="bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-300 p-2.5 rounded-xl transition text-xs">
                        <div class="font-bold text-blue-700 flex items-center gap-1">
                            <i class="fa-solid fa-cash-register text-[10px]"></i> Cashier
                        </div>
                        <div class="text-[10px] text-slate-500 font-mono truncate">cashier@apexpharma.com</div>
                    </button>

                    <button type="button" 
                            onclick="fillDemo('purchase@apexpharma.com')" 
                            class="bg-slate-50 hover:bg-purple-50 border border-slate-200 hover:border-purple-300 p-2.5 rounded-xl transition text-xs">
                        <div class="font-bold text-purple-700 flex items-center gap-1">
                            <i class="fa-solid fa-truck text-[10px]"></i> Purchase Mgr
                        </div>
                        <div class="text-[10px] text-slate-500 font-mono truncate">purchase@apexpharma.com</div>
                    </button>

                    <button type="button" 
                            onclick="fillDemo('owner@apexpharma.com')" 
                            class="bg-slate-50 hover:bg-amber-50 border border-slate-200 hover:border-amber-300 p-2.5 rounded-xl transition text-xs">
                        <div class="font-bold text-amber-700 flex items-center gap-1">
                            <i class="fa-solid fa-user-tie text-[10px]"></i> Org Owner
                        </div>
                        <div class="text-[10px] text-slate-500 font-mono truncate">owner@apexpharma.com</div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-xs text-slate-400">
            &copy; {{ date('Y') }} Apex Pharma ERP. Marg-style High-Speed Retail Billing.
        </div>
    </div>

    <script>
        function fillDemo(email) {
            document.getElementById('loginEmail').value = email;
            document.getElementById('loginPassword').value = 'Password123!';
        }
    </script>
</body>
</html>
