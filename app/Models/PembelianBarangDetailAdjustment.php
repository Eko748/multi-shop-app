<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembelianBarangDetailAdjustment extends Model
{
    protected $table = 'pembelian_barang_detail_adjustment';

    protected $guarded = [];

    public function pembelian()
    {
        return $this->belongsTo(PembelianBarang::class, 'pembelian_barang_id');
    }

    public function detail()
    {
        return $this->belongsTo(PembelianBarangDetail::class, 'pembelian_barang_detail_id');
    }

    public function batch()
    {
        return $this->belongsTo(StockBarangBatch::class, 'stock_barang_batch_id');
    }
}
