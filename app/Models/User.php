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

    protected $guarded = [''];

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = "string";

    public $primaryKey = "id";

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function leveluser(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_level');
    }

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Toko::class, 'id_toko');
    }

    public function user(): HasMany
    {
        return $this->hasMany(User::class, 'id_user', 'id');
    }

    public function role()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'id_level');
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
