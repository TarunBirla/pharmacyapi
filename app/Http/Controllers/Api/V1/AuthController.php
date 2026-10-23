<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::withoutGlobalScopes()
            ->with(['organization', 'branch', 'roles.permissions'])
            ->where('email', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is ' . $user->status . '. Please contact your administrator.'
            ], 403);
        }

        $deviceName = $request->device_name ?? 'API Token';
        $token = $user->createToken($deviceName)->plainTextToken;

        // Log login audit
        auth()->setUser($user);
        AuditLogService::log(
            action: 'auth.login',
            description: "User {$user->email} logged in from " . $request->ip(),
            entity: $user
        );

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => new UserResource($user),
            ]
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing(['organization', 'branch', 'roles.permissions', 'assignedBranches']);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'assigned_branches' => $user->assignedBranches->map(fn ($b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'code' => $b->code,
                ]),
            ]
        ]);
    }

    public function switchBranch(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
        ]);

        $user = $request->user();
        $targetBranchId = (int)$request->branch_id;

        // Verify user is allowed to switch to this branch
        $allowed = false;
        if ($user->hasRole(['super_admin', 'organization_owner'])) {
            $allowed = $user->organization->branches()->where('id', $targetBranchId)->exists();
        } else {
            $allowed = $user->assignedBranches()->where('branches.id', $targetBranchId)->exists() || $user->branch_id === $targetBranchId;
        }

        if (!$allowed) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to switch to this branch.'
            ], 403);
        }

        $oldBranchId = $user->branch_id;
        $user->branch_id = $targetBranchId;
        $user->save();

        AuditLogService::log(
            action: 'branch.switch',
            description: "User switched branch from {$oldBranchId} to {$targetBranchId}",
            entity: $user
        );

        return response()->json([
            'success' => true,
            'message' => 'Branch switched successfully',
            'data' => [
                'user' => new UserResource($user->fresh(['organization', 'branch', 'roles.permissions'])),
            ]
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        AuditLogService::log(
            action: 'auth.logout',
            description: "User {$user->email} logged out",
            entity: $user
        );

        $user->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
