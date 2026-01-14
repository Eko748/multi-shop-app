<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HutangDetail extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'hutang_detail';
    protected $guarded = [];

    public function hutang(): BelongsTo
    {
        return $this->belongsTo(Hutang::class, 'hutang_id', 'id');
    }
}
