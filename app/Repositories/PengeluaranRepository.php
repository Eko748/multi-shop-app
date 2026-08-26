<?php

namespace App\Repositories;

use App\Models\Pengeluaran;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PengeluaranRepository
{
    /**
     * Mengambil rekap pengeluaran aset (kecil & besar)
     *
     * @param int $month
     * @param int $year
     * @param mixed $tokoId ID Toko (jika null/kosong, data dari semua toko akan ditarik)
     * @return array
     */
    public function getPengeluaranAset(int $month, int $year, $tokoId = null): array
    {
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $rawData = Pengeluaran::select(
            'aset',
            DB::raw('SUM(nominal) as total')
        )
            ->whereNotNull('aset')
            ->whereDate('tanggal', '<=', $endDate)
            // Filter toko_id HANYA JIKA $tokoId tidak kosong
            ->when(!empty($tokoId), function ($query) use ($tokoId) {
                $query->where('toko_id', $tokoId);
            })
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
