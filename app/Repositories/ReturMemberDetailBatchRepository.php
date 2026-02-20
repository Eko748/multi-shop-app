<?php

namespace App\Repositories;

use App\Models\ReturMemberDetailBatch;

class ReturMemberDetailBatchRepository
{
    protected $model;

    public function __construct(ReturMemberDetailBatch $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function bulkInsert(array $data)
    {
        return $this->model->insert($data);
    }

    public function update($id, array $data)
    {
        $stok = $this->model->findOrFail($id);
        $stok->update($data);
        return $stok;
    }

    public function delete($id)
    {
        $stok = $this->model->findOrFail($id);
        return $stok->delete();
    }

    public function findByDetailId($detailId)
    {
        return $this->model->where('retur_member_detail_id', $detailId)->get();
    }
}
