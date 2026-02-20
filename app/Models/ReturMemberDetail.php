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
    public function transaksiKasirDetail()
    {
        return $this->belongsTo(TransaksiKasirDetail::class, 'transaksi_kasir_detail_id');
    }

    public function batch()
    {
        return $this->hasMany(ReturMemberDetailBatch::class);
    }


    /**
     * Relasi ke stok detail melalui tabel pivot retur_member_detail_stok.
     */
    public function stokDetails()
    {
        return $this->belongsToMany(StockBarangBatch::class, 'retur_member_detail_batch', 'retur_member_detail_id', 'stock_barang_batch_id')
            ->withPivot('qty')
            ->withTimestamps();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
