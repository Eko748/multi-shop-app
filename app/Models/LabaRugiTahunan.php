<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabaRugiTahunan extends Model
{
    use HasFactory;

    protected $table = 'laba_rugi_tahunan';

    protected $fillable = [
        'toko_id',
        'tahun',
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
