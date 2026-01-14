<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hutang extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'hutang';
    protected $guarded = [''];
    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function toko()
    {
        return $this->belongsTo(Toko::class, 'toko_id', 'id');
    }

    public function hutangTipe()
    {
        return $this->belongsTo(HutangTipe::class, 'hutang_tipe_id', 'id');
    }

    public function hutangDetail()
    {
        return $this->hasMany(HutangDetail::class, 'hutang_id');
    }

    public function sumber(): MorphTo
    {
        return $this->morphTo();
    }

    public function kas()
    {
        return $this->belongsTo(Kas::class, 'kas_id', 'id');
    }
}
