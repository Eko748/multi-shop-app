<?php

namespace App\Services;

use App\Repositories\NeracaRepository;
use App\Traits\PaginateResponse;
use Carbon\Carbon;

class NeracaService
{
    use PaginateResponse;
    protected $repository;

    public function __construct(NeracaRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll($limit = 12, $year = null, $month = null)
    {
        $query = $this->repository->getAll($limit, $year, $month);

        $data = collect($query->items())->map(function ($item) {
            $carbonTanggal = Carbon::parse($item->tanggal);

            return [
                'id' => $item->id,
                'nilai' => rtrim(rtrim(number_format($item->nilai, 10, '.', ''), '0'), '.'),
                'tanggal' => $item->tanggal,
                'read_tanggal' => 'Bulan ' . $carbonTanggal->translatedFormat('F Y'),
                'created_by' => $item->created_by,
                'updated_by' => $item->updated_by,
                'created_at' => optional($item->created_at)->format('d-m-Y H:i:s'),
                'updated_at' => optional($item->updated_at)->format('d-m-Y H:i:s'),
                'creator_name' => $item->creator->nama ?? null,
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

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    public function update($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->repository->delete($id);
    }
}
