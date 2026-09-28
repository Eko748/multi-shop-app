<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanOrder extends Model
{
    use HasFactory;

    // Nama tabel diset eksplisit tanpa s
    protected $table = 'plan_order';

    protected $fillable = [
        'toko_id',
        'kode_plan',
        'grand_total',
        'items',
    ];

    protected $casts = [
        'items' => 'array',
        'grand_total' => 'decimal:2',
    ];

    /**
     * Relasi ke Model Toko
     */
    public function toko()
    {
        return $this->belongsTo(Toko::class, 'toko_id');
    }

    /**
     * Helper / Accessor untuk mengambil Instance Model Barang
     * dari array barang_id yang ada di dalam JSON items
     */
    public function getBarangItemsAttribute()
    {
        if (empty($this->items)) {
            return collect();
        }

        // Ambil semua barang_id dari kolom JSON items
        $barangIds = collect($this->items)->pluck('barang_id')->filter()->unique();

        // Query ke Model Barang (tabel barang)
        return Barang::whereIn('id', $barangIds)->get();
    }
}
