<?php

namespace App\Services\TransaksiBarang;

use App\Repositories\TransaksiBarang\TransaksiKasirRepo;
use App\Traits\PaginateResponse;

class TransaksiKasirService
{
    use PaginateResponse;
    protected $repository;

    public function __construct(TransaksiKasirRepo $repository)
    {
        $this->repository = $repository;
    }

    public function getAll($filter)
    {
        $query = $this->repository->getAll($filter);

        $data = collect(method_exists($query, 'items') ? $query->items() : $query)->map(function ($item) {
            return [
                'id' => $item->public_id,
                'nota' => $item->nota,
                'qty' => $item->total_qty,
                'nominal' => $item->total_nominal,
                'tanggal' => $item->tanggal->format('d-m-Y H:i:s'),
                'created_at' => $item->created_at ?? null,
                'created_by' => $item->createdBy->nama ?? 'System',
            ];
        });

        return [
            'data' => [
                'item' => $data,
                'total' => $this->repository->sumNominal($filter)
            ],
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function getTotalNominal($filter)
    {
        return $this->repository->sumNominal($filter);
    }

    public function getNota($limit = 10, $search = null)
    {
        $query = $this->repository->getNota($limit, $search);

        $data = collect($query->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->nota,
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

    public function count()
    {
        return $this->repository->count();
    }
}
