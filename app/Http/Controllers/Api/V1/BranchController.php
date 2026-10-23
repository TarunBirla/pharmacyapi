<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Branch::with('warehouses');

        if (!$user->hasRole('super_admin')) {
            $query->where('organization_id', $user->organization_id);
        }

        $branches = $query->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => BranchResource::collection($branches)->response()->getData(true),
        ]);
    }

    public function show(Branch $branch): JsonResponse
    {
        $branch->load('warehouses');

        return response()->json([
            'success' => true,
            'data' => new BranchResource($branch),
        ]);
    }

    public function store(CreateBranchRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (empty($data['organization_id'])) {
            $data['organization_id'] = $request->user()->organization_id;
        }

        $branch = Branch::create($data);

        // Automatically create a default retail dispensary warehouse for this branch
        $branch->warehouses()->create([
            'organization_id' => $branch->organization_id,
            'name' => 'Main Retail Counter',
            'code' => 'MAIN-01',
            'type' => 'retail_dispensary',
            'is_default' => true,
            'is_active' => true,
        ]);

        AuditLogService::log(
            action: 'branch.create',
            description: "Created branch {$branch->name} ({$branch->code})",
            entity: $branch,
            newValues: $branch->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully',
            'data' => new BranchResource($branch->load('warehouses')),
        ], 201);
    }

    public function update(Request $request, Branch $branch): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'business_type' => ['sometimes', 'in:retail,wholesale,both'],
            'dl_number_20b' => ['nullable', 'string', 'max:100'],
            'dl_number_21b' => ['nullable', 'string', 'max:100'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'invoice_prefix' => ['nullable', 'string', 'max:10'],
            'status' => ['sometimes', 'in:active,inactive'],
            'settings' => ['nullable', 'array'],
        ]);

        $oldValues = $branch->toArray();
        $branch->update($validated);

        AuditLogService::log(
            action: 'branch.update',
            description: "Updated branch {$branch->name}",
            entity: $branch,
            oldValues: $oldValues,
            newValues: $branch->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully',
            'data' => new BranchResource($branch->load('warehouses')),
        ]);
    }
}
