<?php

namespace App\Repositories;

use App\Models\TransaksiKasirDetail;
use App\Models\PenjualanNonFisik;
use App\Models\ReturMemberDetail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LaporanPenjualanRepository
{
public function getKasirDataGroupJenis($startDate, $endDate, $idToko = null)
{
    $start = Carbon::parse($startDate)->startOfDay();
    $end = Carbon::parse($endDate)->endOfDay();

    $query = TransaksiKasirDetail::select(
        'jenis_barang.id as id_jenis_barang',
        'jenis_barang.nama_jenis_barang as nama',
        DB::raw('COUNT(DISTINCT transaksi_kasir.id) as jml_trx'),
        DB::raw('SUM(transaksi_kasir_detail.qty) as item_qty'),
        DB::raw('SUM(transaksi_kasir_detail.subtotal) as nilai_trx'),
        DB::raw('SUM(transaksi_kasir_detail.qty * stock_barang_batch.harga_beli) as nilai_hpp')
    )
    ->join('transaksi_kasir', 'transaksi_kasir.id', '=', 'transaksi_kasir_detail.transaksi_kasir_id')
    ->join('stock_barang_batch', 'stock_barang_batch.id', '=', 'transaksi_kasir_detail.stock_barang_batch_id')
    ->join('stock_barang', 'stock_barang.id', '=', 'stock_barang_batch.stock_barang_id')
    ->join('barang', 'barang.id', '=', 'stock_barang.barang_id')
    ->join('jenis_barang', 'jenis_barang.id', '=', 'barang.jenis_barang_id')
    ->whereBetween('transaksi_kasir.tanggal', [$start, $end]);

    if ($idToko && $idToko != 1) {
        $query->where('transaksi_kasir.toko_id', $idToko);
    }

    return $query
        ->groupBy('jenis_barang.id', 'jenis_barang.nama_jenis_barang')
        ->orderBy('jenis_barang.id')
        ->get();
}

public function getPenjualanNonFisik($startDate, $endDate, $idToko = null)
{
    $start = Carbon::parse($startDate)->startOfDay();
    $end = Carbon::parse($endDate)->endOfDay();

    $query = PenjualanNonFisik::with(['dompetKategori', 'createdBy.toko'])
        ->withSum('detail', 'qty')
        ->whereBetween('created_at', [$start, $end]);

    if ($idToko) {
        $query->whereHas('createdBy', function ($q) use ($idToko) {
            $q->where('toko_id', $idToko);
        });
    }

    return $query->get();
}

public function getBiayaRetur($startDate, $endDate, $idToko = null)
{
    $query = ReturMemberDetail::with('retur')
        ->where('tipe_kompensasi', 'refund')
        ->whereBetween('created_at', [$startDate, $endDate]);

    if ($idToko) {
        $query->whereHas('retur', function ($q) use ($idToko) {
            $q->where('toko_id', $idToko);
        });
    }

    return (float) $query->sum(DB::raw('total_refund - harga_jual'));
}

    public function getKasbon($startDate, $endDate, $idToko = null)
    {
        $query = DB::table('kasbon')
            ->join('kasir', 'kasbon.id_kasir', '=', 'kasir.id')
            ->where('kasbon.utang_sisa', '>', 0)
            ->whereBetween('kasir.tgl_transaksi', [$startDate, $endDate]);

        if ($idToko) {
            $query->where('kasir.id_toko', $idToko);
        }

        return (float) ($query->sum('kasbon.utang_sisa') ?? 0);
    }
}
