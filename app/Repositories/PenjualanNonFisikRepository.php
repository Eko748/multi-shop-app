<?php

namespace App\Repositories;

use App\Models\PenjualanNonFisik;
use Illuminate\Support\Facades\DB;

class PenjualanNonFisikRepository
{
    protected $model;

    public function __construct(PenjualanNonFisik $model)
    {
        $this->model = $model;
    }

    protected function baseQuery()
    {
        return $this->model::query()
            ->select(
                'td_penjualan_nonfisik.id',
                'td_penjualan_nonfisik.public_id',
                'td_penjualan_nonfisik.dompet_kategori_id',
                'td_penjualan_nonfisik.nota',
                'td_penjualan_nonfisik.total_bayar',
                'td_penjualan_nonfisik.created_by',
                'td_penjualan_nonfisik.created_at',
                'td_penjualan_nonfisik.updated_at',
                DB::raw('COALESCE(SUM(td_penjualan_nonfisik_detail.hpp * td_penjualan_nonfisik_detail.qty), 0) as total_hpp'),
                DB::raw('COALESCE(SUM(td_penjualan_nonfisik_detail.harga_jual * td_penjualan_nonfisik_detail.qty), 0) as total_harga_jual'),
                DB::raw('COALESCE(SUM(td_penjualan_nonfisik_detail.qty), 0) as total_item')
            )
            ->leftJoin(
                'td_penjualan_nonfisik_detail',
                'td_penjualan_nonfisik_detail.penjualan_nonfisik_id',
                '=',
                'td_penjualan_nonfisik.id'
            )
            ->groupBy(
                'td_penjualan_nonfisik.id',
                'td_penjualan_nonfisik.public_id',
                'td_penjualan_nonfisik.dompet_kategori_id',
                'td_penjualan_nonfisik.nota',
                'td_penjualan_nonfisik.total_bayar',
                'td_penjualan_nonfisik.created_by',
                'td_penjualan_nonfisik.created_at',
                'td_penjualan_nonfisik.updated_at'
            )
            ->with('detail.item', 'createdBy', 'dompetKategori:id,nama');
    }

    public function getAll($filter)
    {
        $query = $this->baseQuery();

        if (!empty($filter->start_date) && !empty($filter->end_date)) {
            $query->whereBetween('td_penjualan_nonfisik.created_at', [$filter->start_date, $filter->end_date]);
        } else {
            $query->whereDate('td_penjualan_nonfisik.created_at', date('Y-m-d'));
        }

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('td_penjualan_nonfisik.nota', 'like', '%' . $filter->search . '%')
                    ->orWhereHas('detail.item', function ($q2) use ($filter) {
                        $q2->where('nama', 'like', '%' . $filter->search . '%');
                    });
            });
        }

        if (!empty($filter->total_hpp)) {
            $query->having('total_hpp', $filter->total_hpp);
        }

        if (!empty($filter->month)) {
            $query->whereMonth('td_penjualan_nonfisik.created_at', $filter->month);
        }

        if (!empty($filter->year)) {
            $query->whereYear('td_penjualan_nonfisik.created_at', $filter->year);
        }

        $query->orderByDesc('td_penjualan_nonfisik.id');

        return !empty($filter->limit)
            ? $query->paginate($filter->limit)
            : $query->get();
    }

    public function getDetailByPublicId($id)
    {
        return $this->baseQuery()
            ->where('td_penjualan_nonfisik.public_id', $id)
            ->first();
    }

    public function getTotalPerKategori($filter)
    {
        $query = $this->model::with('detail', 'dompetKategori')
            ->selectRaw('dompet_kategori_id, SUM(total_hpp) as hpp, SUM(total_harga_jual) as harga_jual')
            ->groupBy('dompet_kategori_id');

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('total_hpp', 'like', '%' . $filter->search . '%')
                    ->orWhere('total_harga_jual', 'like', '%' . $filter->search . '%')
                    ->orWhereHas('dompetKategori', function ($q2) use ($filter) {
                        $q2->where('nama', 'like', '%' . $filter->search . '%');
                    });
            });
        }

        return $filter->limit
            ? $query->paginate($filter->limit)
            : $query->get();
    }

    public function getNota($limit = 10, $search = null)
    {
        $query = $this->model::select('id', 'nota', 'created_at')
            ->orderByDesc('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nota', 'like', '%' . $search . '%');
            });
        }

        return $query->paginate($limit);
    }

    public function create(array $data)
    {
        return $this->model::create($data);
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

    public function getByPublicId($id)
    {
        return $this->model::where('public_id', $id)->first();
    }
}
