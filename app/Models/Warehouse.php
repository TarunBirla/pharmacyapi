<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Warehouse extends Model
{
    use HasFactory, BelongsToOrganization, BelongsToBranch;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'name',
        'code',
        'type',
        'is_default',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
