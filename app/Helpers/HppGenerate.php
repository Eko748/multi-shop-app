<?php

namespace App\Helpers;

use App\Models\{StockBarang, StockBarangBatch};

class HppGenerate
{
    // public static function recalcHpp($stockBarangId)
    // {
    //     $stockBarang = StockBarang::where('id', $stockBarangId)
    //         ->lockForUpdate()
    //         ->first();

    //     if (!$stockBarang) return;

    //     $batches = StockBarangBatch::where('stock_barang_id', $stockBarangId)->get();

    //     $totalQty = 0;
    //     $totalValue = 0;

    //     foreach ($batches as $b) {
    //         $totalQty += $b->qty_sisa;

    //         // ❗ PAKAI HARGA BELI ASLI
    //         $totalValue += $b->qty_sisa * $b->harga_beli;
    //     }

    //     if ($totalQty > 0) {
    //         $stockBarang->hpp_awal = $stockBarang->hpp_baru;
    //         $stockBarang->hpp_baru = $totalValue / $totalQty;
    //     } else {
    //         $stockBarang->hpp_awal = 0;
    //         $stockBarang->hpp_baru = 0;
    //     }

    //     $stockBarang->stok = $totalQty;

    //     $stockBarang->save();
    // }

    public static function recalcHpp($stockBarangId)
    {
        $stockBarang = StockBarang::where('id', $stockBarangId)
            ->lockForUpdate()
            ->first();

        if (!$stockBarang) return;

        $batches = StockBarangBatch::where('stock_barang_id', $stockBarangId)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $prevHpp = 0;
        $runningStock = 0;

        foreach ($batches as $batch) {

            $batch->hpp_awal = $prevHpp;

            $totalQty = $runningStock + $batch->qty_masuk;

            if ($totalQty > 0) {
                $batch->hpp_baru =
                    (
                        ($runningStock * $prevHpp) +
                        ($batch->qty_masuk * $batch->harga_beli)
                    ) / $totalQty;
            } else {
                $batch->hpp_baru = $prevHpp;
            }

            $batch->save();

            $qtyKeluar = $batch->qty_masuk - $batch->qty_sisa;

            $runningStock =
                $runningStock + $batch->qty_masuk - $qtyKeluar;

            $prevHpp = $batch->hpp_baru;
        }

        $stockBarang->hpp_awal = $stockBarang->hpp_baru;
        $stockBarang->hpp_baru = $prevHpp;
        $stockBarang->stok = $batches->sum('qty_sisa');
        $stockBarang->save();
    }
}
