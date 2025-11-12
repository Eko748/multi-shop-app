<?php

namespace App\Repositories;

use App\Models\Kasir;
use App\Models\PenjualanNonFisik;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LaporanPenjualanRepository
{
    public function getKasirData($startDate, $endDate, $idToko = null)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $query = Kasir::with(['detail_kasir.jenisBarang', 'toko'])
            ->whereBetween('tgl_transaksi', [$start, $end]);

        if ($idToko && $idToko != 1) {
            $query->where('id_toko', $idToko);
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
                $q->where('id_toko', $idToko);
            });
        }

        return $query->get();
    }

    public function getBiayaRetur($startDate, $endDate, $idToko = null)
    {
        $query = DB::table('detail_retur')
            ->leftJoin('data_retur', 'detail_retur.id_retur', '=', 'data_retur.id')
            ->where('detail_retur.metode', 'Cash')
            ->whereBetween('data_retur.tgl_retur', [$startDate, $endDate]);

        if ($idToko) {
            $query->where('data_retur.id_toko', $idToko);
        }

        return (float) ($query->sum(DB::raw('detail_retur.harga - detail_retur.hpp_jual')) ?? 0);
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
