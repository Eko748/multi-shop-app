<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokoGroupItem extends Model
{
    protected $table = 'toko_group_item';

    protected $fillable = [
        'toko_group_id',
        'toko_id',
    ];

    public function group()
    {
        return $this->belongsTo(TokoGroup::class, 'toko_group_id');
    }

    public function toko()
    {
        return $this->belongsTo(Toko::class, 'toko_id');
    }
}
