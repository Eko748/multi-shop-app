<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PengeluaranTipe extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pengeluaran_tipe';
    protected $guarded = [];

    public function pengeluaran()
    {
        return $this->hasMany(Pengeluaran::class, 'pengeluaran_tipe_id', 'id');
    }
}
