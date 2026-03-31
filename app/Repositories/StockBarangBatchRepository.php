<?php

namespace App\Repositories;

use App\Models\StockBarangBatch;

class StockBarangBatchRepository
{
    protected $model;

    public function __construct(StockBarangBatch $model)
    {
        $this->model = $model;
    }

    public function getAll($filter)
    {
        $table = $this->model->getTable();

        $query = $this->model
            ->selectRaw("
            barang.qrcode,
            barang.nama,
            MAX({$table}.created_at) as created_at,
            SUM({$table}.qty_sisa) as total_qty
        ")
            ->join('stock_barang', 'stock_barang.id', '=', "{$table}.stock_barang_id")
            ->join('barang', 'barang.id', '=', 'stock_barang.barang_id')
            ->where("{$table}.qty_sisa", '>', 0);

        // =========================
        // FILTER SEARCH
        // =========================
        if (!empty($filter->search)) {
            $search = strtolower($filter->search);

            $query->where(function ($q) use ($search) {
                $q->where('barang.qrcode', $search)
                    ->orWhereRaw('LOWER(barang.nama) LIKE ?', ["%{$search}%"]);
            });
        }

        // =========================
        // FILTER TOKO
        // =========================
        if (!empty($filter->toko_id)) {
            $query->where("{$table}.toko_id", $filter->toko_id);
        }

        // =========================
        // GROUPING
        // =========================
        $query->groupBy('barang.qrcode', 'barang.nama')
            ->orderByDesc('created_at');

        return !empty($filter->limit)
            ? $query->paginate($filter->limit)
            : $query->get();
    }

    public function getByQR($filter)
    {
        $query = $this->model
            ->selectRaw('barang.qrcode, SUM(qty_sisa) as total_qty')
            ->join('stock_barang', 'stock_barang.id', '=', $this->model->getTable() . '.stock_barang_id')
            ->join('barang', 'barang.id', '=', 'stock_barang.barang_id')
            ->where($this->model->getTable() . '.qty_sisa', '>', 0)
            ->where($this->model->getTable() . '.toko_id', $filter->toko_id);

        if (!empty($filter->search)) {
            $query->where('barang.qrcode', $filter->search);
        }

        $query->groupBy('barang.qrcode')
            ->orderByDesc('total_qty');

        return $query->get();
    }

    public function getDetailByQR($filter)
    {
        return $this->model
            ->with(['stockBarang.barang'])
            ->where('qty_sisa', '>', 0)
            ->where('toko_id', $filter->toko_id)
            ->whereHas('stockBarang.barang', function ($q) use ($filter) {
                $q->where('qrcode', $filter->search);
            })
            ->orderByDesc('created_at')
            ->first();
    }

    public function getTotalQtyByQR($filter)
    {
        return $this->model
            ->selectRaw('SUM(qty_sisa) as total_qty')
            ->join('stock_barang', 'stock_barang.id', '=', $this->model->getTable() . '.stock_barang_id')
            ->join('barang', 'barang.id', '=', 'stock_barang.barang_id')
            ->where('barang.qrcode', $filter->search)
            ->where($this->model->getTable() . '.toko_id', $filter->toko_id)
            ->value('total_qty');
    }
}
