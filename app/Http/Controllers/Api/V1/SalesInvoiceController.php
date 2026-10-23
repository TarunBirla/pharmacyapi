<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesInvoiceResource;
use App\Models\SalesInvoice;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesInvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SalesInvoice::with(['customer', 'items.product', 'payments'])->latest('id');

        if ($request->filled('invoice_type')) {
            $query->where('invoice_type', $request->invoice_type);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%{$s}%")
                  ->orWhere('patient_name', 'like', "%{$s}%")
                  ->orWhere('doctor_name', 'like', "%{$s}%");
            });
        }

        $invoices = $query->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => SalesInvoiceResource::collection($invoices)->response()->getData(true),
        ]);
    }

    public function show(SalesInvoice $salesInvoice): JsonResponse
    {
        $salesInvoice->load(['customer', 'doctor', 'prescription', 'items.product', 'items.batch', 'payments']);

        return response()->json([
            'success' => true,
            'data' => new SalesInvoiceResource($salesInvoice),
        ]);
    }
}
