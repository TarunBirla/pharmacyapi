<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'name',
        'email',
        'phone',
        'pharmacist_reg_number',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function assignedBranches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_user');
    }

    public function hasRole(string|array $roles): bool
    {
        $roleList = is_array($roles) ? $roles : [$roles];

        // Check if relation already loaded
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(function ($role) use ($roleList) {
                return in_array($role->slug, $roleList, true);
            });
        }

        return $this->roles()->whereIn('slug', $roleList)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->with('permissions')->get();

        foreach ($roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function getAllPermissions(): array
    {
        if ($this->hasRole('super_admin')) {
            return Permission::pluck('slug')->toArray();
        }

        return $this->roles
            ->loadMissing('permissions')
            ->flatMap(fn ($role) => $role->permissions->pluck('slug'))
            ->unique()
            ->values()
            ->toArray();
    }
}
