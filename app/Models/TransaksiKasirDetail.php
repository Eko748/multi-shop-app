<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransaksiKasirDetail extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'transaksi_kasir_detail';
    protected $guarded = [];

    public function transaksiKasir()
    {
        return $this->belongsTo(TransaksiKasir::class, 'id_kasir', 'id');
    }

    public function stockBarangBatch()
    {
        return $this->belongsTo(StockBarangBatch::class, 'stock_barang_batch_id', 'id');
    }

    // Relasi ke StockBarang lewat StockBarangBatch
    public function stockBarang()
    {
        return $this->hasOneThrough(
            StockBarang::class,
            StockBarangBatch::class,
            'id', // FK StockBarangBatch di TransaksiKasirDetail
            'id', // PK StockBarang
            'stock_barang_batch_id', // FK di TransaksiKasirDetail
            'stock_barang_id'       // FK di StockBarangBatch ke StockBarang
        );
    }

    // Relasi ke Barang lewat StockBarang
    public function barang()
    {
        return $this->hasOneThrough(
            Barang::class,
            StockBarang::class,
            'id', // FK StockBarang di StockBarangBatch
            'id', // PK Barang
            'stock_barang_batch_id', // FK di TransaksiKasirDetail
            'id_barang'              // FK StockBarang ke Barang
        );
    }
}
