<?php

namespace App\Repositories;

use App\Models\StockBarangBermasalah;

class StockProblemRepository
{
    public function getStockProblem($month, $year, $toko_id = null): array
    {
        $stokProblem = StockBarangBermasalah::query()
            ->select('stock_barang_bermasalah.*', 'batch.harga_beli', 'batch.toko_id as batch_toko_id')
            ->join('stock_barang_batch as batch', 'batch.id', '=', 'stock_barang_bermasalah.stock_barang_batch_id')
            ->whereYear('stock_barang_bermasalah.created_at', $year)
            ->whereMonth('stock_barang_bermasalah.created_at', $month)
            ->whereIn('stock_barang_bermasalah.status', ['hilang', 'mati'])
            ->when(!empty($toko_id), function ($query) use ($toko_id) {
                // Sesuaikan kolom toko_id ini, apakah di tabel batch atau stock_barang_bermasalah
                $query->where('batch.toko_id', $toko_id);
            })
            ->get()
            ->groupBy('status')
            ->map(function ($group) {
                $totalQty = $group->sum('qty');

                $totalHpp = $group->sum(function ($item) {
                    $hpp = $item->harga_beli ?? 0;
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
