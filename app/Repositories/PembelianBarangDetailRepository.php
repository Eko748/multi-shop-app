<?php

namespace App\Repositories;

use App\Models\DetailPembelianBarang;

class PembelianBarangDetailRepository
{
    protected $model;

    public function __construct(DetailPembelianBarang $model)
    {
        $this->model = $model;
    }

    public function getAll($filter)
    {
        $query = $this->model::query();

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('qrcode', 'like', '%' . $filter->search . '%');
            });
        }

        if (!empty($filter->qrcode)) {
            $query->where('qrcode', $filter->qrcode);
        }

        return !empty($filter->limit)
            ? $query->orderByDesc('id')->paginate($filter->limit)
            : $query->orderByDesc('id')->get();
    }

    public function getQRCode($filter)
    {
        $query = $this->model::with([
            'barang:id,nama_barang',
            'detailStock:id,id_detail_pembelian,qty_now',
        ])
            ->select('id', 'qrcode', 'id_barang', 'status', 'created_at')
            ->where('status', 'success')
            ->whereHas('detailStock', function ($q) {
                $q->where('qty_now', '>', 0);
            });

        if (!empty($filter->search)) {
            $search = $filter->search;
            $query->where(function ($q) use ($search) {
                $q->where('qrcode', 'like', "%{$search}%");
            });
        }

        return !empty($filter->limit)
            ? $query->orderByDesc('id')->paginate($filter->limit)
            : $query->orderByDesc('id')->get();
    }

    public function getHargaBarang($filter)
    {
        $query = $this->model::with([
            'supplier:id,nama_supplier,contact',
            'barang:id,nama_barang',
            'detailStock:id,id_detail_pembelian,id_stock,qty_now',
            'pembelian:id',
        ])
            ->select('id', 'id_supplier', 'qrcode', 'id_barang', 'harga_barang as hpp', 'qty', 'created_at')
            ->where('id', $filter->id);

        return !empty($filter->limit)
            ? $query->orderByDesc('id')->paginate($filter->limit)
            : $query->orderByDesc('id')->get();
    }
}
