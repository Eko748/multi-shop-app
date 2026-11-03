<?php

namespace App\Repositories;

use App\Models\StockBarangBatch;
// Sesuaikan namespace model Anda
use App\Models\StockBarangBulanan;
// Sesuaikan namespace model Anda
use Illuminate\Support\Facades\DB;

class StokRepository
{
    public function getStokData($tokoId, int $month, int $year): object
    {
        return StockBarangBatch::whereHas('stockBarang', function ($q) use ($tokoId) {
            $q->when(
                $tokoId !== null && $tokoId !== 'all' && $tokoId != 0,
                fn ($x) => $x->where('toko_id', $tokoId)
            );
        })
            ->with(['stockBarang.barang.jenis'])
            ->where(function ($query) use ($month, $year) {
                $query->whereYear('created_at', '<', $year)
                    ->orWhere(function ($q) use ($month, $year) {
                        $q->whereYear('created_at', $year)
                            ->whereMonth('created_at', '<=', $month);
                    });
            })
            ->selectRaw('SUM(qty_sisa) as total_qty')
            ->selectRaw('SUM(qty_sisa * harga_beli) as total_harga')
            ->first();
    }

    public function getStokPerJenis($tokoId, int $month, int $year)
    {
        // Selalu baca dari tabel stock_barang_bulanan agar nilai konsisten dengan Neraca
        $query = StockBarangBulanan::query()
            ->leftJoin('jenis_barang', 'stock_barang_bulanan.jenis_barang_id', '=', 'jenis_barang.id')
            ->where('stock_barang_bulanan.tahun', $year)
            ->where('stock_barang_bulanan.bulan', $month);

        // Filter toko jika spesifik (bukan 'all', 0, atau null)
        if ($tokoId !== null && $tokoId !== 'all' && $tokoId != 0) {
            $query->where('stock_barang_bulanan.toko_id', $tokoId);
        }

        // GroupBy & SUM secara konsisten untuk Toko Spesifik maupun All Toko
        $data = $query->select(
            DB::raw('COALESCE(jenis_barang.id, stock_barang_bulanan.jenis_barang_id) as id_jenis_barang'),
            DB::raw("CASE WHEN stock_barang_bulanan.jenis_barang_id = 0 THEN 'Aset Lainnya' ELSE jenis_barang.nama_jenis_barang END as nama_jenis_barang"),
            DB::raw('SUM(stock_barang_bulanan.qty_sisa) as total_qty'),
            DB::raw('SUM(stock_barang_bulanan.nilai_aset) as total_harga')
        )
            ->groupBy('stock_barang_bulanan.jenis_barang_id', 'jenis_barang.id', 'jenis_barang.nama_jenis_barang')
            ->get();

        return $data->map(function ($item) {
            return [
                'id_jenis_barang' => $item->id_jenis_barang,
                'nama_jenis_barang' => $item->nama_jenis_barang ?? 'Aset Lainnya',
                'total_qty' => (int) $item->total_qty,
                'total_harga' => (float) $item->total_harga,
            ];
        })->values();
    }
}
