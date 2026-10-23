<?php

namespace App\Traits;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->organization_id) && auth()->check() && auth()->user()->organization_id) {
                $model->organization_id = auth()->user()->organization_id;
            }
        });

        static::addGlobalScope('organization', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                $table = $builder->getModel()->getTable();

                // If user is not super_admin and belongs to an organization
                if ($user->organization_id) {
                    if ($table === 'roles') {
                        // Allow both org-specific roles and system-wide default roles
                        $builder->where(function ($q) use ($table, $user) {
                            $q->where($table . '.organization_id', $user->organization_id)
                              ->orWhereNull($table . '.organization_id');
                        });
                    } else {
                        $builder->where($table . '.organization_id', $user->organization_id);
                    }
                }
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
