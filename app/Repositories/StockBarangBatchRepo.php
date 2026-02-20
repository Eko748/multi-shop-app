<?php

namespace App\Repositories;

use App\Models\StockBarangBatch;

class StockBarangBatchRepo
{
    protected $model;

    public function __construct(StockBarangBatch $model)
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
            'stockBarang:id,barang_id',
            'stockBarang.barang:id,nama',
        ])
            ->select('id', 'qrcode', 'qty_sisa', 'stock_barang_id', 'created_at')
            ->where('qty_sisa', '>', 0);

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
            'supplier:id,nama,telepon',
            'stockBarang.barang:id,nama',
        ])
            ->select('id', 'supplier_id', 'stock_barang_id', 'harga_beli as hpp', 'qty_sisa', 'created_at')
            ->where('id', $filter->id);

        return !empty($filter->limit)
            ? $query->orderByDesc('id')->paginate($filter->limit)
            : $query->orderByDesc('id')->get();
    }
}
