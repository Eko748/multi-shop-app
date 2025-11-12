<?php

namespace App\Models;

use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DompetKategori extends Model
{
    use SoftDeletes, HasAuditTrail;

    protected $table = 'td_dompet_kategori';

    protected $guarded = ['id'];
}
