<?php

namespace App\Services;

use App\Repositories\CatatanRepo;
use App\Traits\PaginateResponse;

class CatatanService
{
    use PaginateResponse;
    protected $repository;

    public function __construct(CatatanRepo $repository)
    {
        $this->repository = $repository;
    }

    public function getAll($filter)
    {
        $query = $this->repository->getAll($filter);

        $data = collect($query->items())->map(function ($item) use ($filter) {

            $akses = null;

            if ($filter->toko_id == $item->toko_asal_id) {
                $akses = 'sender';
            } elseif ($filter->toko_id == $item->toko_tujuan_id) {
                $akses = 'receiver';
            }

            return [
                'id'            => $item->id,
                'keterangan'    => $item->keterangan,
                'is_read'       => $item->is_read,
                'toko_asal' => optional($item->tokoAsal)->singkatan && optional($item->tokoAsal)->wilayah
                    ? optional($item->tokoAsal)->singkatan . ' - ' . optional($item->tokoAsal)->wilayah
                    : '-',

                'toko_tujuan' => optional($item->tokoTujuan)->singkatan && optional($item->tokoTujuan)->wilayah
                    ? optional($item->tokoTujuan)->singkatan . ' - ' . optional($item->tokoTujuan)->wilayah
                    : '-',
                'toko_tujuan_id'   => $item->toko_tujuan_id ?? null,
                'created_at'    => $item->created_at ?? null,
                'created_by'    => $item->createdBy->nama ?? 'System',
                'read_by'       => $item->readBy->nama ?? null,
                'akses'         => $akses,
            ];
        });

        return [
            'data' => $data,
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function create(array $data)
    {
        $object = [
            'keterangan'       => $data['keterangan'],
            'toko_asal_id'     => $data['toko_id'],
            'toko_tujuan_id'   => $data['toko_tujuan_id'],
            'created_by'       => $data['user_id'],
        ];

        return $this->repository->create($object);
    }

    public function update($id, array $data)
    {
        $object = [
            'keterangan'       => $data['keterangan'],
            'toko_asal_id'     => $data['toko_id'],
            'toko_tujuan_id'   => $data['toko_tujuan_id'],
            'updated_by'       => $data['user_id'],
        ];

        return $this->repository->update($id, $object);
    }

    public function read($id, array $data)
    {
        $object = [
            'is_read'   => $data['is_read'],
            'read_by'   => $data['user_id'],
        ];

        return $this->repository->read($id, $object);
    }

    public function delete($id, $data)
    {
        $object = [
            'deleted_by'   => $data['user_id'],
        ];

        return $this->repository->delete($id, $object);
    }
}
