<?php

namespace App\Repositories;

use App\Models\StockBarangBatch;
use App\Models\StockBarangBermasalah; // Sesuaikan namespace model Anda
use App\Models\StockBarangBulanan;
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

        // Jika env GPS DAN BUKAN bulan ini (artinya bulan lalu / masa lalu), ambil dari rekap bulanan
        if (config('app.name') === 'GPS' && ! $isCurrentMonth) {
            $query = StockBarangBulanan::query()
                ->leftJoin('jenis_barang', 'stock_barang_bulanan.jenis_barang_id', '=', 'jenis_barang.id')
                ->where('stock_barang_bulanan.tahun', $year)
                ->where('stock_barang_bulanan.bulan', $month);

            // Filter toko jika spesifik (bukan 'all' atau 0/null)
            if ($tokoId !== null && $tokoId !== 'all' && $tokoId != 0) {
                $query->where('stock_barang_bulanan.toko_id', $tokoId);
            }

            // Jika 'all' atau 0, agregasi ulang per jenis barang dari semua toko
            if ($tokoId === 'all' || $tokoId == 0 || $tokoId === null) {
                $data = $query->select(
                    DB::raw('COALESCE(jenis_barang.id, stock_barang_bulanan.jenis_barang_id) as id_jenis_barang'),
                    DB::raw("CASE WHEN stock_barang_bulanan.jenis_barang_id = 0 THEN 'Aset Lainnya' ELSE jenis_barang.nama_jenis_barang END as nama_jenis_barang"),
                    DB::raw('SUM(stock_barang_bulanan.qty_sisa) as total_qty'),
                    DB::raw('SUM(stock_barang_bulanan.nilai_aset) as total_harga')
                )
                    ->groupBy('stock_barang_bulanan.jenis_barang_id', 'jenis_barang.id', 'jenis_barang.nama_jenis_barang')
                    ->get();
            } else {
                $data = $query->select(
                    DB::raw('COALESCE(jenis_barang.id, stock_barang_bulanan.jenis_barang_id) as id_jenis_barang'),
                    DB::raw("CASE WHEN stock_barang_bulanan.jenis_barang_id = 0 THEN 'Aset Lainnya' ELSE jenis_barang.nama_jenis_barang END as nama_jenis_barang"),
                    'stock_barang_bulanan.qty_sisa as total_qty',
                    'stock_barang_bulanan.nilai_aset as total_harga'
                )->get();
            }

            return $data->map(function ($item) {
                return [
                    'id_jenis_barang' => $item->id_jenis_barang,
                    'nama_jenis_barang' => $item->nama_jenis_barang ?? 'Aset Lainnya',
                    'total_qty' => (int) $item->total_qty,
                    'total_harga' => (float) $item->total_harga,
                ];
            })->values();
        }

        // =========================================================================
        // LOGIKA BULAN INI ATAU NON-GPS
        // =========================================================================
        if ($isCurrentMonth) {
            $data = StockBarangBatch::query()
                ->join('stock_barang', 'stock_barang_batch.stock_barang_id', '=', 'stock_barang.id')
                ->join('barang', 'stock_barang.barang_id', '=', 'barang.id')
                ->join('jenis_barang', 'barang.jenis_barang_id', '=', 'jenis_barang.id')
                ->whereNull('stock_barang.deleted_at')
                ->whereNull('barang.deleted_at')
                ->whereNull('jenis_barang.deleted_at')
                ->select(
                    'jenis_barang.id as id_jenis_barang',
                    'jenis_barang.nama_jenis_barang',
                    DB::raw('SUM(stock_barang_batch.qty_sisa) as total_qty'),
                    DB::raw('SUM(stock_barang_batch.qty_sisa * stock_barang_batch.harga_beli) as total_harga')
                )
                ->when($tokoId !== null && $tokoId !== 'all' && $tokoId != 0, function ($q) use ($tokoId) {
                    return $q->where('stock_barang_batch.toko_id', $tokoId);
                })
                ->groupBy('jenis_barang.id', 'jenis_barang.nama_jenis_barang')
                ->get();

            return $data->map(function ($item) {
                return [
                    'id_jenis_barang' => $item->id_jenis_barang,
                    'nama_jenis_barang' => $item->nama_jenis_barang,
                    'total_qty' => (int) $item->total_qty,
                    'total_harga' => (float) $item->total_harga,
                ];
            })->values();
        }

        // Backtracking lama (untuk non-GPS yang bukan bulan ini)...
        $targetMonthEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d H:i:s');
        $targetDateEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');

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

        return $batches->map(function ($batch) use ($sales, $problems, $transferOut) {
            $sale = $sales->get($batch->id_jenis_barang);
            $problem = $problems->get($batch->id_jenis_barang);
            $tfOut = $transferOut->get($batch->id_jenis_barang);

            $qtyTerjual = $sale ? (float) $sale->total_qty_terjual : 0;
            $hargaTerjual = $sale ? (float) $sale->total_harga_terjual : 0;

            $qtyBermasalah = $problem ? (float) $problem->total_qty_bermasalah : 0;
            $hargaBermasalah = $problem ? (float) $problem->total_harga_bermasalah : 0;

            $qtyOut = $tfOut ? (float) $tfOut->total_qty_out : 0;
            $hargaOut = $tfOut ? (float) $tfOut->total_harga_out : 0;

            $sisaQty = (float) $batch->total_qty_masuk - $qtyTerjual - $qtyBermasalah - $qtyOut;
            $sisaHarga = (float) $batch->total_harga_masuk - $hargaTerjual - $hargaBermasalah - $hargaOut;

            return [
                'id_jenis_barang' => $batch->id_jenis_barang,
                'nama_jenis_barang' => $batch->nama_jenis_barang,
                'total_qty' => (int) max(0, $sisaQty),
                'total_harga' => (float) max(0, $sisaHarga),
            ];
        })->values();
    }
}
