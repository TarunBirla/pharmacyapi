<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Organization::withCount(['branches', 'users']);

        if (!$user->hasRole('super_admin')) {
            $query->where('id', $user->organization_id);
        }

        $organizations = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => OrganizationResource::collection($organizations)->response()->getData(true),
        ]);
    }

    public function show(Organization $organization): JsonResponse
    {
        $organization->loadCount(['branches', 'users']);

        return response()->json([
            'success' => true,
            'data' => new OrganizationResource($organization),
        ]);
    }

    public function store(CreateOrganizationRequest $request): JsonResponse
    {
        $organization = Organization::create($request->validated());

        AuditLogService::log(
            action: 'organization.create',
            description: "Created organization {$organization->name} ({$organization->code})",
            entity: $organization,
            newValues: $organization->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Organization registered successfully',
            'data' => new OrganizationResource($organization),
        ], 201);
    }

    public function update(Request $request, Organization $organization): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'drug_license_number' => ['nullable', 'string', 'max:100'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'pan' => ['nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'string', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:25'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:50'],
            'settings' => ['nullable', 'array'],
        ]);

        $oldValues = $organization->toArray();
        $organization->update($validated);

        AuditLogService::log(
            action: 'organization.update',
            description: "Updated organization {$organization->name}",
            entity: $organization,
            oldValues: $oldValues,
            newValues: $organization->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Organization updated successfully',
            'data' => new OrganizationResource($organization),
        ]);
    }
}
