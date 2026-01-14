<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KasTransaksi extends Model
{
    use SoftDeletes;

    protected $table = 'kas_transaksi';
    protected $guarded = [];

    public function kas()
    {
        return $this->belongsTo(Kas::class);
    }

    public function sumber(): MorphTo
    {
        return $this->morphTo();
    }
}
