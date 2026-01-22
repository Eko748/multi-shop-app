<?php

namespace App\Services\DataMaster;

use App\Repositories\DataMaster\TokoGroupRepo;
use App\Traits\PaginateResponse;

class TokoGroupService
{
    use PaginateResponse;

    protected $repository;

    public function __construct(TokoGroupRepo $repository)
    {
        $this->repository = $repository;
    }

    public function select($filter)
    {
        $query = $this->repository->getForSelect($filter);

        $items = collect(method_exists($query, 'items') ? $query->items() : $query)
            ->map(function ($item) {
                return [
                    'id'   => $item->id,
                    'text' => $item->nama,
                ];
            });

        return [
            'data' => $items,
            'pagination' => $this->setPaginate($query),
        ];
    }
}

