<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembelianBarangDetail extends Model
{
    protected $table = 'pembelian_barang_detail';
    protected $guarded = [];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function pembelianBarang()
    {
        return $this->belongsTo(PembelianBarang::class, 'pembelian_barang_id');
    }
}
