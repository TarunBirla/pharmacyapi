<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    use HasFactory, BelongsToOrganization, BelongsToBranch;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'warehouse_id',
        'supplier_id',
        'purchase_order_id',
        'grn_number',
        'receipt_date',
        'supplier_delivery_challan_no',
        'carrier_name',
        'lr_number',
        'status',
        'remarks',
        'received_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
