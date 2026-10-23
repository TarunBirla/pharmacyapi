<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CashierShift;
use App\Models\SalesInvoice;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileWebController extends Controller
{
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->load(['roles.permissions', 'organization.branches', 'branch']);

        // Cashier shift status
        $currentShift = CashierShift::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('started_at')
            ->first();

        // User activity statistics
        $userStats = [
            'total_sales_count' => SalesInvoice::where('organization_id', $user->organization_id)
                ->when(!$user->hasRole(['super_admin', 'organization_owner']), function ($q) use ($user) {
                    $q->where('branch_id', $user->branch_id);
                })->count(),
            'total_shifts_count' => CashierShift::where('user_id', $user->id)->count(),
            'total_audit_actions' => AuditLog::where('user_id', $user->id)->count(),
        ];

        // Recent audit trail for this user
        $recentAuditLogs = AuditLog::where('user_id', $user->id)
            ->latest()
            ->take(8)
            ->get();

        // Flatten permissions for this user
        $userPermissions = $user->roles->flatMap->permissions->unique('id');

        return view('profile.index', compact(
            'user',
            'currentShift',
            'userStats',
            'recentAuditLogs',
            'userPermissions'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'pharmacist_reg_number' => 'nullable|string|max:100',
        ]);

        $oldValues = $user->only(['name', 'phone', 'pharmacist_reg_number']);

        $user->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'pharmacist_reg_number' => $validated['pharmacist_reg_number'] ?? null,
        ]);

        AuditLogService::log(
            action: 'user.profile.update',
            description: "User '{$user->name}' updated their profile details.",
            entity: $user,
            oldValues: $oldValues,
            newValues: $user->only(['name', 'phone', 'pharmacist_reg_number'])
        );

        return redirect()->route('profile.index')->with('success', 'Profile information updated successfully!');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        AuditLogService::log(
            action: 'user.password.update',
            description: "User '{$user->name}' updated their account password.",
            entity: $user
        );

        return redirect()->route('profile.index')->with('success', 'Account password changed successfully!');
    }
}
