<?php

namespace App\Models;

use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemNonFisikHarga extends Model
{
    use HasAuditTrail;

    protected $table = 'td_item_nonfisik_harga';

    protected $guarded = ['id'];

    public function item()
    {
        return $this->belongsTo(ItemNonFisik::class, 'item_nonfisik_id')
            ->select('id', 'nama', 'item_nonfisik_tipe_id');
    }

    public function dompetKategori()
    {
        return $this->belongsTo(DompetKategori::class, 'dompet_kategori_id')
            ->select('id', 'public_id', 'nama');
    }
}
