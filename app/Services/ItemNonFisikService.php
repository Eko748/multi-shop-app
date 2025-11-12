<?php

namespace App\Services;

use App\Repositories\DompetKategoriRepository;
use App\Repositories\ItemNonFisikRepository;
use App\Traits\PaginateResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class ItemNonFisikService
{
    use PaginateResponse;
    protected $repository;
    protected $repository2;

    public function __construct(ItemNonFisikRepository $repository, DompetKategoriRepository $repository2)
    {
        $this->repository = $repository;
        $this->repository2 = $repository2;
    }

    public function getAll($filter)
    {
        $query = $this->repository->getAll($filter);

        $data = collect($query->items())->map(function ($item) {
            return [
                'id' => $item->public_id,
                'nama' => $item->nama,
                'tipe' => $item->tipe->nama ?? 'Tidak ada tipe',
                'tipe_id' => $item->tipe->id ?? null,
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
        return DB::transaction(function () use ($data) {
            $mainData = [
                'nama' => $data['nama'],
                'item_nonfisik_tipe_id' => $data['item_nonfisik_tipe_id'],
                'created_by' => $data['created_by'],
            ];

            $itemNonfisik = $this->repository->create($mainData);

            $kategoriList = $this->repository2->getAll((object) [
                'limit' => null,
            ]);

            return $itemNonfisik;
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $mainData = [
                'nama' => $data['nama'],
                'item_nonfisik_tipe_id' => $data['item_nonfisik_tipe_id'],
                'updated_by' => $data['updated_by'],
            ];

            $existing = $this->repository->getDetailByPublicId($id);
            if (!$existing) {
                throw ValidationException::withMessages([
                    'public_id' => "Item nonfisik dengan ID {$id} tidak ditemukan.",
                ]);
            }

            $itemNonfisik = $this->repository->update($existing->id, $mainData);

            return $itemNonfisik;
        });
    }

    public function delete($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $existing = $this->repository->getDetailByPublicId($id);

            return $this->repository->delete($existing->id, $data);
        });
    }
}
