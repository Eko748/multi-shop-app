<?php

namespace App\Repositories;

use App\Models\ReturMember;

class ReturMemberRepository
{
    protected $model;

    public function __construct(ReturMember $model)
    {
        $this->model = $model;
    }

    public function getAll($filter)
    {
        $query = $this->model->with(['detail', 'toko', 'member', 'createdBy']);

        if (!empty($filter->start_date) && !empty($filter->end_date)) {
            $query->whereBetween('tanggal', [$filter->start_date, $filter->end_date]);
        }

        if (!empty($filter->search)) {
            $query->where('status', 'like', "%{$filter->search}%");
        }

        return $query->orderByDesc('created_at')->paginate($filter->limit ?? 30);
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
