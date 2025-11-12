<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturMemberDetail extends Model
{
    protected $table = 'retur_member_detail';

    protected $guarded = [];


    /**
     * Relasi ke retur_member (header).
     */
    public function retur()
    {
        return $this->belongsTo(ReturMember::class, 'retur_id');
    }

    /**
     * Relasi ke barang.
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    /**
     * Relasi ke detail kasir.
     */
    public function detailKasir()
    {
        return $this->belongsTo(DetailKasir::class, 'detail_kasir_id');
    }

    /**
     * Relasi ke stok detail melalui tabel pivot retur_member_detail_stok.
     */
    public function stokDetails()
    {
        return $this->belongsToMany(DetailStockBarang::class, 'retur_member_detail_stok', 'retur_member_detail_id', 'stok_detail_id')
            ->withPivot('qty')
            ->withTimestamps();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
