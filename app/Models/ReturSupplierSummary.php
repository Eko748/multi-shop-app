<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturSupplierSummary extends Model
{
    use SoftDeletes;

    protected $table = 'retur_supplier_summary';

    protected $guarded = [];

    protected $dates = ['tanggal'];

    public function suppliers()
    {
        return $this->hasMany(ReturSupplier::class, 'summary_id');
    }
}
