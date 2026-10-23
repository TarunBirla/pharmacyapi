<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'business_type',
        'dl_number_20b',
        'dl_number_21b',
        'gstin',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'postal_code',
        'invoice_prefix',
        'next_invoice_number',
        'is_main_branch',
        'status',
        'settings',
    ];

    protected $casts = [
        'is_main_branch' => 'boolean',
        'settings' => 'array',
        'next_invoice_number' => 'integer',
    ];

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_user');
    }
}
