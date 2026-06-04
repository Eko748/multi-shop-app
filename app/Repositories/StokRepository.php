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

        // 2. Ambil SEMUA data StockBarangBatch yang masuk sampai bulan target
        // Menggunakan qty_masuk (bukan qty_sisa), dikelompokkan per jenis_barang_id
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

        // 3. Ambil data penjualan (TransaksiKasirHarian) dari AWAL sampai BULAN TARGET SAJA
        // Penjualan setelah bulan target diabaikan (karena tidak boleh mengurangi aset bulan target)
        $salesUntilTargetMonth = TransaksiKasirHarian::when(
            $tokoId !== null && $tokoId !== 'all' && $tokoId != 0,
            fn ($x) => $x->where('toko_id', $tokoId)
        )
            ->where('jenis_barang_id', '!=', 0)
            ->where('tanggal', '<=', $targetMonthEnd->format('Y-m-d'))
            ->get()
            ->groupBy('jenis_barang_id');

        // 4. Hitung Nilai Aset Bulan Target
        return $batches->map(function ($group, $jenisBarangId) use ($salesUntilTargetMonth) {
            $first = $group->first();
            $jenis = $first->stockBarang->barang->jenis;

            // TOTAL MASUK: Total semua barang yang pernah masuk sampai bulan target
            $totalQtyMasuk = $group->sum('qty_masuk');

            // Menghitung total harga beli awal (qty_masuk * harga_beli)
            $totalHargaMasuk = $group->sum(fn ($i) => $i->qty_masuk * $i->harga_beli);

            // TOTAL TERJUAL: Total penjualan dari awal sampai bulan target
            $salesGroup = $salesUntilTargetMonth->get($jenisBarangId);
            $qtyTerjual = $salesGroup ? $salesGroup->sum('total_qty') : 0;
            $hargaBeliTerjual = $salesGroup ? $salesGroup->sum('total_harga_beli') : 0;

            // RUMUS BARU: Total Masuk - Total Terjual (Sampai Bulan Target)
            // Otomatis transaksi bulan setelahnya tidak mengganggu nilai ini
            $sisaQtyBulanTarget = $totalQtyMasuk - $qtyTerjual;
            $sisaHargaBulanTarget = $totalHargaMasuk - $hargaBeliTerjual;

            return [
                'id_jenis_barang' => $jenis->id,
                'nama_jenis_barang' => $jenis->nama_jenis_barang,
                'total_qty' => max(0, $sisaQtyBulanTarget), // max(0) untuk menghindari angka minus jika data transaksional tidak sinkron
                'total_harga' => max(0, $sisaHargaBulanTarget),
            ];
        })->values();
    }
}
