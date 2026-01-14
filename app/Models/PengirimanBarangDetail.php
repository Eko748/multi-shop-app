<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengirimanBarangDetail extends Model
{
    protected $table = 'pengiriman_barang_detail';

    protected $guarded = [];

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
