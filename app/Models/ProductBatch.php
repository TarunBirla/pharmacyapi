<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToOrganization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBatch extends Model
{
    use HasFactory, BelongsToOrganization, BelongsToBranch;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'warehouse_id',
        'product_id',
        'batch_number',
        'manufacturing_date',
        'expiry_date',
        'mrp',
        'purchase_rate',
        'sale_price',
        'wholesale_price',
        'quantity_received',
        'quantity_available',
        'quantity_reserved',
        'quantity_quarantined',
        'quantity_damaged',
        'quantity_expired',
        'status',
        'supplier_id',
    ];

    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'mrp' => 'decimal:2',
        'purchase_rate' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'quantity_received' => 'decimal:4',
        'quantity_available' => 'decimal:4',
        'quantity_reserved' => 'decimal:4',
        'quantity_quarantined' => 'decimal:4',
        'quantity_damaged' => 'decimal:4',
        'quantity_expired' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Strict FEFO (First Expiry, First Out) ordering.
     */
    public function scopeFefo(Builder $query): Builder
    {
        return $query->orderBy('expiry_date', 'asc')->orderBy('id', 'asc');
    }

    /**
     * Scope for saleable batches: non-expired, active status, stock > 0.
     */
    public function scopeEligibleForSale(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('quantity_available', '>', 0)
            ->where('expiry_date', '>', now()->toDateString())
            ->fefo();
    }

    /**
     * Scope for batches expiring within N days.
     */
    public function scopeExpiringWithinDays(Builder $query, int $days): Builder
    {
        $today = now()->toDateString();
        $targetDate = now()->addDays($days)->toDateString();

        return $query->where('status', '!=', 'expired')
            ->where('quantity_available', '>', 0)
            ->whereBetween('expiry_date', [$today, $targetDate])
            ->orderBy('expiry_date', 'asc');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }

    public function daysUntilExpiry(): int
    {
        return (int)now()->startOfDay()->diffInDays($this->expiry_date, false);
    }
}
