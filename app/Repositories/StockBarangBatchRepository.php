<?php

namespace App\Repositories;

use App\Models\StockBarangBatch;

class StockBarangBatchRepository
{
    protected $model;

    public function __construct(StockBarangBatch $model)
    {
        $this->model = $model;
    }

    public function getAll($filter)
    {
        $query = $this->model::where('qty_sisa', '>', 0);

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('qrcode', $filter->search)->orWhereHas('stockBarang.barang', function ($q2) use ($filter) {
                    $q2->where('nama', 'like', '%' . $filter->search . '%');
                });
            });
        }

        if (!empty($filter->toko_id)) {
            $query->where('toko_id', $filter->toko_id);
        }

        $query->orderBy('created_at');

        return !empty($filter->limit)
            ? $query->paginate($filter->limit)
            : $query->get();
    }

    public function getByQR($filter)
    {
        $query = $this->model::where('qty_sisa', '>', 0)->where('toko_id', $filter->toko_id);

        if (!empty($filter->search)) {
            $query->where('qrcode', $filter->search);
        }

        $query->orderByDesc('created_at');

        return $query->first();
    }
}
