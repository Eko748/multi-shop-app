<?php

namespace App\Services;

use App\Repositories\DompetKategoriRepository;
use App\Traits\PaginateResponse;

class DompetKategoriService
{
    use PaginateResponse;
    protected $repository;

    public function __construct(DompetKategoriRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll($filter)
    {
        $query = $this->repository->getAll($filter);

        $data = collect($query->items())->map(function ($item) {
            return [
                'id' => $item->public_id,
                'nama' => $item->nama,
                'created_at' => $item->created_at ?? null,
                'created_by' => $item->createdBy->nama ?? 'System',
            ];
        });

        return [
            'data' => $data,
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function getNama($limit = 10, $search = null)
    {
        $query = $this->repository->getNama($limit, $search);

        $data = collect($query->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->nama,
            ];
        });

        return [
            'data' => $data,
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    public function update($id, array $data)
    {
        $existing = $this->repository->getDetailByPublicId($id);

        return $this->repository->update($existing->id, $data);
    }

    public function delete($id, $data)
    {
        $existing = $this->repository->getDetailByPublicId($id);

        return $this->repository->delete($existing->id, $data);
    }

    public function count()
    {
        return $this->repository->count();
    }
}
