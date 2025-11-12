<?php

namespace App\Models;

use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemNonFisik extends Model
{
    use SoftDeletes, HasAuditTrail;

    protected $table = 'td_item_nonfisik';

    protected $guarded = ['id'];

    public function tipe()
    {
        return $this->belongsTo(ItemNonFisikTipe::class, 'item_nonfisik_tipe_id')
            ->select('id', 'nama');
    }

    public function harga()
    {
        return $this->hasMany(ItemNonFisikHarga::class, 'item_nonfisik_id')
            ->select('id', 'public_id', 'hpp', 'harga_jual', 'item_nonfisik_id', 'dompet_kategori_id')->with('dompetKategori');
    }
}
