<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NeracaPenyesuaian extends Model
{
    use HasFactory;

    protected $table = 'neraca_penyesuaian';

    protected $fillable = [
        'nilai',
        'tanggal',
        'created_by',
        'updated_by',
    ];

    public function getCreatorNameAttribute()
    {
        return $this->creator ? $this->creator->name : null;
    }

    public function getUpdaterNameAttribute()
    {
        return $this->updater ? $this->updater->name : null;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
