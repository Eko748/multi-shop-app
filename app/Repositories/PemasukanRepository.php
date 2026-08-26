<?php

namespace App\Repositories;

use App\Models\Pemasukan;

class PemasukanRepository
{
    /**
     * Dapatkan total nominal modal berdasarkan periode.
     *
     * @param int $month
     * @param int $year
     * @param mixed $tokoId ID Toko (jika null/kosong/ALL, akumulasi seluruh toko akan dihitung)
     * @return float
     */
    public function getModal(int $month, int $year, $tokoId = null): float
    {
        return (float) Pemasukan::query()
            ->whereIn('pemasukan_tipe_id', [1, 2])
            // Filter toko_id HANYA JIKA $tokoId tidak kosong dan bukan string 'all'
            ->when(!empty($tokoId) && strtolower((string)$tokoId) !== 'all', function ($q) use ($tokoId) {
                $q->where('toko_id', $tokoId);
            })
            ->where(function ($query) use ($month, $year) {
                $query->whereYear('tanggal', '<', $year)
                    ->orWhere(function ($sub) use ($month, $year) {
                        $sub->whereYear('tanggal', '=', $year)
                            ->whereMonth('tanggal', '<=', $month);
                    });
            })
            ->sum('nominal');
    }
}
