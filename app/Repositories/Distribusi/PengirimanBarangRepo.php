<?php

namespace App\Repositories\Distribusi;

use App\Models\PengirimanBarang;
use App\Models\PengirimanBarangDetail;

class PengirimanBarangRepo
{
    protected $model;

    public function __construct(PengirimanBarang $model)
    {
        $this->model = $model;
    }

    public function getAll($filter)
    {
        $query = $this->model->newQuery();

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('no_resi', 'like', '%' . $filter->search . '%');
            });
        }

        if (!empty($filter->start_date) && !empty($filter->end_date)) {
            $query->whereBetween('send_at', [$filter->start_date, $filter->end_date]);
        }

        return !empty($filter->limit)
            ? $query->orderByDesc('id')->paginate($filter->limit)
            : $query->orderByDesc('id')->get();
    }

    public function getStokPengirimanBarang(int $month, int $year, int $idToko): array
    {
        $details = PengirimanBarangDetail::where('qty_send', '>', 0)
            ->whereHas('pengirimanBarang', function ($q) use ($idToko, $month, $year) {
                $q->where('status', 'progress')
                    ->where('toko_asal_id', $idToko)
                    ->whereMonth('send_at', $month)
                    ->whereYear('send_at', $year);
            })
            ->with('batch:id,harga_beli')
            ->get();

        $totalQty = $details->sum('qty_send');

        $totalHarga = $details->sum(function ($item) {
            return ($item->batch->harga_beli ?? 0) * $item->qty_send;
        });

        return [
            'total_qty'   => $totalQty,
            'total_harga' => $totalHarga,
        ];
    }

    public function getLaporan($filter)
    {
        $query = $this->model->newQuery()
            ->with('tokoAsal', 'tokoTujuan')
            ->leftJoin('pengiriman_barang_detail as pbd', 'pbd.pengiriman_barang_id', '=', 'pengiriman_barang.id')
            ->leftJoin('stock_barang_batch as b', 'b.id', '=', 'pbd.stock_barang_batch_id')
            ->selectRaw('
            pengiriman_barang.toko_asal_id,
            pengiriman_barang.toko_tujuan_id,
            SUM(pbd.qty_send) as total_qty,
            SUM(pbd.qty_send * b.harga_beli) as total_nominal,
            COUNT(DISTINCT pengiriman_barang.id) as total_transaksi
        ')
            ->groupBy('pengiriman_barang.toko_asal_id', 'pengiriman_barang.toko_tujuan_id');

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->whereHas('tokoAsal', function ($q2) use ($filter) {
                    $q2->where('nama', 'like', '%' . $filter->search . '%');
                })
                    ->orWhereHas('tokoTujuan', function ($q2) use ($filter) {
                        $q2->where('nama', 'like', '%' . $filter->search . '%');
                    });
            });
        }

        if (!empty($filter->start_date) && !empty($filter->end_date)) {
            $query->whereBetween('pengiriman_barang.send_at', [$filter->start_date, $filter->end_date]);
        }

        return !empty($filter->limit)
            ? $query->orderByDesc('total_nominal')->paginate($filter->limit)
            : $query->orderByDesc('total_nominal')->get();
    }
}
