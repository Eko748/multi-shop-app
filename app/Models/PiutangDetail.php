<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PiutangDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'piutang_detail';
    protected $guarded = [];

    public function piutang()
    {
        return $this->belongsTo(Toko::class, 'piutang_id', 'id');
    }
}
