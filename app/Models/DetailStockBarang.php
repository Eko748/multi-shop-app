<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetailStockBarang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'detail_stock';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    protected $guarded = [];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id');
    }

    public function detailPembelian()
    {
        return $this->belongsTo(DetailPembelianBarang::class, 'id_detail_pembelian');
    }

    public function stok()
    {
        return $this->belongsTo(StockBarang::class, 'id_stock', 'id');
    }

    public function returDetails()
    {
        return $this->belongsToMany(ReturMemberDetail::class, 'retur_member_detail_stok', 'stok_detail_id', 'retur_member_detail_id')
            ->withPivot('qty')
            ->withTimestamps();
    }
}
