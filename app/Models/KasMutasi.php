<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasMutasi extends Model
{
    protected $table = 'kas_mutasi';

    protected $fillable = [
        'kas_asal_id',
        'kas_tujuan_id',
        'nominal',
        'keterangan',
        'tanggal',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function kasAsal()
    {
        return $this->belongsTo(Kas::class, 'kas_asal_id');
    }

    public function kasTujuan()
    {
        return $this->belongsTo(Kas::class, 'kas_tujuan_id');
    }
}
