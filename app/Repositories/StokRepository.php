<?php

namespace App\Repositories;

use App\Models\StockBarangBatch;
use App\Models\TransaksiKasirHarian;
use Carbon\Carbon;

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
        // 1. Tentukan batas akhir dari bulan target (Contoh: 2026-05-31 23:59:59)
        $targetMonthEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // 2. Ambil data StockBarangBatch yang ada saat ini (Kondisi Real-time)
        // Filter hanya batch yang sudah lahir/dibuat sebelum atau pada bulan target
        $currentBatches = StockBarangBatch::whereHas('stockBarang', function ($q) use ($tokoId) {
            $q->when(
                $tokoId !== null && $tokoId !== 'all' && $tokoId != 0,
                fn ($x) => $x->where('toko_id', $tokoId)
            );
        })
            ->with(['stockBarang.barang.jenis'])
            ->where('created_at', '<=', $targetMonthEnd)
            ->get()
            ->groupBy(fn ($item) => $item->stockBarang->barang->jenis->id);

        // 3. Ambil data penjualan (TransaksiKasirHarian) yang terjadi SETELAH bulan target
        // Karena penjualan setelah bulan target inilah yang membuat stok saat ini "berkurang/bocor"
        $salesAfterTargetMonth = TransaksiKasirHarian::when(
            $tokoId !== null && $tokoId !== 'all' && $tokoId != 0,
            fn ($x) => $x->where('toko_id', $tokoId)
        )
            ->where('jenis_barang_id', '!=', 0)
            ->where('tanggal', '>', $targetMonthEnd->format('Y-m-d'))
            ->get()
            ->groupBy('jenis_barang_id');

        // 4. Gabungkan data untuk menghitung nilai masa lalu (Backtracking)
        // Kita gunakan master data jenis barang dari batch saat ini sebagai acuan loop
        return $currentBatches->map(function ($group, $jenisBarangId) use ($salesAfterTargetMonth) {
            $first = $group->first();
            $jenis = $first->stockBarang->barang->jenis;

            // KONDISI SEKARANG: Stok sisa saat ini di database
            $currentQtySisa = $group->sum('qty_sisa');
            $currentHargaSisa = $group->sum(fn ($i) => $i->qty_sisa * $i->harga_beli);

            // KONDISI MASA DEPAN (PENAMBAL): Penjualan yang terjadi setelah bulan target
            $salesGroup = $salesAfterTargetMonth->get($jenisBarangId);
            $qtyTerjualSetelahnya = $salesGroup ? $salesGroup->sum('total_qty') : 0;
            $hargaBeliTerjualSetelahnya = $salesGroup ? $salesGroup->sum('total_harga_beli') : 0;

            // RUMUS BACKTRACKING: Kondisi Sekarang + Penjualan setelahnya
            $totalQtyBulanLalu = $currentQtySisa + $qtyTerjualSetelahnya;
            $totalHargaBulanLalu = $currentHargaSisa + $hargaBeliTerjualSetelahnya;

            return [
                'id_jenis_barang' => $jenis->id,
                'nama_jenis_barang' => $jenis->nama_jenis_barang,
                'total_qty' => $totalQtyBulanLalu,
                'total_harga' => $totalHargaBulanLalu,
            ];
        })
            ->values();
    }
}
