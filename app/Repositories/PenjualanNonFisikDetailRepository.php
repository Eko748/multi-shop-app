<?php

namespace App\Repositories;

use App\Models\PenjualanNonFisikDetail;

class PenjualanNonFisikDetailRepository
{
    protected $model;

    public function __construct(PenjualanNonFisikDetail $model)
    {
        $this->model = $model;
    }

    public function sumTotalHarga($filter)
    {
        $query = $this->model->newQuery();

        if (!empty($filter->start_date) && !empty($filter->end_date)) {
            $query->whereBetween('created_at', [$filter->start_date, $filter->end_date]);
        } else {
            $query->whereDate('created_at', date('Y-m-d'));
        }

        $result = $query->selectRaw('
            SUM(hpp * qty) as total_hpp,
            SUM(harga_jual * qty) as total_harga_jual
        ')->first();

        return [
            'hpp' => (float) ($result->total_hpp ?? 0),
            'harga_jual' => (float) ($result->total_harga_jual ?? 0),
        ];
    }

    public function sumHPP(?int $month = null, ?int $year = null, ?int $tokoId = null)
    {
        $query = $this->model->where('toko_id', $tokoId)->selectRaw('SUM(hpp * qty) as total_hpp');

        if ($month && $year) {
            $query->whereYear('created_at', $year)
                ->whereMonth('created_at', '<=', $month);
        }

        return $query->value('total_hpp');
    }

    public function sumHargaJual()
    {
        return (float) $this->model
            ->selectRaw('SUM(harga_jual * qty) as total_harga_jual')
            ->value('total_harga_jual');
    }

    public function getAll($filter)
    {
        $query = $this->model->with(['item.tipe']);

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('hpp', 'like', '%' . $filter->search . '%')
                    ->orWhere('harga_jual', 'like', '%' . $filter->search . '%')
                    ->orWhereHas('item', function ($q2) use ($filter) {
                        $q2->where('nama', 'like', '%' . $filter->search . '%');
                    });
            });
        }

        if (!empty($filter->penjualan_nonfisik_id)) {
            $query->where('penjualan_nonfisik_id', $filter->penjualan_nonfisik_id);
        }

        if (!empty($filter->nama)) {
            $query->whereHas('item', function ($q2) use ($filter) {
                $q2->where('nama', 'like', '%' . $filter->nama . '%');
            });
        }

        return $query->orderByDesc('id')->paginate($filter->limit ?? 10);
    }

    public function getTotalPerKategori($filter)
    {
        $query = $this->model::query()
            ->join('td_penjualan_nonfisik as pn', 'pn.id', '=', 'td_penjualan_nonfisik_detail.penjualan_nonfisik_id')
            ->join('td_dompet_kategori as dk', 'dk.id', '=', 'pn.dompet_kategori_id')
            ->selectRaw('
                dk.id as dompet_kategori_id,
                SUM(td_penjualan_nonfisik_detail.hpp * td_penjualan_nonfisik_detail.qty) as total_hpp,
                SUM(td_penjualan_nonfisik_detail.harga_jual * td_penjualan_nonfisik_detail.qty) as total_harga_jual
            ');

        if (!empty($filter->dompet_kategori)) {
            $query->where('dk.id', $filter->dompet_kategori);
        }

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('td_penjualan_nonfisik_detail.hpp', 'like', '%' . $filter->search . '%')
                    ->orWhere('td_penjualan_nonfisik_detail.harga_jual', 'like', '%' . $filter->search . '%')
                    ->orWhere('dk.nama', 'like', '%' . $filter->search . '%');
            });
        }

        return !empty($filter->limit)
            ? $query->groupBy('dk.id')->paginate($filter->limit)
            : $query->groupBy('dk.id')->get();
    }

    public function getDetailByPublicId($id)
    {
        return $this->model::where('public_id', $id)->first();
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

    public function deleteByPenjualanId($penjualanNonfisikId)
    {
        return $this->model::where('penjualan_nonfisik_id', $penjualanNonfisikId)->delete();
    }

    public function delete($id)
    {
        $item = $this->model::where('penjualan_nonfisik_id', $id);

        return $item ? $item->delete() : false;
    }
}
