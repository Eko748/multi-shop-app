<?php

namespace App\Repositories;

use App\Models\TransaksiKasirDetail;

class KasirDetailRepository
{
    protected $model;

    public function __construct(TransaksiKasirDetail $model)
    {
        $this->model = $model;
    }

    public function getAll($filter)
    {
        $query = $this->model::query();

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('nama', 'like', '%' . $filter->search . '%');
            });
        }

        if (!empty($filter->nama)) {
            $query->where('nama', $filter->nama);
        }

        return !empty($filter->limit)
            ? $query->orderByDesc('id')->paginate($filter->limit)
            : $query->orderByDesc('id')->get();
    }

    public function getQRCode($filter)
    {
        $query = $this->model::with([
            'stockBarangBatch:id,stock_barang_id',
            'stockBarangBatch.stockBarang:id,barang_id',
            'stockBarangBatch.stockBarang.barang:id,nama',
            'transaksiKasir:id,member_id'
        ])
            ->selectRaw('id, qrcode, stock_barang_batch_id, transaksi_kasir_id, qty, retur_qty, (qty - IFNULL(retur_qty, 0)) as qty_selisih')
            ->whereRaw('qty > IFNULL(retur_qty, 0)');

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('qrcode', 'like', '%' . $filter->search . '%');
            });
        }

        if (isset($filter->member_id) && $filter->member_id !== '') {
            $query->whereHas('transaksiKasir', function ($q) use ($filter) {

                if ($filter->member_id === 'guest') {
                    $q->whereNull('member_id');
                } else {
                    $q->where('member_id', (string) $filter->member_id);
                }
            });
        }

        return !empty($filter->limit)
            ? $query->orderByDesc('id')->paginate($filter->limit)
            : $query->orderByDesc('id')->get();
    }

    public function getHargaBarang($filter)
    {
        $query = $this->model::with([
            'stockBarangBatch:id,stock_barang_id,harga_beli,supplier_id,qty_sisa',
            'stockBarangBatch.stockBarang:id,barang_id,stok',
            'stockBarangBatch.stockBarang.barang:id,nama',
            'transaksiKasir:id,member_id'
        ])
            ->selectRaw('
            id,
            qrcode,
            stock_barang_batch_id, transaksi_kasir_id,
            qty,
            retur_qty,
            nominal,
            (qty - IFNULL(retur_qty, 0)) as qty_selisih
        ')
            ->where('id', $filter->id);

        return !empty($filter->limit)
            ? $query->orderByDesc('id')->paginate($filter->limit)
            : $query->orderByDesc('id')->get();
    }

    public function findById($id)
    {
        return $this->model::where('id', $id)->first();
    }

    public function findByDetailId($detailId)
    {
        return $this->model::where('id', $detailId)->get();
    }

    public function update($id, array $data)
    {
        $item = $this->model::where('id', $id);
        if ($item) {
            $item->update($data);
        }
        return $item;
    }
}
