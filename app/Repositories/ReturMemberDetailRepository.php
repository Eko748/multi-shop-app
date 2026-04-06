<?php

namespace App\Repositories;

use App\Models\ReturMemberDetail;

class ReturMemberDetailRepository
{
    protected $model;

    public function __construct(ReturMemberDetail $model)
    {
        $this->model = $model;
    }

    public function getById($id)
    {
        return $this->model->where('id', $id)->get();
    }

    public function find($id)
    {
        return $this->model->where('id', $id)->first();
    }

    public function getAll($filter)
    {
        $query = $this->model->query()->with('barang:id,nama', 'supplier:id,nama', 'batch.stokDetail');

        if (!empty($filter->start_date) && !empty($filter->end_date)) {
            $query->whereBetween('tanggal', [$filter->start_date, $filter->end_date]);
        }

        if (!empty($filter->supplier_id)) {
            $query->where('supplier_id', $filter->supplier_id);
        }

        if (!empty($filter->id)) {
            $query->where('retur_id', $filter->id);
        }

        $query->orderByDesc('id');

        return !empty($filter->limit)
            ? $query->paginate($filter->limit)
            : $query->get();
    }

    public function getDetail($id)
    {
        return $this->model::with([
            'detailKasir'
        ])->where('id', $id)->first();
    }

    public function getDistinctSuppliers($filter)
    {
        if (empty($filter->toko_id)) {
            throw new \Exception("toko_id wajib diisi");
        }

        $query = $this->model->with('supplier:id,nama,telepon')
            ->selectRaw("
            supplier_id,
            SUM(qty_request - COALESCE(qty_ke_supplier,0)) as total_item_retur
        ")
            ->whereRaw("
            qty_request > COALESCE(qty_ke_supplier,0)
        ")
            ->whereHas('retur', function ($q) use ($filter) {
                $q->where('toko_id', $filter->toko_id);
            })
            ->groupBy('supplier_id');

        if (!empty($filter->search)) {
            $search = $filter->search;
            $query->whereHas('supplier', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('telepon', 'like', "%{$search}%");
            });
        }

        $result = $query->paginate($filter->limit ?? 10);

        $result->getCollection()->transform(function ($item) {
            return (object) [
                'id'               => $item->supplier->id ?? null,
                'nama'             => $item->supplier->nama ?? null,
                'telepon'          => $item->supplier->telepon ?? null,
                'total_item_retur' => (int) ($item->total_item_retur ?? 0),
            ];
        });

        return $result;
    }

    public function getHargaBarang($filter)
    {
        if (empty($filter->toko_id)) {
            throw new \Exception("toko_id wajib diisi");
        }

        $query = $this->model::with([
            'supplier:id,nama,telepon',
            'barang:id,nama',
            'transaksiKasirDetail:id,stock_barang_batch_id,qrcode',
        ])
            ->select(
                'id',
                'transaksi_kasir_detail_id',
                'supplier_id',
                'barang_id',
                'qty_request',
                'qty_barang',
                'qty_ke_supplier',
                'hpp',
                'harga_jual',
                'created_at'
            )
            ->selectRaw('(qty_request - COALESCE(qty_ke_supplier,0)) as qty')
            ->where('supplier_id', $filter->id)
            ->whereRaw('(qty_request - COALESCE(qty_ke_supplier,0)) > 0')

            // ✅ FILTER TOKO
            ->whereHas('retur', function ($q) use ($filter) {
                $q->where('toko_id', $filter->toko_id);
            })

            // ✅ GROUP BY SEMUA KOLOM (biar aman)
            ->groupBy(
                'id',
                'transaksi_kasir_detail_id',
                'supplier_id',
                'barang_id',
                'qty_request',
                'qty_barang',
                'qty_ke_supplier',
                'hpp',
                'harga_jual',
                'created_at'
            );

        return !empty($filter->limit)
            ? $query->orderByDesc('id')->paginate($filter->limit)
            : $query->orderByDesc('id')->get();
    }

    public function sumHppBarang()
    {
        return (float) $this->model
            ->selectRaw('SUM(total_hpp) as total')
            ->value('total');
    }

    public function sumRefund()
    {
        return (float) $this->model
            ->selectRaw('SUM(total_refund) as total')
            ->value('total');
    }

    public function getByReturId($returId)
    {
        return $this->model
            ->where('retur_id', $returId)
            ->with(['barang'])
            ->get();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $detail = $this->model->findOrFail($id);
        $detail->update($data);
        return $detail;
    }

    public function delete($id)
    {
        $detail = $this->model->findOrFail($id);
        return $detail->delete();
    }
}
