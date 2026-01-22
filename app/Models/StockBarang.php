<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockBarang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'stock_barang';

    protected $guarded = [];

    protected $casts = [
        'level_harga' => 'array',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id', 'id');
    }

    public function tokoGroup()
    {
        return $this->belongsTo(TokoGroup::class, 'toko_group_id', 'id');
    }
}
