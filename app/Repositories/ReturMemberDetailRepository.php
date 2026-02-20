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
        $query = $this->model->with('supplier:id,nama,telepon')
            ->select('supplier_id')
            ->distinct()
            // exclude supplier yg semua itemnya sudah terpenuhi (qty_ke_supplier = qty_request)
            ->whereRaw('EXISTS (
            SELECT 1
            FROM ' . $this->model->getTable() . ' d
            WHERE d.supplier_id = ' . $this->model->getTable() . '.supplier_id
              AND (d.qty_request > COALESCE(d.qty_ke_supplier,0))
        )');

        if (!empty($filter->search)) {
            $search = $filter->search;
            $query->whereHas('supplier', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('telepon', 'like', "%{$search}%");
            });
        }

        $result = $query->paginate($filter->limit ?? 10);

        $result->getCollection()->transform(function ($item) {
            $supplierId = $item->supplier->id ?? null;

            $totalItemRetur = $this->model
                ->where('supplier_id', $supplierId)
                ->whereRaw('qty_request > COALESCE(qty_ke_supplier,0)')
                ->selectRaw('SUM(qty_request - COALESCE(qty_ke_supplier,0)) as total')
                ->value('total');

            return (object) [
                'id'               => $supplierId,
                'nama'             => $item->supplier->nama ?? null,
                'telepon'          => $item->supplier->telepon ?? null,
                'total_item_retur' => $totalItemRetur,
            ];
        });

        return $result;
    }

    public function getHargaBarang($filter)
    {
        $query = $this->model::with([
            'supplier:id,nama,telepon',
            'barang:id,nama',
            'transaksiKasirDetail:id,stock_barang_batch_id',
            'transaksiKasirDetail.stockBarangBatch' => function ($q) {
                $q->select('id', 'qrcode');
            },
        ])
            ->select(
                'id',
                'transaksi_kasir_detail_id',
                'supplier_id',
                'barang_id',
                'qty_request',
                'qty_ke_supplier',
                'hpp',
                'harga_jual',
                'created_at'
            )
            ->selectRaw('(qty_request - COALESCE(qty_ke_supplier,0)) as qty')
            ->where('supplier_id', $filter->id)
            ->whereRaw('(qty_request - COALESCE(qty_ke_supplier,0)) > 0');

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
