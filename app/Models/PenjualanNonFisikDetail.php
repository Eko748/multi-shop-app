<?php

namespace App\Models;

use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PenjualanNonFisikDetail extends Model
{
    use HasAuditTrail;

    protected $table = 'td_penjualan_nonfisik_detail';

    protected $guarded = ['id'];

    public function penjualanNonfisik()
    {
        return $this->belongsTo(PenjualanNonFisik::class, 'penjualan_nonfisik_id')
            ->select('id', 'public_id');
    }

    public function item()
    {
        return $this->belongsTo(ItemNonFisik::class, 'item_nonfisik_id')
            ->select('id', 'nama', 'item_nonfisik_tipe_id');
    }
}
