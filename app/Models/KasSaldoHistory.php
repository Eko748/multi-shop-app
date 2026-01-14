<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasSaldoHistory extends Model
{
    protected $table = 'kas_saldo_history';
    protected $guarded = [];

    public function kas()
    {
        return $this->belongsTo(Kas::class);
    }
}
