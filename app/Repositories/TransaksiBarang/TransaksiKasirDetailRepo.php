<?php

namespace App\Repositories\TransaksiBarang;

use App\Models\TransaksiKasirDetail;
use Illuminate\Support\Carbon;

class TransaksiKasirDetailRepo
{
    protected $model;

    public function __construct(TransaksiKasirDetail $model)
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

        return $query->sum('nominal');
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
