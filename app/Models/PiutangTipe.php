<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PiutangTipe extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'piutang_tipe';
    protected $guarded = [];

    public function piutang()
    {
        return $this->hasMany(Piutang::class, 'piutang_tipe_id', 'id');
    }
}
