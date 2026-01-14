<?php

namespace App\Models;

use App\Http\Controllers\BarangController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PembelianBarang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pembelian_barang';

    protected $guarded = [];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function detail()
    {
        return $this->hasMany(PembelianBarangDetail::class, 'pembelian_barang_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function jenisBarang()
    {
        return $this->belongsTo(JenisBarang::class, 'jenis_barang_id');
    }

    public function level_harga()
    {
        return $this->belongsTo(LevelHarga::class, 'id_level_harga');
    }
}
