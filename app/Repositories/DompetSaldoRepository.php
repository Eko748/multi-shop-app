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

    public function sumSaldo(?int $month = null, ?int $year = null, ?int $tokoId = null)
    {
        // WAJIB: Gunakan newQuery() agar kondisi tidak menempel antar-query
        $query = $this->model->newQuery();

        if ($tokoId !== null && $tokoId !== 'all' && $tokoId != 0) {
            $query->where('toko_id', $tokoId);
        }

        if ($month && $year) {
            // Membuat tanggal cut-off akhir bulan (Contoh April: 2026-04-30 23:59:59)
            $endDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateTimeString();

            // Ambil semua data saldo masuk dari awal waktu sampai AKHIR BULAN TARGET
            $query->where('created_at', '<=', $endDate);
        }

        return $query->sum('saldo');
    }

    public function sumHargaBeli(?int $month = null, ?int $year = null, ?int $tokoId = null)
    {
        // Gunakan newQuery() agar kondisi query sebelumnya tidak menumpuk/terbawa
        $query = $this->model->newQuery();

        if ($tokoId !== null && $tokoId !== 'all' && $tokoId != 0) {
            $query->where('toko_id', $tokoId);
        }

        if ($month && $year) {
            // Ambil tanggal terakhir di bulan target (Contoh: 2026-05-31 23:59:59)
            $endDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateTimeString();

            // Menggunakan '<=' agar batch dari tahun-tahun sebelumnya juga ikut terhitung sebagai modal awal
            $query->where('created_at', '<=', $endDate);
        }

        // Menghitung total nilai barang yang PERNAH MASUK sampai bulan target
        // Pastikan rumusnya mencerminkan total aset awal (qty_awal * harga_beli) jika tersedia
        return $query->sum('harga_beli');
    }

    public function getAll($filter)
    {
        $query = $this->model::with('dompetKategori');

        if (! empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('saldo', 'like', '%'.$filter->search.'%')
                    ->orWhere('harga_beli', 'like', '%'.$filter->search.'%')
                    ->orWhereHas('dompetKategori', function ($q2) use ($filter) {
                        $q2->where('nama', 'like', '%'.$filter->search.'%');
                    });
            });
        }

        if (! empty($filter->saldo)) {
            $query->where('saldo', $filter->saldo);
        }

        if (! empty($filter->month)) {
            $query->whereMonth('created_at', $filter->month);
        }

        if (! empty($filter->year)) {
            $query->whereYear('created_at', $filter->year);
        }

        $query->orderByDesc('created_at');

        return ! empty($filter->limit)
            ? $query->paginate($filter->limit)
            : $query->get();
    }

    public function getTotalPerKategori($filter)
    {
        $query = $this->model::with('dompetKategori')
            ->where('toko_id', $filter->toko_id)
            ->selectRaw('dompet_kategori_id, SUM(saldo) as total_saldo, SUM(harga_beli) as total_harga_beli')
            ->groupBy('dompet_kategori_id');

        if (! empty($filter->search)) {
            $query->where(function ($q) use ($filter) {
                $q->where('saldo', 'like', '%'.$filter->search.'%')
                    ->orWhere('harga_beli', 'like', '%'.$filter->search.'%')
                    ->orWhereHas('dompetKategori', function ($q2) use ($filter) {
                        $q2->where('nama', 'like', '%'.$filter->search.'%');
                    });
            });
        }

        return ! empty($filter->limit)
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
                $q->where('saldo', 'like', '%'.$search.'%');
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
