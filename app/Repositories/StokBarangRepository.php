<?php

namespace App\Repositories;

use App\Models\StockBarang;

class StokBarangRepository
{
    protected $model;

    public function __construct(StockBarang $model)
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

    public function findByBarangId($barangId)
    {
        return $this->model::where('id_barang', $barangId)->first();
    }

    public function find($id)
    {
        return $this->model::where('id', $id)->first();
    }
}
