<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class StokRepository
{
    /**
     * Dapatkan data stok keseluruhan: total qty & total harga.
     */
    public function getStokData(int $month, int $year): object
    {
        return DB::table('detail_stock as ds')
            ->whereMonth('ds.created_at', '<=', $month)
            ->whereYear('ds.created_at', $year)
            ->join('detail_pembelian_barang as dpb', 'ds.id_detail_pembelian', '=', 'dpb.id')
            ->join('pembelian_barang as pb', 'pb.id', '=', 'dpb.id_pembelian_barang')
            ->join('barang as b', 'ds.id_barang', '=', 'b.id')
            ->join('jenis_barang as jb', 'b.id_jenis_barang', '=', 'jb.id')
            ->join('stock_barang as sb', function ($join) {
                $join->on('sb.id', '=', 'ds.id_stock')
                    ->on('sb.id_barang', '=', 'ds.id_barang');
            })
            ->whereNull('ds.deleted_at')
            ->whereNull('dpb.deleted_at')
            ->whereNull('pb.deleted_at')
            ->whereNull('b.deleted_at')
            ->whereNull('sb.deleted_at')
            ->select(
                DB::raw('SUM(ds.qty_now) as total_qty'),
                DB::raw('SUM(ds.qty_now * dpb.harga_barang) as total_harga')
            )
            ->first();
    }

    /**
     * Dapatkan data stok per jenis barang: total qty & total harga tiap jenis.
     */
    public function getStokPerJenis(int $month, int $year)
    {
        return DB::table('detail_stock as ds')
            ->join('detail_pembelian_barang as dpb', 'ds.id_detail_pembelian', '=', 'dpb.id')
            ->join('barang as b', 'ds.id_barang', '=', 'b.id')
            ->join('jenis_barang as jb', 'b.id_jenis_barang', '=', 'jb.id')
            ->whereNull('ds.deleted_at')
            ->whereNull('dpb.deleted_at')
            ->whereNull('b.deleted_at')
            ->where(function ($query) use ($month, $year) {
                // Ambil semua stok sampai bulan & tahun yang diminta
                $query->whereYear('ds.created_at', '<', $year)
                    ->orWhere(function ($sub) use ($month, $year) {
                        $sub->whereYear('ds.created_at', '=', $year)
                            ->whereMonth('ds.created_at', '<=', $month);
                    });
            })
            ->select(
                'jb.id as id_jenis_barang',
                'jb.nama_jenis_barang',
                DB::raw('SUM(ds.qty_now) as total_qty'),
                DB::raw('SUM(ds.qty_now * dpb.harga_barang) as total_harga')
            )
            ->groupBy('jb.id', 'jb.nama_jenis_barang')
            ->get();
    }
}
