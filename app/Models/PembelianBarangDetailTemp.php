<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembelianBarangDetailTemp extends Model
{
    protected $table = 'pembelian_barang_detail_temp';
    protected $guarded = [];

    public function pembelianBarang()
    {
        return $this->belongsTo(PembelianBarang::class, 'pembelian_barang_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}
