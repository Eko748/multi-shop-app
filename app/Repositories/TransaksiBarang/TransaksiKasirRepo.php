<?php

namespace App\Repositories\TransaksiBarang;

use App\Models\TransaksiKasir;
use Illuminate\Support\Carbon;

class TransaksiKasirRepo
{
    protected $model;

    public function __construct(TransaksiKasir $model)
    {
        $this->model = $model;
    }

    public function sumNominal($filter)
    {
        $query = $this->model;

        if (!empty($filter->start_date) && !empty($filter->end_date)) {
            $query->whereBetween('tanggal', [$filter->start_date, $filter->end_date]);
        } else {
            $query->whereDate('tanggal', Carbon::today());
        }

        return $query->sum('total_nominal');
    }

    public function getAll($filter)
    {
        $query = $this->model::query();

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('nota', 'like', '%' . $filter->search . '%');
            });
        }

        if (!empty($filter->toko_id)) {
            $query->where('toko_id', $filter->toko_id);
        }

        if (!empty($filter->nota)) {
            $query->where('nota', $filter->nota);
        }

        if (!empty($filter->start_date) && !empty($filter->end_date)) {
            $query->whereBetween('tanggal', [$filter->start_date, $filter->end_date]);
        } else {
            $query->whereDate('tanggal', Carbon::today());
        }

        return !empty($filter->limit)
            ? $query->orderByDesc('id')->paginate($filter->limit)
            : $query->orderByDesc('id')->get();
    }

    public function getNota($limit = 10, $search = null)
    {
        $query = $this->model::select('id', 'nota', 'created_at')
            ->orderByDesc('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nota', 'like', '%' . $search . '%');
            });
        }

        return $query->paginate($limit);
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

    public function count()
    {
        return $this->model::count();
    }
}
