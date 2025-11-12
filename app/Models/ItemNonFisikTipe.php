<?php

namespace App\Models;

use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemNonFisikTipe extends Model
{
    use SoftDeletes, HasAuditTrail;

    protected $table = 'td_item_nonfisik_tipe';

    protected $guarded = ['id'];
}
