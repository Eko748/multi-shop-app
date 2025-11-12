<?php

namespace App\Repositories;

use App\Models\ReturSupplierDetail;

class ReturSupplierDetailRepository
{
    protected $model;

    public function __construct(ReturSupplierDetail $model)
    {
        $this->model = $model;
    }

    public function getByRetur($id)
    {
        return $this->model->where('retur_supplier_id', $id)->get();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function getAll($filter)
    {
        $query = $this->model->query()->with('barang:id,nama_barang');

        if (!empty($filter->start_date) && !empty($filter->end_date)) {
            $query->whereBetween('tanggal', [$filter->start_date, $filter->end_date]);
        }

        if (!empty($filter->id)) {
            $query->where('retur_supplier_id', $filter->id);
        }

        $query->orderByDesc('id');

        return !empty($filter->limit)
            ? $query->paginate($filter->limit)
            : $query->get();
    }

    public function find($id)
    {
        $data = $this->model->with('barang')->find($id);

        return $data;
    }

    public function update($id, array $data)
    {
        $detail = $this->find($id);
        $detail->update($data);

        return $detail->fresh('barang');
    }
}
