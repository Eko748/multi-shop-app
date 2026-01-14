<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HutangTipe extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hutang_tipe';
    protected $guarded = [];

    public function hutang()
    {
        return $this->hasMany(Hutang::class, 'hutang_tipe_id', 'id');
    }
}
