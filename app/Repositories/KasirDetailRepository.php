<?php

namespace App\Repositories;

use App\Models\DetailKasir;

class KasirDetailRepository
{
    protected $model;

    public function __construct(DetailKasir $model)
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
            'barang:id,nama_barang',
            'kasir:id,id_member'
        ])
            ->selectRaw('id, qrcode, id_barang, id_kasir, qty, reture_qty, (qty - IFNULL(reture_qty, 0)) as qty_selisih')
            ->whereRaw('qty > IFNULL(reture_qty, 0)');

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('qrcode', 'like', '%' . $filter->search . '%');
            });
        }

        if (isset($filter->member_id) && $filter->member_id !== '') {
            $memberId = $filter->member_id === 'Guest' ? '0' : $filter->member_id;
            $query->whereHas('kasir', function ($q) use ($memberId) {
                $q->where('id_member', (string) $memberId);
            });
        }

        return !empty($filter->limit)
            ? $query->orderByDesc('id')->paginate($filter->limit)
            : $query->orderByDesc('id')->get();
    }

    public function getHargaBarang($filter)
    {
        $query = $this->model::with([
            'barang:id,nama_barang',
            'detailPembelian:id,harga_barang',
            'detailPembelian.detailStock:id,id_detail_pembelian,id_stock,qty_now',
            'detailPembelian.detailStock.stok:id,stock'
        ])
            ->selectRaw('
            id,
            qrcode,
            id_supplier,
            id_barang,
            id_detail_pembelian,
            harga,
            qty,
            reture_qty,
            (qty - IFNULL(reture_qty, 0)) as qty_selisih
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
