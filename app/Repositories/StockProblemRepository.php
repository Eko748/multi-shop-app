<?php

namespace App\Repositories;

use App\Models\StockBarangBermasalah;
use Illuminate\Support\Facades\DB;

class StockProblemRepository
{
    /**
     * Dapatkan ringkasan stok bermasalah (status hilang/mati).
     */
    public function getStockProblem(): array
    {
        $stokProblem = StockBarangBermasalah::select(
            'status',
            DB::raw('SUM(qty) as total_qty'),
            DB::raw('SUM(total_hpp) as total_hpp')
        )
        ->whereIn('status', ['hilang', 'mati'])
        ->groupBy('status')
        ->get()
        ->keyBy('status');

        return [
            'stock_hilang' => [
                'qty'       => $stokProblem['hilang']->total_qty ?? 0,
                'total_hpp' => $stokProblem['hilang']->total_hpp ?? 0,
            ],
            'stock_mati' => [
                'qty'       => $stokProblem['mati']->total_qty ?? 0,
                'total_hpp' => $stokProblem['mati']->total_hpp ?? 0,
            ],
        ];
    }
}
