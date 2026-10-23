<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['organization', 'branch', 'roles.permissions']);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('slug', $request->role));
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        $users = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users)->response()->getData(true),
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['organization', 'branch', 'roles.permissions', 'assignedBranches']);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $currentUser = $request->user();

        $user = new User([
            'organization_id' => $currentUser->organization_id,
            'branch_id' => $data['branch_id'] ?? $currentUser->branch_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'pharmacist_reg_number' => $data['pharmacist_reg_number'] ?? null,
            'password' => Hash::make($data['password']),
            'status' => 'active',
        ]);
        $user->save();

        // Assign roles
        $roles = Role::whereIn('slug', $data['role_slugs'])->get();
        $user->roles()->sync($roles->pluck('id'));

        // Assign additional branches if specified
        if (!empty($data['assigned_branch_ids'])) {
            $user->assignedBranches()->sync($data['assigned_branch_ids']);
        }

        AuditLogService::log(
            action: 'user.create',
            description: "Created user {$user->name} ({$user->email})",
            entity: $user
        );

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => new UserResource($user->load(['organization', 'branch', 'roles.permissions'])),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:25'],
            'pharmacist_reg_number' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'in:active,inactive,blocked'],
            'password' => ['nullable', 'string', 'min:8'],
            'role_slugs' => ['nullable', 'array'],
            'role_slugs.*' => ['string', 'exists:roles,slug'],
            'assigned_branch_ids' => ['nullable', 'array'],
            'assigned_branch_ids.*' => ['exists:branches,id'],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        if (isset($validated['role_slugs'])) {
            $roles = Role::whereIn('slug', $validated['role_slugs'])->get();
            $user->roles()->sync($roles->pluck('id'));
        }

        if (isset($validated['assigned_branch_ids'])) {
            $user->assignedBranches()->sync($validated['assigned_branch_ids']);
        }

        AuditLogService::log(
            action: 'user.update',
            description: "Updated user {$user->email}",
            entity: $user
        );

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => new UserResource($user->load(['organization', 'branch', 'roles.permissions'])),
        ]);
    }
}
