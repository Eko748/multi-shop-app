<?php

namespace App\Repositories\Distribusi;

use App\Models\PengirimanBarangDetailTemp;
use Illuminate\Support\Facades\DB;

class PengirimanBarangDetailTempRepo
{
    protected $model;

    public function __construct(PengirimanBarangDetailTemp $model)
    {
        $this->model = $model;
    }

    public function getAll($filter)
    {
        $query = $this->model::query()->where('pengiriman_barang_id', $filter->id);

        if (!empty($filter->search)) {
            $query->whereHas('barang', function ($q) use ($filter) {
                $q->where('nama', 'like', '%' . $filter->search . '%');
            });
        }

        return !empty($filter->limit)
            ? $query->orderByDesc('id')->paginate($filter->limit)
            : $query->orderByDesc('id')->get();
    }

    public function sumHargaBeli($filter)
    {
        $query = $this->model::query()->where('pengiriman_barang_id', $filter->id)
            ->join('stock_barang_batch as batches', 'batches.id', '=', 'pengiriman_barang_detail_temp.stock_barang_batch_id');

        if (!empty($filter->search)) {
            $query->whereHas('barang', function ($q) use ($filter) {
                $q->where('nama', 'like', '%' . $filter->search . '%');
            });
        }

        return $query->selectRaw("
            SUM(pengiriman_barang_detail_temp.qty_send * batches.harga_beli) as total_send,
            SUM(pengiriman_barang_detail_temp.qty_verified * batches.harga_beli) as total_verified,
            SUM((pengiriman_barang_detail_temp.qty_send - pengiriman_barang_detail_temp.qty_verified) * batches.harga_beli) as total_selisih
        ")
            ->first();
    }

    public function delete($id)
    {
        $query = $this->model::findOrFail($id);

        return $query->delete();
    }
}
