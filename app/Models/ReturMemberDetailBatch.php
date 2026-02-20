<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReturMemberDetailBatch extends Model
{
    use HasFactory;

    protected $table = 'retur_member_detail_batch';

    protected $fillable = [
        'retur_member_detail_id',
        'stock_barang_batch_id',
        'qty',
    ];

    /**
     * Relasi ke retur_member_detail.
     */
    public function returDetail()
    {
        return $this->belongsTo(ReturMemberDetail::class, 'retur_member_detail_id');
    }

    /**
     * Relasi ke stok detail.
     */
    public function stokDetail()
    {
        return $this->belongsTo(StockBarangBatch::class, 'stock_barang_batch_id');
    }
}
