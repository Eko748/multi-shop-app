<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DetailKasir;
use App\Models\Kasir;
use App\Models\StockBarang;

class FixHppJualSeeder extends Seeder
{
    public function run(): void
    {
        $detailKasirs = DetailKasir::where('hpp_jual', 0)->get();

        if ($detailKasirs->isEmpty()) {
            echo "Tidak ada data hpp_jual = 0 yang ditemukan.\n";
            return;
        }

        $totalUpdated = 0;

        foreach ($detailKasirs as $item) {
            $kasir = Kasir::find($item->id_kasir);
            if (!$kasir) continue;

            $hpp = 0;
            if ($kasir->id_toko == 1) {
                $stock = StockBarang::where('id_barang', $item->id_barang)->first();
                $hpp = $stock?->hpp_baru ?? 0;
            }

            if ($hpp > 0) {
                $oldHpp = $item->hpp_jual;
                $item->hpp_jual = $hpp;
                $item->save();
                $totalUpdated++;

                echo "Updated: ID {$item->id} | ID_Kasir: {$item->id_kasir} | ID_Barang: {$item->id_barang} | Old HPP: {$oldHpp} | New HPP: {$hpp}\n";
            }
        }

        echo "✅ Selesai update hpp_jual pada {$totalUpdated} baris detail_kasir.\n";
    }
}
