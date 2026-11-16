<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StockOpnameDetail extends Model
{
    use HasUuids;

    protected $table = 'stock_opname_details';
    protected $guarded = ['id'];
    protected $keyType = 'string';
    public $incrementing = false;

    public function stockOpname()
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }
}
