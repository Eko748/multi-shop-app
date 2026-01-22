<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TokoGroup extends Model
{
    use SoftDeletes;

    protected $table = 'toko_group';

    protected $fillable = [
        'parent_toko_id',
        'nama',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    public function toko()
    {
        return $this->belongsToMany(
            Toko::class,
            'toko_group_item',
            'toko_group_id',
            'toko_id'
        )->withTimestamps();
    }
}
