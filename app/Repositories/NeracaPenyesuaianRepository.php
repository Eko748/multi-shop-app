<?php

namespace App\Repositories;

use App\Models\NeracaPenyesuaian;

class NeracaPenyesuaianRepository
{
    /**
     * Dapatkan total nilai penyesuaian neraca.
     *
     * @param int|string $month
     * @param int|string $year
     * @param mixed $tokoId ID Toko (jika null/kosong, data dari semua toko akan ditarik)
     * @return float
     */
    public function getTotalPenyesuaian($month, $year, $tokoId = null): float
    {
        return (float) NeracaPenyesuaian::whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            // Filter toko_id HANYA JIKA $tokoId tidak kosong
            ->when(!empty($tokoId), function ($query) use ($tokoId) {
                $query->where('toko_id', $tokoId);
            })
            ->sum('nominal');
    }
}
