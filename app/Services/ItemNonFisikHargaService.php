<?php

namespace App\Services;

use App\Repositories\ItemNonFisikHargaRepository;
use App\Traits\PaginateResponse;

class ItemNonFisikHargaService
{
    use PaginateResponse;
    protected $repository;

    public function __construct(ItemNonFisikHargaRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getItemHarga($limit = 10, $search = null)
    {
        $query = $this->repository->getItemHarga($limit, $search);

        $data = collect($query->items())->map(function ($item) {
            return [
                'id' => $item->public_id,
                'text' => sprintf(
                    '%s - Hpp: %s - Harga Jual: %s - %s',
                    $item->dompetKategori->nama ?? '-',
                    'Rp ' . number_format($item->hpp, 0, ',', '.'),
                    'Rp ' . number_format($item->harga_jual, 0, ',', '.'),
                    $item->item->nama ?? '-'
                ),
                'harga' => $item->harga_jual
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
        return $this->repository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->repository->delete($id);
    }
}
