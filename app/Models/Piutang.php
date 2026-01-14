<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Piutang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'piutang';
    protected $guarded = [];
    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function toko()
    {
        return $this->belongsTo(Toko::class, 'toko_id', 'id');
    }

    public function piutangTipe()
    {
        return $this->belongsTo(PiutangTipe::class, 'piutang_tipe_id', 'id');
    }

    public function piutangDetail()
    {
        return $this->hasMany(PiutangDetail::class, 'piutang_id');
    }

    public function sumber(): MorphTo
    {
        return $this->morphTo();
    }

    public function jenisBarang()
    {
        return $this->belongsTo(JenisBarang::class, 'jenis_barang_id', 'id');
    }

    public function kas()
    {
        return $this->belongsTo(Kas::class, 'kas_id', 'id');
    }
}
