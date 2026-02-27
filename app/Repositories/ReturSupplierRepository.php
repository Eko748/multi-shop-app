<?php

namespace App\Repositories;

use App\Helpers\TextGenerate;
use App\Models\ReturSupplier;

class ReturSupplierRepository
{
    protected $model;

    public function __construct(ReturSupplier $model)
    {
        $this->model = $model;
    }

    public function find($id, $tokoId)
    {
        $data = $this->model
            ->with(['detail', 'supplier', 'toko', 'detail.barang:id,nama'])
            ->where('toko_id', $tokoId)
            ->find($id);

        if ($data) {
            if ($data->tipe_retur === 'pembelian') {
                $data->tipe_retur_text = 'Pembelian Barang';
            } elseif ($data->tipe_retur === 'member') {
                $data->tipe_retur_text = 'Retur Member';
            } else {
                $data->tipe_retur_text = ucfirst($data->tipe_retur);
            }
            $data->no_retur = "R-{$data->id}";

            foreach ($data->detail as $detail) {
                if ($detail->barang) {
                    $detail->barang->nama =
                        TextGenerate::smartTail($detail->barang->nama, 10);
                }
            }
        }


        return $data;
    }

    public function sumHppDanRefund()
    {
        return $this->model->with(['detail'])->get();
    }

    public function getAll($filter)
    {
        $query = $this->model->with(['detail', 'toko', 'supplier', 'createdBy'])->where('toko_id', $filter->toko_id);

        if (!empty($filter->start_date) && !empty($filter->end_date)) {
            $query->whereBetween('tanggal', [$filter->start_date, $filter->end_date]);
        }

        if (!empty($filter->search)) {
            $query->where('status', 'like', "%{$filter->search}%");
        }

        $query->orderByRaw("CASE WHEN status = 'proses' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');

        return $query->paginate($filter->limit ?? 30);
    }

    public function getDetailById($id, $tokoId)
    {
        return $this->model->where('id', $id)->where('toko_id', $tokoId)->first();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $retur = $this->model->findOrFail($id);
        $retur->update($data);
        return $retur;
    }

    public function delete($id, $data)
    {
        $retur = $this->model->findOrFail($id);
        $retur->deleted_by = $data['deleted_by'] ?? null;
        $retur->save();
        return $retur->delete();
    }
}
