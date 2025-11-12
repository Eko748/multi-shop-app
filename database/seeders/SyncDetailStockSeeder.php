<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SyncDetailStockSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil semua stock_barang
        $stockBarangList = DB::table('stock_barang')->get();

        foreach ($stockBarangList as $stockBarang) {
            $idStock   = $stockBarang->id;
            $idBarang  = $stockBarang->id_barang;
            $stokBenar = (int) $stockBarang->stock;
            
            // Ambil detail_stock sesuai id_stock dan id_barang
            $detailStocks = DB::table('detail_stock')
                ->where('id_stock', $idStock)
                ->where('id_barang', $idBarang)
                ->orderBy('id') // urutkan paling lama → terbaru
                ->get();

            if ($detailStocks->isEmpty()) {
                continue; // tidak ada detail_stock valid
            }

            // Hitung total qty_now
            $totalQtyNow = $detailStocks->sum('qty_now');

            if ($totalQtyNow == $stokBenar) {
                continue; // sudah sinkron
            }

            echo "Sinkronisasi stok ID {$idStock}, barang ID {$idBarang}, stok_barang={$stokBenar}, total detail_stock={$totalQtyNow}\n";

            $stokSisa = $stokBenar;

            foreach ($detailStocks as $row) {
                if ($stokSisa <= 0) {
                    // Semua stok habis → qty_now = 0, qty_out penuh
                    DB::table('detail_stock')
                        ->where('id', $row->id)
                        ->update([
                            'qty_now' => 0,
                            'qty_out' => $row->qty_buy,
                        ]);
                } else {
                    if ($stokSisa >= $row->qty_buy) {
                        // Stok masih cukup untuk isi penuh
                        DB::table('detail_stock')
                            ->where('id', $row->id)
                            ->update([
                                'qty_now' => $row->qty_buy,
                                'qty_out' => 0,
                            ]);
                        $stokSisa -= $row->qty_buy;
                    } else {
                        // Sisa stok kurang dari qty_buy
                        DB::table('detail_stock')
                            ->where('id', $row->id)
                            ->update([
                                'qty_now' => $stokSisa,
                                'qty_out' => $row->qty_buy - $stokSisa,
                            ]);
                        $stokSisa = 0;
                    }
                }
            }
        }

        echo "Sinkronisasi selesai.\n";
    }
}
