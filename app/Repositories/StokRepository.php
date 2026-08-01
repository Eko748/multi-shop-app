<?php

namespace App\Repositories;

use App\Models\JenisBarang;
use App\Models\StockBarangBatch;
use App\Models\StockBarangBermasalah; // Sesuaikan namespace model Anda
use App\Models\TransaksiKasirHarian; // Sesuaikan namespace model Anda
use Carbon\Carbon;
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
        $now = Carbon::now();
        $isCurrentMonth = ($now->month === $month && $now->year === $year);

        // =========================================================================
        // JIKA AKSES BULAN LALU (BACKTRACKING)
        // =========================================================================
        $targetMonthEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d H:i:s');
        $targetDateEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');

        // 1. Ambil TOTAL MASUK (Pembelian Batch - Biasanya hanya ada di Parent)
        $batches = StockBarangBatch::query()
            ->join('stock_barang', 'stock_barang_batch.stock_barang_id', '=', 'stock_barang.id')
            ->join('barang', 'stock_barang.barang_id', '=', 'barang.id')
            ->join('jenis_barang', 'barang.jenis_barang_id', '=', 'jenis_barang.id')
            ->whereNull('stock_barang.deleted_at')
            ->whereNull('barang.deleted_at')
            ->whereNull('jenis_barang.deleted_at')
            ->select(
                'jenis_barang.id as id_jenis_barang',
                'jenis_barang.nama_jenis_barang',
                DB::raw('SUM(stock_barang_batch.qty_masuk) as total_qty_masuk'),
                DB::raw('SUM(stock_barang_batch.qty_masuk * stock_barang_batch.harga_beli) as total_harga_masuk')
            )
            ->where('stock_barang_batch.created_at', '<=', $targetMonthEnd)
            ->when($tokoId !== null && $tokoId !== 'all' && $tokoId != 0, function ($q) use ($tokoId) {
                return $q->where('stock_barang_batch.toko_id', $tokoId);
            })
            ->groupBy('jenis_barang.id', 'jenis_barang.nama_jenis_barang')
            ->get()
            ->keyBy('id_jenis_barang');

        // 2. Ambil TOTAL TERJUAL
        $sales = TransaksiKasirHarian::query()
            ->select(
                'jenis_barang_id',
                DB::raw('SUM(total_qty) as total_qty_terjual'),
                DB::raw('SUM(total_harga_beli) as total_harga_terjual')
            )
            ->where('jenis_barang_id', '!=', 0)
            ->where('tanggal', '<=', $targetDateEnd)
            ->when($tokoId !== null && $tokoId !== 'all' && $tokoId != 0, function ($q) use ($tokoId) {
                return $q->where('toko_id', $tokoId);
            })
            ->groupBy('jenis_barang_id')
            ->get()
            ->keyBy('jenis_barang_id');

        // 3. Ambil TOTAL BERMASALAH
        $problems = StockBarangBermasalah::query()
            ->join('stock_barang_batch', 'stock_barang_bermasalah.stock_barang_batch_id', '=', 'stock_barang_batch.id')
            ->join('stock_barang', 'stock_barang_batch.stock_barang_id', '=', 'stock_barang.id')
            ->join('barang', 'stock_barang.barang_id', '=', 'barang.id')
            ->whereNull('stock_barang.deleted_at')
            ->whereNull('barang.deleted_at')
            ->select(
                'barang.jenis_barang_id',
                DB::raw('SUM(stock_barang_bermasalah.qty) as total_qty_bermasalah'),
                DB::raw('SUM(stock_barang_bermasalah.qty * stock_barang_batch.harga_beli) as total_harga_bermasalah')
            )
            ->where('stock_barang_bermasalah.created_at', '<=', $targetMonthEnd)
            ->when($tokoId !== null && $tokoId !== 'all' && $tokoId != 0, function ($q) use ($tokoId) {
                return $q->where('stock_barang_batch.toko_id', $tokoId);
            })
            ->groupBy('barang.jenis_barang_id')
            ->get()
            ->keyBy('jenis_barang_id');

        // 4. Pengiriman KELUAR (dari toko ini ke toko lain) -> Mengurangi stok toko pengirim
        $transferOut = DB::table('pengiriman_barang_detail')
            ->join('pengiriman_barang', 'pengiriman_barang_detail.pengiriman_barang_id', '=', 'pengiriman_barang.id')
            ->join('stock_barang_batch', 'pengiriman_barang_detail.stock_barang_batch_id', '=', 'stock_barang_batch.id')
            ->join('stock_barang', 'stock_barang_batch.stock_barang_id', '=', 'stock_barang.id')
            ->join('barang', 'stock_barang.barang_id', '=', 'barang.id')
            ->select(
                'barang.jenis_barang_id',
                DB::raw('SUM(pengiriman_barang_detail.qty_send) as total_qty_out'),
                DB::raw('SUM(pengiriman_barang_detail.qty_send * stock_barang_batch.harga_beli) as total_harga_out')
            )
            ->where('pengiriman_barang.created_at', '<=', $targetMonthEnd)
            ->when($tokoId !== null && $tokoId !== 'all' && $tokoId != 0, function ($q) use ($tokoId) {
                return $q->where('pengiriman_barang.toko_asal_id', $tokoId);
            })
            ->groupBy('barang.jenis_barang_id')
            ->get()
            ->keyBy('jenis_barang_id');

        // 5. Pengiriman MASUK (dari toko lain ke toko ini) -> Menambah stok toko penerima
        $transferIn = DB::table('pengiriman_barang_detail')
            ->join('pengiriman_barang', 'pengiriman_barang_detail.pengiriman_barang_id', '=', 'pengiriman_barang.id')
            ->join('stock_barang_batch', 'pengiriman_barang_detail.stock_barang_batch_id', '=', 'stock_barang_batch.id')
            ->join('stock_barang', 'stock_barang_batch.stock_barang_id', '=', 'stock_barang.id')
            ->join('barang', 'stock_barang.barang_id', '=', 'barang.id')
            ->join('jenis_barang', 'barang.jenis_barang_id', '=', 'jenis_barang.id')
            ->select(
                'barang.jenis_barang_id',
                'jenis_barang.nama_jenis_barang',
                DB::raw('SUM(pengiriman_barang_detail.qty_verified) as total_qty_in'),
                DB::raw('SUM(pengiriman_barang_detail.qty_verified * stock_barang_batch.harga_beli) as total_harga_in')
            )
            ->where('pengiriman_barang.created_at', '<=', $targetMonthEnd)
            ->when($tokoId !== null && $tokoId !== 'all' && $tokoId != 0, function ($q) use ($tokoId) {
                return $q->where('pengiriman_barang.toko_tujuan_id', $tokoId);
            })
            ->groupBy('barang.jenis_barang_id', 'jenis_barang.nama_jenis_barang')
            ->get()
            ->keyBy('jenis_barang_id');

        // =========================================================================
        // KUNCI PERBAIKAN: GABUNGKAN SEMUA JENIS BARANG YANG DITEMUKAN
        // =========================================================================
        $allJenisBarangIds = collect()
            ->merge($batches->keys())
            ->merge($sales->keys())
            ->merge($problems->keys())
            ->merge($transferOut->keys())
            ->merge($transferIn->keys())
            ->unique();

        $jenisBarangMap = JenisBarang::whereIn('id', $allJenisBarangIds)
            ->pluck('nama_jenis_barang', 'id')
            ->toArray();

        return $allJenisBarangIds->map(function ($jenisId) use ($batches, $sales, $problems, $transferOut, $transferIn, $jenisBarangMap) {
            $batch = $batches->get($jenisId);
            $sale = $sales->get($jenisId);
            $problem = $problems->get($jenisId);
            $tfOut = $transferOut->get($jenisId);
            $tfIn = $transferIn->get($jenisId);

            $qtyMasuk = $batch ? (float) $batch->total_qty_masuk : 0;
            $hargaMasuk = $batch ? (float) $batch->total_harga_masuk : 0;

            $qtyTerjual = $sale ? (float) $sale->total_qty_terjual : 0;
            $hargaTerjual = $sale ? (float) $sale->total_harga_terjual : 0;

            $qtyBermasalah = $problem ? (float) $problem->total_qty_bermasalah : 0;
            $hargaBermasalah = $problem ? (float) $problem->total_harga_bermasalah : 0;

            $qtyOut = $tfOut ? (float) $tfOut->total_qty_out : 0;
            $hargaOut = $tfOut ? (float) $tfOut->total_harga_out : 0;

            $qtyIn = $tfIn ? (float) $tfIn->total_qty_in : 0;
            $hargaIn = $tfIn ? (float) $tfIn->total_harga_in : 0;

            // RUMUS LENGKAP:
            // Sisa = (Batch Masuk + Transfer Masuk) - (Terjual + Bermasalah + Transfer Keluar)
            $sisaQty = ($qtyMasuk + $qtyIn) - ($qtyTerjual + $qtyBermasalah + $qtyOut);
            $sisaHarga = ($hargaMasuk + $hargaIn) - ($hargaTerjual + $hargaBermasalah + $hargaOut);

            return [
                'id_jenis_barang' => $jenisId,
                'nama_jenis_barang' => $jenisBarangMap[$jenisId] ?? 'Lainnya',
                'total_qty' => (int) max(0, $sisaQty),
                'total_harga' => (float) max(0, $sisaHarga),
            ];
        })->filter(function ($item) {
            return $item['total_qty'] > 0; // Hanya tampilkan yang stoknya > 0
        })->values();
    }
}
