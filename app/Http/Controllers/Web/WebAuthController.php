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

            // Set active branch in session if user has a default branch
            $user = Auth::user();
            if ($user->branch_id && !session()->has('active_branch_id')) {
                session(['active_branch_id' => $user->branch_id]);
            }

            return redirect()->intended(route('pos.index'))
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
        $branch = Branch::where('id', $request->branch_id)
            ->where('organization_id', $user->organization_id)
            ->firstOrFail();

        session(['active_branch_id' => $branch->id]);

        return back()->with('success', "Switched active branch to: {$branch->name}");
    }
}
