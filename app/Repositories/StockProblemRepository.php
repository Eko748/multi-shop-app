<?php

namespace App\Repositories;

use App\Models\StockBarangBermasalah;

class StockProblemRepository
{
    /**
     * Dapatkan rekap stok barang bermasalah (hilang & mati).
     *
     * @param int|string $month
     * @param int|string $year
     * @param mixed $toko_id ID Toko (jika null/kosong, data dari semua toko akan ditarik)
     * @return array
     */
    public function getStockProblem($month, $year, $toko_id = null): array
    {
        $stokProblem = StockBarangBermasalah::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            // Filter toko_id HANYA JIKA $toko_id tidak kosong
            ->when(!empty($toko_id), function ($query) use ($toko_id) {
                $query->where('toko_id', $toko_id);
            })
            ->with('batch')
            ->whereIn('status', ['hilang', 'mati'])
            ->get()
            ->groupBy('status')
            ->map(function ($group) {

                $totalQty = $group->sum('qty');

                $totalHpp = $group->sum(function ($item) {
                    $hpp = $item->batch->harga_beli ?? 0;

                    return $item->qty * $hpp;
                });

                return [
                    'qty' => $totalQty,
                    'total_hpp' => $totalHpp,
                ];
            });

        return [
            'stock_hilang' => $stokProblem['hilang'] ?? ['qty' => 0, 'total_hpp' => 0],
            'stock_mati' => $stokProblem['mati'] ?? ['qty' => 0, 'total_hpp' => 0],
        ];
    }
}
