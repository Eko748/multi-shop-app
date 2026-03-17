<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Catatan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'catatan';
    protected $guarded = [];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function tokoAsal()
    {
        return $this->belongsTo(Toko::class, 'toko_asal_id', 'id');
    }

    public function tokoTujuan()
    {
        return $this->belongsTo(Toko::class, 'toko_tujuan_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function readBy()
    {
        return $this->belongsTo(User::class, 'read_by', 'id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }
}
