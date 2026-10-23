<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Super Admin can access all tenants
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // If request payload explicitly provides organization_id, it must match user's org
        if ($request->has('organization_id') && (int)$request->input('organization_id') !== (int)$user->organization_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cross-tenant access denied.'
            ], 403);
        }

        // If request provides branch_id, ensure user has access to that branch
        if ($request->has('branch_id')) {
            $requestedBranchId = (int)$request->input('branch_id');
            // If user is Org Owner or Branch Manager for all org branches, check branch belongs to user's org
            if ($user->hasRole(['organization_owner'])) {
                $branchExistsInOrg = $user->organization->branches()->where('id', $requestedBranchId)->exists();
                if (!$branchExistsInOrg) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Requested branch does not belong to your organization.'
                    ], 403);
                }
            } elseif ($user->branch_id && (int)$user->branch_id !== $requestedBranchId) {
                // Check if user is explicitly assigned to this branch
                $hasAssignedBranch = $user->assignedBranches()->where('branches.id', $requestedBranchId)->exists();
                if (!$hasAssignedBranch) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have access to this branch.'
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}
