<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TransaksiKasir extends Model
{
    protected $table = 'transaksi_kasir';
    protected $guarded = [];
    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function details()
    {
        return $this->hasMany(TransaksiKasirDetail::class, 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->public_id)) {
                $model->public_id = (string) Str::uuid();
            }
        });
    }
}
