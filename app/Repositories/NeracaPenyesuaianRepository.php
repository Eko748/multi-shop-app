<?php

namespace App\Repositories;

use App\Models\NeracaPenyesuaian;

class NeracaPenyesuaianRepository
{
    /**
     * Dapatkan total nilai penyesuaian neraca.
     */
    public function getTotalPenyesuaian($month, $year, $tokoId): float
    {
        return (float) NeracaPenyesuaian::where('toko_id', $tokoId)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->sum('nominal');
    }
}
