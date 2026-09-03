<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockBarangBulanan extends Model
{
    protected $table = 'stock_barang_bulanan';

    protected $guarded = [];

    public function jenis()
    {
        return $this->belongsTo(JenisBarang::class, 'jenis_barang_id');
    }
}
