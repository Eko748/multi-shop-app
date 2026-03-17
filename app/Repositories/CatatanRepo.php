<?php

namespace App\Repositories;

use App\Models\Catatan;

class CatatanRepo
{
    protected $model;

    public function __construct(Catatan $model)
    {
        $this->model = $model;
    }

    public function getAll($filter)
    {
        $query = $this->model::query();

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('keterangan', 'like', '%' . $filter->search . '%');
            });
        }

        if (!empty($filter->toko_id)) {
            $query->where(function ($q) use ($filter) {
                $q->where('toko_asal_id', $filter->toko_id)
                    ->orWhere('toko_tujuan_id', $filter->toko_id);
            });
        }

        if (!is_null($filter->is_read ?? null)) {
            $query->where('is_read', $filter->is_read);
        }

        if (!empty($filter->toko_id)) {
            $query->orderByRaw("
            CASE
                WHEN is_read = 0 AND toko_tujuan_id = ? THEN 0
                WHEN is_read = 0 THEN 1
                WHEN toko_tujuan_id = ? THEN 2
                ELSE 3
            END
        ", [$filter->toko_id, $filter->toko_id]);
        }

        $query->orderByDesc('id');

        return !empty($filter->limit)
            ? $query->paginate($filter->limit)
            : $query->get();
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

    public function read($id, array $data)
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
