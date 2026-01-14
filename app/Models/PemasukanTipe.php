<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PemasukanTipe extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pemasukan_tipe';

    protected $guarded = [];

    public function pemasukan()
    {
        return $this->hasMany(Pemasukan::class, 'pemasukan_tipe_id', 'id');
    }
}
