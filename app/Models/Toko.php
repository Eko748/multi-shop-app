<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Toko extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'toko';

    protected $guarded = [''];

    protected $casts = [
        'kasbon' => 'boolean',
    ];

    public function groups()
    {
        return $this->belongsToMany(
            TokoGroup::class,
            'toko_group_items',
            'toko_id',
            'toko_group_id'
        )->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_toko', 'id');
    }

    public function pengirimanSebagaiPengirim()
    {
        return $this->hasMany(PengirimanBarang::class, 'toko_pengirim', 'id')->with('tokos');
    }

    public function pengirimanSebagaiPenerima()
    {
        return $this->hasMany(PengirimanBarang::class, 'toko_penerima', 'id');
    }

    public function stok()
    {
        return $this->hasMany(StockBarang::class, 'toko_pengirim', 'id');
    }

    public function mutasipengirim()
    {
        return $this->hasMany(KasMutasi::class, 'id_toko_pengirim', 'id');
    }

    public function mutasipenerima()
    {
        return $this->hasMany(KasMutasi::class, 'id_toko_penerima', 'id');
    }

    public function levelHarga()
    {
        return $this->belongsTo(LevelHarga::class, 'id_level_harga', 'id');
    }

    public function kasir()
    {
        return $this->hasMany(TransaksiKasir::class, 'id_toko', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(Toko::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Toko::class, 'parent_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function stockBarangs()
    {
        return $this->hasMany(StockBarang::class);
    }

    public function kas()
    {
        return $this->hasMany(Kas::class);
    }
}
