<?php

namespace App\Repositories;

use App\Models\TransaksiKasir;
use App\Models\PenjualanNonFisik;
use App\Models\ReturMemberDetail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LaporanPenjualanRepository
{
    public function getKasirData($startDate, $endDate, $idToko = null)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $query = TransaksiKasir::with(['details.stockBarangBatch.stockBarang.barang.jenis', 'toko'])
            ->whereBetween('tanggal', [$start, $end]);

        if ($idToko && $idToko != 1) {
            $query->where('toko_id', $idToko);
        }

        return $query->get();
    }

    public function getPenjualanNonFisik($startDate, $endDate, $idToko = null)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $query = PenjualanNonFisik::with(['dompetKategori', 'createdBy.toko'])->withSum('detail', 'qty')
            ->whereBetween('created_at', [$start, $end]);

        if ($idToko && $idToko != 1) {
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

        return (float) ($query->sum(DB::raw('total_refund - harga_jual')) ?? 0);
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
