<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class TransaksiKasirHarian extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'transaksi_kasir_harian';
    protected $guarded = [];
}
