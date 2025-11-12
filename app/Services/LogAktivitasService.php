<?php

namespace App\Services;

use App\Repositories\LogAktivitasRepository;
use App\Traits\PaginateResponse;

class LogAktivitasService
{
    use PaginateResponse;
    protected $repository;

    public function __construct(LogAktivitasRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll($filter)
    {
        $query = $this->repository->getAll($filter);

        $data = collect($query->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'log_name' => $item->log_name,
                'event' => $item->event,
                'description' => $item->description,
                'message' => $item->message,
                'route' => $item->route,
                'method' => $item->method,
                'ip_address' => $item->ip_address,
                'created_at' => optional($item->created_at)->format('d-m-Y H:i:s'),
                'user' => $item->user->nama ?? null,
                'properties' => $item->properties,
            ];
        });


        return [
            'data' => $data,
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function getLogEvent($limit = 10, $search = null)
    {
        $query = $this->repository->getLogEvent($limit, $search);

        $data = collect($query->items())->map(function ($item) {
            return [
                'id' => $item->log_name . '/' . $item->event,
                'text' => $item->log_name . ' - ' . $item->event,
            ];
        });

        return [
            'data' => $data,
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function model()
    {
        return $this->repository->query();
    }

    public function getById($id)
    {
        return $this->repository->getById($id);
    }
}
