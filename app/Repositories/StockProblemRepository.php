<?php

namespace App\Repositories;

use App\Models\StockBarangBermasalah;
use Illuminate\Support\Facades\DB;

class StockProblemRepository
{
    public function getStockProblem($toko_id): array
    {
        $stokProblem = StockBarangBermasalah::where('toko_id', $toko_id)->with('batch')
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
                    'qty'       => $totalQty,
                    'total_hpp' => $totalHpp,
                ];
            });

        return [
            'stock_hilang' => $stokProblem['hilang'] ?? ['qty' => 0, 'total_hpp' => 0],
            'stock_mati'   => $stokProblem['mati']   ?? ['qty' => 0, 'total_hpp' => 0],
        ];
    }
}
