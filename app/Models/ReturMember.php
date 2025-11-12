<?php

namespace App\Models;

use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturMember extends Model
{
    use SoftDeletes;

    protected $table = 'retur_member';

    protected $guarded = [];

    protected $dates = ['tanggal'];

    public function detail()
    {
        return $this->hasMany(ReturMemberDetail::class, 'retur_id');
    }

    public function toko()
    {
        return $this->belongsTo(Toko::class, 'toko_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')
            ->select('id', 'nama', 'id_toko');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')
            ->select('id', 'nama', 'id_toko');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by')
            ->select('id', 'nama');
    }
}
