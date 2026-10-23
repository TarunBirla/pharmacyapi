<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientRefillReminder extends Model
{
    use HasFactory, BelongsToOrganization, BelongsToBranch;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'customer_id',
        'patient_name',
        'patient_phone',
        'sales_invoice_id',
        'product_id',
        'purchase_date',
        'quantity_supplied',
        'dosage_frequency_per_day',
        'predicted_refill_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'predicted_refill_date' => 'date',
        'quantity_supplied' => 'decimal:2',
        'dosage_frequency_per_day' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }
}
