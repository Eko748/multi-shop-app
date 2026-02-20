<?php

namespace App\Repositories;

use App\Models\PembelianBarangDetail;
use App\Models\StockBarangBatch;

class StokBarangDetailRepository
{
    protected $model;

    public function __construct(StockBarangBatch $model)
    {
        $this->model = $model;
    }

    public function update($id, array $data)
    {
        $item = $this->model::where('id', $id);
        if ($item) {
            $item->update($data);
        }
        return $item;
    }

    public function updateWithPembelian($id, array $data)
    {
        $item = $this->model::where('sumber_id', $id)
            ->where('sumber_type', PembelianBarangDetail::class);
        if ($item) {
            $item->update($data);
        }
        return $item;
    }

    public function findByPembelian($id)
    {
        return $this->model::where('sumber_id', $id)
            ->where('sumber_type', PembelianBarangDetail::class)->where('qty_sisa', '>', 0)->first();
    }

    public function findByPembelianWithZero($id)
    {
        return $this->model::where('sumber_id', $id)
            ->where('sumber_type', PembelianBarangDetail::class)
            ->first();
    }

    public function findAvailableByBarangId($barangId, $tokoId)
    {
        return $this->model::whereHas('stockBarang', function ($q) use ($barangId) {
            $q->where('barang_id', $barangId);
        })
            ->where('qty_sisa', '>', 0)
            ->where('toko_id', $tokoId)
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
