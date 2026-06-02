<?php

namespace App\Repositories;

use App\Models\StockBarangBatch;
use App\Models\TransaksiKasirDetail;
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

        // 2. Ambil data StockBarangBatch kondisi Real-time
        // Hanya batch yang dibuat sebelum atau pada akhir bulan target
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

        // 3. Ambil data penjualan dari TransaksiKasirDetail yang terjadi SETELAH bulan target
        // Filter toko_id dilakukan langsung melalui relasi stockBarangBatch
        $salesAfterTargetMonth = TransaksiKasirDetail::whereHas('stockBarangBatch', function ($q) use ($tokoId) {
            $q->when(
                $tokoId !== null && $tokoId !== 'all' && $tokoId != 0,
                fn ($x) => $x->where('toko_id', $tokoId)
            );
        })
            ->whereNull('deleted_at')
            ->where('created_at', '>', $targetMonthEnd->toDateTimeString())
            ->with(['stockBarangBatch.stockBarang.barang.jenis']) // Muat relasi lengkap sampai ke jenis
            ->get()
            // Kelompokkan data penjualan berdasarkan ID Jenis Barang yang ditarik dari relasi batch
            ->groupBy(fn ($detail) => $detail->stockBarangBatch->stockBarang->barang->jenis->id ?? 0);

        // 4. Gabungkan data (Backtracking)
        return $currentBatches->map(function ($group, $jenisBarangId) use ($salesAfterTargetMonth) {
            $first = $group->first();
            $jenis = $first->stockBarang->barang->jenis;

            // KONDISI SEKARANG: Stok sisa di database saat ini
            $currentQtySisa = $group->sum('qty_sisa');
            $currentHargaSisa = $group->sum(fn ($i) => $i->qty_sisa * $i->harga_beli);

            // KONDISI MASA DEPAN (PENAMBAL): Penjualan retail setelah bulan target
            $salesGroup = $salesAfterTargetMonth->get($jenisBarangId);

            // Menggunakan kolom 'qty' dan 'harga_beli' langsung dari TransaksiKasirDetail
            $qtyTerjualSetelahnya = $salesGroup ? $salesGroup->sum('qty') : 0;
            $hargaBeliTerjualSetelahnya = $salesGroup ? $salesGroup->sum(fn ($d) => $d->qty * $d->harga_beli) : 0;

            // RUMUS BACKTRACKING: Mengembalikan barang yang terjual di masa depan ke masa lalu
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
