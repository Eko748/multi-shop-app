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
        'tanggal' => 'datetime',
    ];

    public function tokoGroup()
    {
        return $this->belongsTo(TokoGroup::class, 'toko_group_id', 'id');
    }

    public function kas()
    {
        return $this->belongsTo(Kas::class, 'kas_id', 'id');
    }

    public function detail()
    {
        return $this->hasMany(PembelianBarangDetail::class, 'pembelian_barang_id');
    }

    public function temp()
    {
        return $this->hasMany(PembelianBarangDetailTemp::class, 'pembelian_barang_id');
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
