<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LevelHargaStockBarangSeeder extends Seeder
{
    public function run()
    {
        $stockItems = DB::table('stock_barang')->whereNull('deleted_at')->get();

        foreach ($stockItems as $stock) {
            $barang = DB::table('barang')
                ->where('id', $stock->id_barang)
                ->whereNull('deleted_at')
                ->first();

            if ($barang) {
                DB::table('stock_barang')
                    ->where('id', $stock->id)
                    ->update(['level_harga' => $barang->level_harga]);
            }
        }

        $this->command->info('Level harga pada stock_barang berhasil disamakan dengan barang.');
    }
}

