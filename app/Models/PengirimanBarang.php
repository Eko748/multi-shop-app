<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PengirimanBarang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pengiriman_barang';

    protected $guarded = [];

    public function tokoAsal()
    {
        return $this->belongsTo(Toko::class, 'toko_asal_id', 'id');
    }

    public function tokoTujuan()
    {
        return $this->belongsTo(Toko::class, 'toko_tujuan_id', 'id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'send_by');
    }

    public function verified()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function pengirimanBarangDetail()
    {
        return $this->hasMany(PengirimanBarangDetail::class, 'pengiriman_barang_id');
    }

    public function temp()
    {
        return $this->hasMany(PengirimanBarangDetailTemp::class, 'pengiriman_barang_id');
    }
}
