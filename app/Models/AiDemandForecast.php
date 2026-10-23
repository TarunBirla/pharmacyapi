<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiDemandForecast extends Model
{
    use HasFactory, BelongsToOrganization, BelongsToBranch;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'product_id',
        'forecast_date',
        'current_stock',
        'sales_velocity_daily',
        'estimated_days_stock_remaining',
        'recommended_reorder_quantity',
        'confidence_score',
        'stockout_risk_level',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'current_stock' => 'decimal:4',
        'sales_velocity_daily' => 'decimal:4',
        'recommended_reorder_quantity' => 'decimal:4',
        'confidence_score' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
