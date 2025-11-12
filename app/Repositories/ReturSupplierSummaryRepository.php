<?php

namespace App\Repositories;

use App\Models\ReturSupplierSummary;
use Illuminate\Validation\ValidationException;

class ReturSupplierSummaryRepository
{
    protected $model;

    public function __construct(ReturSupplierSummary $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function findByDate($tanggal)
    {
        return $this->model->whereDate('tanggal', $tanggal)->first();
    }

    public function update($id, array $data)
    {
        $summary = $this->model->find($id);

        $summary->update($data);

        return $summary->fresh();
    }
}
