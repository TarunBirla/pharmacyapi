<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class WebAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('pos.index');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            /** @var User $user */
            $user = Auth::user();

            // If user has no organization_id or branch_id (e.g. Super Admin), assign first available defaults
            if (!$user->organization_id) {
                $firstOrg = \App\Models\Organization::first();
                $firstBranch = \App\Models\Branch::first();
                if ($firstOrg) {
                    $user->organization_id = $firstOrg->id;
                    $user->branch_id = $firstBranch?->id;
                    $user->save();
                }
            }

            // Set active branch and organization in session
            $branchId = $user->branch_id ?? \App\Models\Branch::first()?->id;
            $orgId = $user->organization_id ?? \App\Models\Organization::first()?->id;
            
            session([
                'active_branch_id' => $branchId,
                'active_organization_id' => $orgId,
            ]);

            // Role-based smart redirection
            if ($user->hasRole('purchase_manager')) {
                return redirect()->to(route('procurement.index'))
                    ->with('success', "Welcome back, {$user->name}!");
            }

            return redirect()->to(route('pos.index'))
                ->with('success', "Welcome back, {$user->name}!");
        }

        return back()->withErrors([
            'email' => 'Invalid email or password. Please verify your credentials.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'You have been logged out successfully.');
    }

    public function switchBranch(Request $request): RedirectResponse
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $user = Auth::user();
        
        if ($user->hasRole('super_admin')) {
            $branch = Branch::findOrFail($request->branch_id);
        } else {
            $branch = Branch::where('id', $request->branch_id)
                ->where('organization_id', $user->organization_id)
                ->firstOrFail();
        }

        session([
            'active_branch_id' => $branch->id,
            'active_organization_id' => $branch->organization_id,
        ]);

        return back()->with('success', "Switched active branch to: {$branch->name}");
    }
}
