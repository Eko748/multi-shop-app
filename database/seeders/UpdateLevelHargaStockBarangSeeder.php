<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateLevelHargaStockBarangSeeder extends Seeder
{
    public function run()
    {
        // Ambil data stock_barang yang level_harga-nya masih null
        $stocks = DB::table('stock_barang')
            ->whereNull('level_harga')
            ->get();

        foreach ($stocks as $stock) {
            // Ambil data level_harga dari tabel barang berdasarkan id_barang
            $barang = DB::table('barang')
                ->where('id', $stock->id_barang)
                ->first();

            if ($barang && !is_null($barang->level_harga)) {
                DB::table('stock_barang')
                    ->where('id', $stock->id)
                    ->update(['level_harga' => $barang->level_harga]);
            }
        }

        echo "Seeder selesai: level_harga dari barang telah ditransfer ke stock_barang.\n";
    }
}
