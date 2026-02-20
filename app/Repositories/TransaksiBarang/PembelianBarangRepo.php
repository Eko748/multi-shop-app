<?php

namespace App\Repositories\TransaksiBarang;

use App\Helpers\RupiahGenerate;
use App\Models\PembelianBarang;
use App\Models\PembelianBarangDetail;
use App\Models\PembelianBarangDetailTemp;
use Illuminate\Support\Carbon;

class PembelianBarangRepo
{
    protected $model;

    public function __construct(PembelianBarang $model)
    {
        $this->model = $model;
    }

    public function sumNominal($filter)
    {
        $query = PembelianBarang::query();

        if (!empty($filter->start_date) && !empty($filter->end_date)) {
            $query->whereBetween('tanggal', [
                Carbon::parse($filter->start_date)->startOfDay(),
                Carbon::parse($filter->end_date)->endOfDay(),
            ]);
        }

        $pembelian = $query->get();

        $totalQty = 0;
        $totalNominal = 0;

        foreach ($pembelian as $pb) {
            if ($pb->status === 'progress') {
                $detail = PembelianBarangDetailTemp::where('pembelian_barang_id', $pb->id)
                    ->selectRaw('SUM(qty) as qty, SUM(harga_beli * qty) as nominal')
                    ->first();
            } else {
                $detail = PembelianBarangDetail::where('pembelian_barang_id', $pb->id)
                    ->selectRaw('SUM(qty) as qty, SUM(harga_beli * qty) as nominal')
                    ->first();
            }

            $totalQty += $detail->qty ?? 0;
            $totalNominal += $detail->nominal ?? 0;
        }

        return [
            'qty'     => $totalQty,
            'nominal' => RupiahGenerate::build($totalNominal)
        ];
    }

    public function getAll($filter)
    {
        $query = $this->model->newQuery();

        if (!empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                // Search di kolom utama
                $q->where('nota', 'like', '%' . $filter->search . '%');

                // Search di relasi detail -> barang -> nama
                $q->orWhereHas('detail.barang', function ($q2) use ($filter) {
                    $q2->where('nama', 'like', '%' . $filter->search . '%');
                });
            });
        }

        if (!empty($filter->nota)) {
            $query->where('nota', $filter->nota);
        }

        if (!empty($filter->start_date) && !empty($filter->end_date)) {
            $query->whereBetween('tanggal', [$filter->start_date, $filter->end_date]);
        }

        return !empty($filter->limit)
            ? $query->orderByDesc('id')->paginate($filter->limit)
            : $query->orderByDesc('id')->get();
    }


    public function getDetail($filter)
    {
        return $this->model->with([
            'detail.barang',
            'temp.barang',
            'supplier',
            'tokoGroup',
            'kas',
        ])->find($filter->id);
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
        $item = $this->model::find($id);
        if ($item) {
            $item->update($data);
        }
        return $item;
    }

    public function delete($id, $data)
    {
        $item = $this->model::find($id);
        if ($item) {
            $item->update($data);
        }
        return $item ? $item->delete() : false;
    }

    public function count()
    {
        return $this->model::count();
    }
}
