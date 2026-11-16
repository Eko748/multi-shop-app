<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    use HasUuids;

    protected $table = 'stock_opnames';
    protected $guarded = ['id'];
    protected $keyType = 'string';
    public $incrementing = false;

    public function details()
    {
        return $this->hasMany(StockOpnameDetail::class, 'stock_opname_id');
    }

    public function toko()
    {
        return $this->belongsTo(Toko::class, 'toko_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
