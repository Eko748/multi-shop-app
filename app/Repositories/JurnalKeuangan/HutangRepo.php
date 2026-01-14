<?php

namespace App\Repositories\JurnalKeuangan;

use App\Models\Hutang;

class HutangRepo
{
    protected $model;

    public function __construct(Hutang $model)
    {
        $this->model = $model;
    }

    public function getAll($filter)
    {
        $query = $this->model;

        if (!empty($filter->start_date) && !empty($filter->end_date)) {
            $query->whereBetween('tanggal', [$filter->start_date, $filter->end_date]);
        }

        if (!empty($filter->search)) {
            $query->where('status', 'like', "%{$filter->search}%");
        }

        $query->orderByDesc('created_at');

        return !empty($filter->limit)
            ? $query->paginate($filter->limit)
            : $query->get();
    }

    public function getDetailById($id)
    {
        return $this->model->where('id', $id)->first();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $retur = $this->model->findOrFail($id);
        $retur->update($data);
        return $retur;
    }

    public function delete($id, $data)
    {
        $retur = $this->model->findOrFail($id);
        $retur->deleted_by = $data['deleted_by'] ?? null;
        $retur->save();
        return $retur->delete();
    }
}
