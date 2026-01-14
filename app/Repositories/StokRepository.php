<?php

namespace App\Repositories;

use App\Models\StockBarangBatch;
use Illuminate\Support\Facades\DB;

class StokRepository
{
    public function getStokData($tokoId, int $month, int $year): object
    {
        return StockBarangBatch::whereHas('stockBarang', function ($q) use ($tokoId) {
            $q->when(
                $tokoId !== null && $tokoId !== 'all' && $tokoId != 0,
                fn($x) => $x->where('toko_id', $tokoId)
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
        return StockBarangBatch::whereHas('stockBarang', function ($q) use ($tokoId) {
            $q->when(
                $tokoId !== null && $tokoId !== 'all' && $tokoId != 0,
                fn($x) => $x->where('toko_id', $tokoId)
            );
        })
            ->with(['stockBarang.barang.jenis'])
            ->where(function ($query) use ($month, $year) {
                $query->whereYear('created_at', '<', $year)
                    ->orWhere(function ($sub) use ($month, $year) {
                        $sub->whereYear('created_at', $year)
                            ->whereMonth('created_at', '<=', $month);
                    });
            })
            ->get()
            ->groupBy(fn($item) => $item->stockBarang->barang->jenis->id)
            ->map(function ($group) {

                $first  = $group->first();
                $jenis  = $first->stockBarang->barang->jenis;

                return [
                    'id_jenis_barang'   => $jenis->id,
                    'nama_jenis_barang' => $jenis->nama_jenis_barang,
                    'total_qty'         => $group->sum('qty_sisa'),
                    'total_harga'       => $group->sum(fn($i) => $i->qty_sisa * $i->stockBarang->hpp_baru),
                ];
            })
            ->values();
    }
}
