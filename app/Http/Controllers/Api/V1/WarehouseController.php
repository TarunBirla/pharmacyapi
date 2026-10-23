<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Warehouse::query();

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $warehouses = $query->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => WarehouseResource::collection($warehouses)->response()->getData(true),
        ]);
    }

    public function show(Warehouse $warehouse): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new WarehouseResource($warehouse),
        ]);
    }

    public function store(CreateWarehouseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['organization_id'] = $request->user()->organization_id;

        $warehouse = Warehouse::create($data);

        AuditLogService::log(
            action: 'warehouse.create',
            description: "Created warehouse {$warehouse->name} ({$warehouse->code})",
            entity: $warehouse,
            newValues: $warehouse->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Warehouse created successfully',
            'data' => new WarehouseResource($warehouse),
        ], 201);
    }

    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:retail_dispensary,main_godown,cold_storage,quarantine,damaged'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $oldValues = $warehouse->toArray();
        $warehouse->update($validated);

        AuditLogService::log(
            action: 'warehouse.update',
            description: "Updated warehouse {$warehouse->name}",
            entity: $warehouse,
            oldValues: $oldValues,
            newValues: $warehouse->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Warehouse updated successfully',
            'data' => new WarehouseResource($warehouse),
        ]);
    }
}
