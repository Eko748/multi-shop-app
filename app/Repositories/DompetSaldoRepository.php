<?php

namespace App\Repositories;

use App\Models\DompetSaldo;

class DompetSaldoRepository
{
    protected $model;

    public function __construct(DompetSaldo $model)
    {
        $this->model = $model;
    }

    public function sumSaldo(?int $month  = null, ?int $year  = null, ?int $tokoId = null)
    {
        $query = $this->model;

        if ($month && $year) {
            $query->whereYear('created_at', $year)
                ->whereMonth('created_at', '<=', $month);
        }

        return $query->where('toko_id', $tokoId)->sum('saldo');
    }

    public function sumHargaBeli(?int $month  = null, ?int $year  = null, ?int $tokoId = null)
    {
        $query = $this->model;

        if ($month && $year) {
            $query->whereYear('created_at', $year)
                ->whereMonth('created_at', '<=', $month);
        }

        return $query->where('toko_id', $tokoId)->sum('harga_beli');
    }

    public function getAll($filter)
    {
        $query = $this->model::with('dompetKategori');

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('saldo', 'like', '%' . $filter->search . '%')
                    ->orWhere('harga_beli', 'like', '%' . $filter->search . '%')
                    ->orWhereHas('dompetKategori', function ($q2) use ($filter) {
                        $q2->where('nama', 'like', '%' . $filter->search . '%');
                    });
            });
        }

        if (!empty($filter->saldo)) {
            $query->where('saldo', $filter->saldo);
        }

        if (!empty($filter->month)) {
            $query->whereMonth('created_at', $filter->month);
        }

        if (!empty($filter->year)) {
            $query->whereYear('created_at', $filter->year);
        }

        $query->orderByDesc('created_at');

        return !empty($filter->limit)
            ? $query->paginate($filter->limit)
            : $query->get();
    }

    public function getTotalPerKategori($filter)
    {
        $query = $this->model::with('dompetKategori')
            ->selectRaw('dompet_kategori_id, SUM(saldo) as total_saldo, SUM(harga_beli) as total_harga_beli')
            ->groupBy('dompet_kategori_id');

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('saldo', 'like', '%' . $filter->search . '%')
                    ->orWhere('harga_beli', 'like', '%' . $filter->search . '%')
                    ->orWhereHas('dompetKategori', function ($q2) use ($filter) {
                        $q2->where('nama', 'like', '%' . $filter->search . '%');
                    });
            });
        }

        return !empty($filter->limit)
            ? $query->paginate($filter->limit)
            : $query->get();
    }

    public function getTotalHargaBeli($filter)
    {
        $query = $this->model::selectRaw('kas, kas_jenis_barang, SUM(saldo) as total_saldo, SUM(harga_beli) as total_harga_beli')
            ->where('kas', $filter->kas)
            ->where('kas_jenis_barang', $filter->kas_jenis_barang);

        return $query->get();
    }

    public function getSaldo($limit = 10, $search = null)
    {
        $query = $this->model::select('id', 'saldo', 'created_at')
            ->orderByDesc('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('saldo', 'like', '%' . $search . '%');
            });
        }

        return $query->paginate($limit);
    }

    public function create(array $data)
    {
        return $this->model::create($data);
    }

    public function find($id)
    {
        return $this->model::where('public_id', $id)->first();
    }

    public function update($id, array $data)
    {
        $item = $this->model::where('public_id', $id);
        if ($item) {
            $item->update($data);
        }
        return $item;
    }

    public function delete($id, array $data)
    {
        $item = $this->model::where('public_id', $id);
        if ($item) {
            $item->update($data);
        }
        return $item ? $item->delete() : false;
    }
}
