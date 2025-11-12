<?php

namespace App\Repositories;

use App\Models\ItemNonFisikHarga;

class ItemNonFisikHargaRepository
{
    protected $model;

    public function __construct(ItemNonFisikHarga $model)
    {
        $this->model = $model;
    }

    public function getAll($filter)
    {
        $query = $this->model::query();

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('nama', 'like', '%' . $filter->search . '%');
            });
        }

        if (!empty($filter->nama)) {
            $query->where('nama', $filter->nama);
        }

        if (!empty($filter->item_nonfisik_id)) {
            $query->where('item_nonfisik_id', $filter->item_nonfisik_id);
        }

        return !empty($filter->limit)
            ? $query->paginate($filter->limit)
            : $query->get();
    }

    public function getItemHarga($limit = 10, $search = null)
    {
        $query = $this->model::with(['item', 'dompetKategori'])
            ->select('id', 'public_id', 'item_nonfisik_id', 'dompet_kategori_id', 'hpp', 'harga_jual', 'created_at')
            ->where('hpp', '>', 0)
            ->where('harga_jual', '>', 0)
            ->orderByDesc('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('item', function ($q2) use ($search) {
                    $q2->where('nama', 'like', '%' . $search . '%');
                })
                ->orWhereHas('dompetKategori', function ($q3) use ($search) {
                    $q3->where('nama', 'like', '%' . $search . '%');
                });
            });
        }

        return $query->paginate($limit);
    }

    public function getDetailById($id)
    {
        return $this->model::select('id', 'hpp', 'harga_jual', 'dompet_kategori_id')
            ->where('id', $id)
            ->first();
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
        $item = $this->model::find($id);
        if ($item) {
            $item->update($data);
        }
        return $item;
    }

    public function delete($id)
    {
        $item = $this->model::where('item_nonfisik_id', $id);
        return $item ? $item->delete() : false;
    }
}
