<?php

namespace App\Traits;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->branch_id) && auth()->check() && auth()->user()->branch_id) {
                $model->branch_id = auth()->user()->branch_id;
            }
        });

        static::addGlobalScope('branch', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                // Users with a specific branch assigned are scoped to their branch unless they have org-wide roles (like Org Owner)
                if ($user->branch_id && !$user->hasRole(['super_admin', 'organization_owner'])) {
                    $builder->where($builder->getModel()->getTable() . '.branch_id', $user->branch_id);
                }
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
