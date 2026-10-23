<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%")
                  ->orWhere('gstin', 'like', "%{$s}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $customers = $query->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => CustomerResource::collection($customers)->response()->getData(true),
        ]);
    }

    public function show(Customer $customer): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new CustomerResource($customer),
        ]);
    }

    public function store(CreateCustomerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['organization_id'] = $request->user()->organization_id;

        $customer = Customer::create($data);

        AuditLogService::log(
            action: 'customer.create',
            description: "Registered customer {$customer->name} ({$customer->code})",
            entity: $customer
        );

        return response()->json([
            'success' => true,
            'message' => 'Customer registered successfully',
            'data' => new CustomerResource($customer),
        ], 201);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:retail,wholesale_dealer,hospital,institutional'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'credit_limit' => ['sometimes', 'numeric', 'min:0'],
            'credit_period_days' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:active,inactive,credit_hold'],
            'address' => ['nullable', 'string'],
        ]);

        $customer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer profile updated successfully',
            'data' => new CustomerResource($customer),
        ]);
    }

    public function ledger(Request $request, Customer $customer): JsonResponse
    {
        $query = CustomerLedgerEntry::where('customer_id', $customer->id)->latest('id');

        $entries = $query->paginate($request->input('per_page', 30));

        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'current_balance' => (float)$customer->current_balance,
                'credit_limit' => (float)$customer->credit_limit,
            ],
            'data' => $entries,
        ]);
    }
}
