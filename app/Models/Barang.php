<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'barang';

    protected $guarded = [];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class,'brand_id', 'id');
    }
    public function jenis(): BelongsTo
    {
        return $this->belongsTo(JenisBarang::class, 'jenis_barang_id', 'id');
    }
    public function stockBarang(): HasMany
    {
        return $this->hasMany(StockBarang::class, 'barang_id', 'id');
    }

    public function level_harga()
    {
        return $this->hasMany(LevelHarga::class, 'id_barang', 'id');
    }

    public function promo()
    {
        return $this->hasMany(Promo::class, 'id_barang');
    }

    public function updateHargaLevel(array $data)
    {
        // data: [ ['nama' => 'Level 1', 'harga' => 3000], ... ]
        $this->update(['level_harga' => $data]);
    }

    public function getHargaByLevel($levelName)
    {
        return collect($this->level_harga)
            ->firstWhere('nama', $levelName)['harga'] ?? null;
    }
}
