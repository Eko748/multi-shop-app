<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabaRugi extends Model
{
    use HasFactory;

    protected $table = 'laba_rugi';

    protected $fillable = [
        'toko_id',
        'tahun',
        'bulan',
        'pendapatan',
        'beban',
        'laba_bersih',
    ];

    protected $casts = [
        'pendapatan' => 'float',
        'beban' => 'float',
        'laba_bersih' => 'float',
    ];

    public function toko()
    {
        return $this->belongsTo(Toko::class);
    }
}
