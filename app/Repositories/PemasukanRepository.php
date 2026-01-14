<?php

namespace App\Repositories;

use App\Models\Pemasukan;

class PemasukanRepository
{
    public function getModal(int $month, int $year, $tokoId = null): float
    {
        return (float) Pemasukan::query()->whereIn('pemasukan_tipe_id', [1, 2])
            ->when(
                $tokoId !== null && $tokoId !== 'all',
                fn($q) => $q->where('toko_id', $tokoId)
            )
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
