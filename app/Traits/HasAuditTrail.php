<?php

namespace App\Traits;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasAuditTrail
{
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->public_id)) {
                $model->public_id = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Carbon::parse($value)
                ->timezone('Asia/Jakarta')
                ->format('d-m-Y H:i:s'),
        );
    }

    protected function updatedAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Carbon::parse($value)
                ->timezone('Asia/Jakarta')
                ->format('d-m-Y H:i:s'),
        );
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by')
            ->select('id', 'nama', 'toko_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by')
            ->select('id', 'nama', 'toko_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by')
            ->select('id', 'nama');
    }
}
