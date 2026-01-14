<?php

namespace App\Repositories;

use App\Models\Pengeluaran;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PengeluaranRepository
{
    public function getPengeluaranAset(int $month, int $year): array
    {
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $rawData = Pengeluaran::select(
            'aset',
            DB::raw('SUM(nominal) as total')
        )
            ->whereNotNull('aset')
            ->whereDate('tanggal', '<=', $endDate)
            ->groupBy('aset')
            ->pluck('total', 'aset')
            ->toArray();

        // 🧱 Default hasil (wajib ada)
        return [
            'kecil' => (float) ($rawData['kecil'] ?? 0),
            'besar' => (float) ($rawData['besar'] ?? 0),
        ];
    }
}
