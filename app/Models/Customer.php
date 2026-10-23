<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'type',
        'phone',
        'email',
        'gstin',
        'pan',
        'dl_number',
        'credit_limit',
        'credit_period_days',
        'current_balance',
        'loyalty_points',
        'status',
        'address',
        'city',
        'state',
        'postal_code',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'credit_period_days' => 'integer',
        'loyalty_points' => 'integer',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CustomerLedgerEntry::class)->orderBy('entry_date', 'asc')->orderBy('id', 'asc');
    }

    public function isCreditAllowed(float $additionalAmount = 0): bool
    {
        if ($this->status === 'credit_hold' || $this->status === 'inactive') {
            return false;
        }

        if ((float)$this->credit_limit <= 0) {
            return false;
        }

        return ((float)$this->current_balance + $additionalAmount) <= (float)$this->credit_limit;
    }
}
