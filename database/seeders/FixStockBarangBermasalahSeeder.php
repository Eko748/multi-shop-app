<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StockBarangBermasalah;
use App\Models\StockBarang;
use App\Models\DetailStockBarang;
use App\Models\DetailPembelianBarang;

class FixStockBarangBermasalahSeeder extends Seeder
{
    public function run(): void
    {
        $records = StockBarangBermasalah::all();

        foreach ($records as $record) {
            $sisaQty = $record->qty;
            $totalHpp = 0;

            // ambil detail pembelian per stock, urutkan biar konsisten (FIFO)
            $details = DetailStockBarang::where('id_stock', $record->stock_barang_id)
                ->with('detailPembelian')
                ->orderBy('id') // bisa diganti orderBy('created_at')
                ->get();

            foreach ($details as $ds) {
                if ($sisaQty <= 0) break;

                $harga = $ds->detailPembelian->harga_barang ?? 0;
                $ambil = min($sisaQty, $ds->qty_buy);

                $totalHpp += $ambil * $harga;
                $sisaQty -= $ambil;
            }

            // untuk hpp, cukup jadi asumsi rata-rata total_hpp / qty
            $hpp = $record->qty > 0 ? $totalHpp / $record->qty : 0;

            $record->update([
                'hpp' => $hpp,
                'total_hpp' => $totalHpp,
            ]);
        }
    }
}
