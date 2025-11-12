<?php

namespace App\Repositories;

use App\Models\NeracaPenyesuaian;

class NeracaRepository
{
    protected $model;

    public function __construct(NeracaPenyesuaian $model)
    {
        $this->model = $model;
    }

    public function query()
    {
        return $this->model->newQuery();
    }

    public function getAll($limit = 12, $year = null, $month = null)
    {
        $query = $this->model::with('creator')
            ->orderBy('tanggal', 'asc');

        if ($year && $month) {
            $query->whereYear('tanggal', $year)
                ->whereMonth('tanggal', $month);
        } elseif ($year) {
            $query->whereYear('tanggal', $year);
        } elseif ($month) {
            $query->whereMonth('tanggal', $month);
        }

        return $query->paginate($limit);
    }

    public function getById($id)
    {
        return $this->model::find($id);
    }

    public function create(array $data)
    {
        $data['created_by'] = $data['user_id'] ?? null;
        $data['updated_by'] = $data['user_id'] ?? null;
        return $this->model::create($data);
    }

    public function update($id, array $data)
    {
        $item = $this->model::find($id);
        if ($item) {
            $data['updated_by'] = $data['user_id'] ?? null;
            $item->update($data);
        }
        return $item;
    }

    public function delete($id)
    {
        $item = $this->model::find($id);
        return $item ? $item->delete() : false;
    }
}
