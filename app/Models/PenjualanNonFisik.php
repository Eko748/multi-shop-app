<?php

namespace App\Models;

use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PenjualanNonFisik extends Model
{
    use SoftDeletes, HasAuditTrail;

    protected $table = 'td_penjualan_nonfisik';

    protected $guarded = ['id'];

    public function detail()
    {
        return $this->hasMany(PenjualanNonFisikDetail::class, 'penjualan_nonfisik_id')
            ->select('id', 'public_id', 'penjualan_nonfisik_id', 'hpp', 'harga_jual', 'qty');
    }

    public function dompetKategori()
    {
        return $this->belongsTo(DompetKategori::class, 'dompet_kategori_id')
            ->select('id', 'public_id', 'nama');
    }
}
