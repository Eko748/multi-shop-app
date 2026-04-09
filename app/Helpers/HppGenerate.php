<?php

namespace App\Helpers;

use App\Models\{StockBarang, StockBarangBatch};

class HppGenerate
{
    public static function recalcHpp($stockBarangId)
    {
        $stockBarang = StockBarang::where('id', $stockBarangId)
            ->lockForUpdate()
            ->first();

        if (!$stockBarang) return;

        $batches = StockBarangBatch::where('stock_barang_id', $stockBarangId)->get();

        $totalQty = 0;
        $totalValue = 0;

        foreach ($batches as $b) {
            $totalQty += $b->qty_sisa;

            // ❗ PAKAI HARGA BELI ASLI
            $totalValue += $b->qty_sisa * $b->harga_beli;
        }

        if ($totalQty > 0) {
            $stockBarang->hpp_awal = $stockBarang->hpp_baru;
            $stockBarang->hpp_baru = $totalValue / $totalQty;
        } else {
            $stockBarang->hpp_awal = 0;
            $stockBarang->hpp_baru = 0;
        }

        $stockBarang->stok = $totalQty;

        $stockBarang->save();
    }
}
