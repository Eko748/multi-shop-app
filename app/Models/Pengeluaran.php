<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pengeluaran extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pengeluaran';
    protected $guarded = [];
    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function pengeluaranTipe(): BelongsTo
    {
        return $this->belongsTo(PengeluaranTipe::class, 'pengeluaran_tipe_id', 'id');
    }

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Toko::class, 'toko_id', 'id');
    }

    public function kas(): BelongsTo
    {
        return $this->belongsTo(Kas::class, 'kas_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
