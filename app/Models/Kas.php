<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kas extends Model
{
    protected $table = 'kas';
    protected $guarded = [];

    public function toko()
    {
        return $this->belongsTo(Toko::class);
    }

    public function jenisBarang()
    {
        return $this->belongsTo(JenisBarang::class, 'jenis_barang_id');
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(KasTransaksi::class);
    }

    public function mutasiKeluar()
    {
        return $this->hasMany(KasMutasi::class, 'kas_asal_id');
    }

    public function mutasiMasuk()
    {
        return $this->hasMany(KasMutasi::class, 'kas_tujuan_id');
    }
}
