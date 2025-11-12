<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockBarangBermasalah extends Model
{
    use HasFactory;

    protected $table = 'stock_barang_bermasalah';

    protected $guarded = [];

    public function stockBarang()
    {
        return $this->belongsTo(StockBarang::class, 'stock_barang_id', 'id');
    }
}
