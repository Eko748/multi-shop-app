<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengirimanBarangDetailTemp extends Model
{
    protected $table = 'pengiriman_barang_detail_temp';

    protected $guarded = [];

    protected $casts = [
        'level_harga' => 'array',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function pengirimanBarang()
    {
        return $this->belongsTo(PengirimanBarang::class);
    }

    public function batch()
    {
        return $this->belongsTo(StockBarangBatch::class, 'stock_barang_batch_id');
    }
}
