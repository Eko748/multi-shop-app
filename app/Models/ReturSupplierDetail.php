<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturSupplierDetail extends Model
{
    protected $table = 'retur_supplier_detail';

    protected $guarded = [];

    public function returSupplier()
    {
        return $this->belongsTo(ReturSupplier::class, 'retur_supplier_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}
