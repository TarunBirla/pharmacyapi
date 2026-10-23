<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Account::where('organization_id', $user->organization_id);

        if ($request->has('account_type')) {
            $query->where('account_type', $request->query('account_type'));
        }

        if ($request->has('search')) {
            $term = $request->query('search');
            $query->where(function ($q) use ($term) {
                $q->where('account_code', 'like', "%{$term}%")
                    ->orWhere('account_name', 'like', "%{$term}%");
            });
        }

        $accounts = $query->orderBy('account_code')->get();

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_code' => 'required|string|max:30',
            'account_name' => 'required|string|max:150',
            'account_type' => 'required|in:asset,liability,equity,revenue,expense',
            'subtype' => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric',
            'description' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $account = Account::create([
            'organization_id' => $user->organization_id,
            'branch_id' => $user->branch_id,
            'account_code' => $validated['account_code'],
            'account_name' => $validated['account_name'],
            'account_type' => $validated['account_type'],
            'subtype' => $validated['subtype'] ?? 'general',
            'is_system' => false,
            'is_active' => true,
            'opening_balance' => $validated['opening_balance'] ?? 0.00,
            'current_balance' => $validated['opening_balance'] ?? 0.00,
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully',
            'data' => $account,
        ], 201);
    }

    public function statement(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $account = Account::where('organization_id', $user->organization_id)->findOrFail($id);

        $lines = $account->journalLines()
            ->with('journalEntry')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'account' => $account,
                'ledger_lines' => $lines,
            ]
        ]);
    }
}
