<?php

namespace App\Repositories;

use App\Models\ItemNonFisik;

class ItemNonFisikRepository
{
    protected $model;

    public function __construct(ItemNonFisik $model)
    {
        $this->model = $model;
    }

    public function getAll($filter)
    {
        $query = $this->model::orderByDesc('created_at');

        if (!empty($filter->start_date) && !empty($filter->end_date)) {
            $query->whereBetween('created_at', [$filter->start_date, $filter->end_date]);
        }

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('nama', 'like', '%' . $filter->search . '%');
            });
        }

        if (!empty($filter->nama)) {
            $query->where('nama', $filter->nama);
        }

        return $query->paginate($filter->limit ?? 10);
    }

    public function getNama($limit = 10, $search = null)
    {
        $query = $this->model::select('id', 'nama', 'created_at')
            ->orderByDesc('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%');
            });
        }

        return $query->paginate($limit);
    }

    public function getDetailByPublicId($id)
    {
        return $this->model::where('public_id', $id)->first();
    }

    public function create(array $data)
    {
        return $this->model::create($data);
    }

    public function update($id, array $data)
    {
        $item = $this->model::find($id);
        if ($item) {
            $item->update($data);
        }
        return $item;
    }

    public function delete($id, $data)
    {
        $item = $this->model::find($id);
        if ($item) {
            $item->update($data);
        }
        return $item ? $item->delete() : false;
    }
}
