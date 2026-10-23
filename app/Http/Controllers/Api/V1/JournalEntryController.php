<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = JournalEntry::where('organization_id', $user->organization_id)
            ->with(['lines.account']);

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('entry_date', [$request->query('from_date'), $request->query('to_date')]);
        }

        if ($request->has('source_type')) {
            $query->where('source_type', $request->query('source_type'));
        }

        $journals = $query->orderBy('id', 'desc')->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $journals,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'narration' => 'nullable|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.type' => 'required|in:debit,credit',
            'lines.*.amount' => 'required|numeric|gt:0',
            'lines.*.narration' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $fy = $this->accountingService->getOrCreateFinancialYear($user->organization_id, $validated['entry_date']);

        $journal = $this->accountingService->createJournal(
            organizationId: $user->organization_id,
            branchId: $user->branch_id,
            financialYearId: $fy->id,
            entryDate: $validated['entry_date'],
            sourceType: 'manual_journal',
            sourceId: null,
            referenceNumber: $validated['reference_number'] ?? null,
            narration: $validated['narration'] ?? 'Manual journal adjustment',
            lines: $validated['lines'],
            userId: $user->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Journal entry posted successfully',
            'data' => $journal,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $journal = JournalEntry::where('organization_id', $user->organization_id)
            ->with(['lines.account', 'creator', 'branch'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $journal,
        ]);
    }
}
