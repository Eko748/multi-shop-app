<?php

namespace App\Repositories;

use App\Models\Pemasukan;

class PemasukanRepository
{
    /**
     * Dapatkan total modal (id_jenis_pemasukan 1 & 2) untuk bulan dan tahun yang diberikan.
     */
    public function getModal(int $month, int $year): float
    {
        return (float) Pemasukan::whereIn('id_jenis_pemasukan', [1, 2])
            ->where(function ($query) use ($month, $year) {
                $query->whereYear('tanggal', '<', $year)
                    ->orWhere(function ($sub) use ($month, $year) {
                        $sub->whereYear('tanggal', '=', $year)
                            ->whereMonth('tanggal', '<=', $month);
                    });
            })
            ->sum('nilai');
    }
}
