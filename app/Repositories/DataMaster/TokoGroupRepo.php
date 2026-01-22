<?php

namespace App\Repositories\DataMaster;

use App\Models\TokoGroup;

class TokoGroupRepo
{
    protected $model;

    public function __construct(TokoGroup $model)
    {
        $this->model = $model;
    }

    public function getForSelect($filter)
    {
        $query = $this->model->newQuery();

        if (!empty($filter->toko_id)) {
            $query->whereHas('toko', function ($q) use ($filter) {
                $q->where('toko_id', $filter->toko_id);
            });
        }

        if (!empty($filter->search)) {
            $query->where('nama', 'like', '%' . $filter->search . '%');
        }

        return !empty($filter->limit)
            ? $query->orderBy('nama')->paginate($filter->limit)
            : $query->orderBy('nama')->get();
    }
}

