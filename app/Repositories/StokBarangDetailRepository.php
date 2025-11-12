<?php

namespace App\Repositories;

use App\Models\DetailStockBarang;

class StokBarangDetailRepository
{
    protected $model;

    public function __construct(DetailStockBarang $model)
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
        $item = $this->model::where('id_detail_pembelian', $id);
        if ($item) {
            $item->update($data);
        }
        return $item;
    }

    public function findByPembelian($id)
    {
        return $this->model::where('id_detail_pembelian', $id)->where('qty_now', '>', 0)->first();
    }

    public function findByPembelianWithZero($id)
    {
        return $this->model::where('id_detail_pembelian', $id)->first();
    }

    public function findAvailableByBarangId($barangId)
    {
        return $this->model::where('id_barang', $barangId)
            ->where('qty_now', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
