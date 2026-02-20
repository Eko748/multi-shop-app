<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockBarangBatch extends Model
{
    protected $table = 'stock_barang_batch';

    protected $guarded = [];

    public function stockBarang()
    {
        return $this->belongsTo(StockBarang::class, 'stock_barang_id', 'id');
    }

    public function toko()
    {
        return $this->belongsTo(Toko::class, 'toko_id', 'id');
    }

    public function pembelianBarangDetail()
    {
        return $this->belongsTo(PembelianBarangDetail::class, 'pembelian_barang_detail_id', 'id');
    }

    public function sumber()
    {
        return $this->morphTo();
    }
}
