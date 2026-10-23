<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiExpiryRiskAnalysis extends Model
{
    use HasFactory, BelongsToOrganization, BelongsToBranch;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'product_batch_id',
        'batch_number',
        'expiry_date',
        'days_to_expiry',
        'quantity_at_risk',
        'financial_exposure',
        'recommended_action',
        'discount_suggestion_percent',
        'action_rationale',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'quantity_at_risk' => 'decimal:4',
        'financial_exposure' => 'decimal:2',
        'discount_suggestion_percent' => 'decimal:2',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
