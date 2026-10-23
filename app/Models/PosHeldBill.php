<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosHeldBill extends Model
{
    use HasFactory, BelongsToOrganization, BelongsToBranch;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'user_id',
        'reference_code',
        'customer_id',
        'cart_data',
        'total_amount',
        'held_at',
    ];

    protected $casts = [
        'cart_data' => 'array',
        'total_amount' => 'decimal:2',
        'held_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
