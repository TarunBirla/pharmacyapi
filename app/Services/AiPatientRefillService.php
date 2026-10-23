<?php

namespace App\Services;

use App\Models\PatientRefillReminder;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AiPatientRefillService
{
    /**
     * Scan patient purchases and forecast medication refill dates.
     */
    public function detectRefillOpportunities(int $organizationId, ?int $branchId = null): Collection
    {
        $query = SalesInvoice::with(['customer', 'items.product'])
            ->where('organization_id', $organizationId)
            ->where('status', 'posted')
            ->whereNotNull('customer_id');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $invoices = $query->latest('id')->limit(50)->get();
        $reminders = collect();

        foreach ($invoices as $inv) {
            foreach ($inv->items as $item) {
                $qty = (float)$item->quantity;
                if ($qty <= 0) continue;

                // Typical chronic daily dosage frequency (1 tab/day or 2 tabs/day)
                $dailyDose = 1.0;
                $daysSupply = (int)floor($qty / $dailyDose);
                $purchaseDate = Carbon::parse($inv->invoice_date);
                $predictedRefillDate = $purchaseDate->copy()->addDays($daysSupply)->toDateString();

                $reminder = PatientRefillReminder::firstOrCreate(
                    [
                        'organization_id' => $organizationId,
                        'branch_id' => $inv->branch_id,
                        'customer_id' => $inv->customer_id,
                        'product_id' => $item->product_id,
                        'purchase_date' => $inv->invoice_date->format('Y-m-d'),
                    ],
                    [
                        'patient_name' => $inv->customer->name,
                        'patient_phone' => $inv->customer->phone,
                        'sales_invoice_id' => $inv->id,
                        'quantity_supplied' => $qty,
                        'dosage_frequency_per_day' => $dailyDose,
                        'predicted_refill_date' => $predictedRefillDate,
                        'status' => 'pending',
                        'notes' => "Automatic chronic refill alert for {$item->product->name}. Expected exhaustion on {$predictedRefillDate}.",
                    ]
                );

                $reminders->push($reminder->load(['customer', 'product']));
            }
        }

        return $reminders;
    }

    /**
     * Update reminder status (e.g. customer contacted or refilled).
     */
    public function updateStatus(int $id, string $status, ?string $notes = null): PatientRefillReminder
    {
        $reminder = PatientRefillReminder::findOrFail($id);
        $reminder->status = $status;
        if ($notes) {
            $reminder->notes = $notes;
        }
        $reminder->save();

        return $reminder;
    }
}
