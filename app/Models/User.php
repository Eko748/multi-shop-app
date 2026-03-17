<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function catatanBelumDibaca()
    {
        return $this->hasMany(Catatan::class, 'toko_tujuan_id', 'toko_id')
            ->where('is_read', 0);
    }

    public function leveluser(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Toko::class, 'toko_id');
    }

    public function user(): HasMany
    {
        return $this->hasMany(User::class, 'id_user', 'id');
    }

    public function role()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'role_id');
    }

    public function getAllPermissionsAttribute()
    {
        return $this->role ? $this->role->permissions : collect([]);
    }

    public function hasPermission($permissionName)
    {
        return $this->getAllPermissionsAttribute()->contains('name', $permissionName);
    }

    public function hasPermissionTo($permission)
    {
        return $this->role && $this->role->permissions->contains('name', $permission);
    }
}
