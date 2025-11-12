<?php

namespace App\Repositories;

use App\Models\Pengeluaran;
use Illuminate\Support\Facades\DB;

class PengeluaranRepository
{
    public function getPengeluaranByMonthYear(int $month, int $year)
    {
        return Pengeluaran::select('is_asset', DB::raw('SUM(nilai) as total'))
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->groupBy('is_asset')
            ->pluck('total', 'is_asset');
    }
}
