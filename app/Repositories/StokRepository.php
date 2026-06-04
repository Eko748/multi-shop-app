<?php

namespace App\Repositories;

use App\Models\StockBarangBatch;
use App\Models\StockBarangBermasalah;
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

    // 2. Ambil SEMUA data StockBarangBatch yang masuk sampai bulan target
    $batches = StockBarangBatch::whereHas('stockBarang', function ($q) use ($tokoId) {
        $q->when(
            $tokoId !== null && $tokoId !== 'all' && $tokoId != 0,
            fn ($x) => $x->where('toko_id', $tokoId)
        );
    })
        ->with(['stockBarang.barang.jenis'])
        ->where('created_at', '<=', $targetMonthEnd)
        ->get()
        ->groupBy(fn ($item) => $item->stockBarang->barang->jenis->id);

    // 3. Ambil data penjualan (TransaksiKasirHarian) dari AWAL sampai BULAN TARGET
    $salesUntilTargetMonth = TransaksiKasirHarian::when(
        $tokoId !== null && $tokoId !== 'all' && $tokoId != 0,
        fn ($x) => $x->where('toko_id', $tokoId)
    )
        ->where('jenis_barang_id', '!=', 0)
        ->where('tanggal', '<=', $targetMonthEnd->format('Y-m-d'))
        ->get()
        ->groupBy('jenis_barang_id');

    // 4. Ambil data StockBarangBermasalah dari AWAL sampai BULAN TARGET
    // Filter berdasarkan tanggal temuan (created_at) dan toko_id
    $problemsUntilTargetMonth = StockBarangBermasalah::whereHas('batch.stockBarang', function ($q) use ($tokoId) {
        $q->when(
            $tokoId !== null && $tokoId !== 'all' && $tokoId != 0,
            fn ($x) => $x->where('toko_id', $tokoId)
        );
    })
        ->with(['batch.stockBarang.barang.jenis'])
        ->where('created_at', '<=', $targetMonthEnd)
        ->get()
        // Dikelompokkan berdasarkan jenis_barang_id agar strukturnya sama dengan data penjualan
        ->groupBy(fn ($item) => $item->batch->stockBarang->barang->jenis->id);

    // 5. Hitung Nilai Aset Bulan Target (Backtracking Semesta)
    return $batches->map(function ($group, $jenisBarangId) use ($salesUntilTargetMonth, $problemsUntilTargetMonth) {
        $first = $group->first();
        $jenis = $first->stockBarang->barang->jenis;

        // TOTAL MASUK (Awal mula aset)
        $totalQtyMasuk = $group->sum('qty_masuk');
        $totalHargaMasuk = $group->sum(fn ($i) => $i->qty_masuk * $i->harga_beli);

        // TOTAL TERJUAL (Sampai bulan target)
        $salesGroup = $salesUntilTargetMonth->get($jenisBarangId);
        $qtyTerjual = $salesGroup ? $salesGroup->sum('total_qty') : 0;
        $hargaBeliTerjual = $salesGroup ? $salesGroup->sum('total_harga_beli') : 0;

        // TOTAL BERMASALAH (Sampai bulan target)
        $problemGroup = $problemsUntilTargetMonth->get($jenisBarangId);
        $qtyBermasalah = $problemGroup ? $problemGroup->sum('qty') : 0;
        // Menghitung nilai kerugian barang bermasalah berdasarkan harga_beli batch-nya masing-masing
        $hargaBeliBermasalah = $problemGroup ? $problemGroup->sum(fn ($p) => $p->qty * $p->stockBarangBatch->harga_beli) : 0;

        // RUMUS BARU: Total Masuk - Total Terjual - Total Bermasalah
        $sisaQtyBulanTarget = $totalQtyMasuk - $qtyTerjual - $qtyBermasalah;
        $sisaHargaBulanTarget = $totalHargaMasuk - $hargaBeliTerjual - $hargaBeliBermasalah;

        return [
            'id_jenis_barang' => $jenis->id,
            'nama_jenis_barang' => $jenis->nama_jenis_barang,
            'total_qty' => max(0, $sisaQtyBulanTarget),
            'total_harga' => max(0, $sisaHargaBulanTarget),
        ];
    })->values();
}
}
