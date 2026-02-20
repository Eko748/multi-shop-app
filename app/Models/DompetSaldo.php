<?php

namespace App\Models;

use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DompetSaldo extends Model
{
    use SoftDeletes, HasAuditTrail;

    protected $table = 'td_dompet_saldo';

    protected $guarded = ['id'];

    public function dompetKategori()
    {
        return $this->belongsTo(DompetKategori::class, 'dompet_kategori_id')
            ->select('id', 'public_id', 'nama');
    }

    public function kas()
    {
        return $this->belongsTo(Kas::class);
    }
}
