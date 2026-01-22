<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiKasirDetail extends Model
{
    protected $table = 'transaksi_kasir_detail';
    protected $guarded = [];

    public function transaksiKasir()
    {
        return $this->belongsTo(TransaksiKasir::class, 'transaksi_kasir_id', 'id');
    }

    public function stockBarangBatch()
    {
        return $this->belongsTo(StockBarangBatch::class, 'stock_barang_batch_id', 'id');
    }
}
