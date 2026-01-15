<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiKasir extends Model
{
    protected $table = 'transaksi_kasir';
    protected $guarded = [];
    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
