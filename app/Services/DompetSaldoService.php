<?php

namespace App\Services;

use App\Repositories\DompetSaldoRepository;
use App\Repositories\PenjualanNonFisikDetailRepository;
use App\Traits\PaginateResponse;
use Illuminate\Validation\ValidationException;

class DompetSaldoService
{
    use PaginateResponse;
    protected $repository;
    protected $repository2;

    public function __construct(DompetSaldoRepository $repository, PenjualanNonFisikDetailRepository $repository2)
    {
        $this->repository = $repository;
        $this->repository2 = $repository2;
    }

    public function sumSisaSaldo(?int $month = null, ?int $year  = null)
    {
        $data = $this->repository->sumSaldo($month, $year) - $this->repository2->sumHPP($month, $year);
        return [
            'saldo' => $data,
            'format' => 'Rp ' . number_format($data, 0, ',', '.')
        ];
    }

    public function sumHPP(?int $month  = null, ?int $year = null)
    {
        $data = $this->repository->sumHargaBeli($month, $year) - $this->repository2->sumHPP($month, $year);
        return [
            'saldo' => $data,
            'format' => 'Rp ' . number_format($data, 0, ',', '.')
        ];
    }

    public function getAll($filter)
    {
        $query = $this->repository->getAll($filter);

        $data = collect($query->items())->map(function ($item) {
            return [
                'id' => $item->public_id,
                'saldo' => $item->saldo,
                'format_saldo' => 'Rp ' . number_format($item->saldo, 0, ',', '.'),
                'harga_beli' => $item->harga_beli,
                'format_harga_beli' => 'Rp ' . number_format($item->harga_beli, 0, ',', '.'),
                'id_kategori' => $item->dompetKategori->id ?? null,
                'kategori' => $item->dompetKategori->nama ?? 'Tidak ada kategori',
                'created_at' => $item->created_at ?? null,
                'created_by' => $item->createdBy->nama ?? 'System',
                'updated_at' => $item->updated_at ?? null,
                'updated_by' => $item->updatedBy->nama ?? null,
            ];
        });

        return [
            'data' => $data,
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function getTotalPerKategori($filter)
    {
        $query = $this->repository->getTotalPerKategori($filter);

        $hppData = $this->repository2->getTotalPerKategori((object) [
            'limit' => null,
            'search' => $filter->search,
            'dompet_kategori' => $filter->dompet_kategori
        ])
            ->mapWithKeys(function ($item) {
                return [
                    (int) $item->dompet_kategori_id => (float) ($item->total_hpp ?? 0)
                ];
            });

        $data = collect(method_exists($query, 'items') ? $query->items() : $query)->map(function ($item) use ($hppData) {
            $dompetKategoriId = (int) ($item->dompet_kategori_id ?? 0);
            $hpp = $hppData[$dompetKategoriId] ?? 0;
            $totalSaldo = (float) ($item->total_saldo ?? 0);

            $saldoSetelahHpp = $totalSaldo - $hpp;

            return [
                'dompet_kategori_id' => $dompetKategoriId,
                'nama_kategori' => $item->nama_kategori ?? $item->dompetKategori->nama ?? 'Tidak ada kategori',

                'total_saldo' => $saldoSetelahHpp,
                'format_total_saldo' => 'Rp ' . number_format($saldoSetelahHpp, 0, ',', '.'),

                'total_harga_beli' => (float) ($item->total_harga_beli ?? 0),
                'format_total_harga_beli' => 'Rp ' . number_format((float) ($item->total_harga_beli ?? 0), 0, ',', '.'),

                'hpp' => $hpp,
                'format_hpp' => 'Rp ' . number_format($hpp, 0, ',', '.'),
            ];
        });

        return [
            'data' => $data,
            'pagination' => !empty($filter->limit) ? $this->setPaginate($query) : null,
        ];
    }

    public function getSaldoAkhir($filter)
    {
        $query = $this->repository->getTotalPerKategori($filter);

        $hppData = $this->repository2->getTotalPerKategori((object) [
            'limit' => $filter->limit,
            'search' => $filter->search,
            'dompet_kategori' => $filter->dompet_kategori
        ])
            ->mapWithKeys(function ($item) {
                return [
                    (int) $item->dompet_kategori_id => (float) ($item->total_hpp ?? 0)
                ];
            });

        $data = collect(method_exists($query, 'items') ? $query->items() : $query)->map(function ($item) use ($hppData) {
            $dompetKategoriId = (int) ($item->dompet_kategori_id ?? 0);
            $hpp = $hppData[$dompetKategoriId] ?? 0;
            $totalSaldo = (float) ($item->total_saldo ?? 0);

            $saldoSetelahHpp = $totalSaldo - $hpp;
            $kategori = $item->nama_kategori ?? $item->dompetKategori->nama ?? 'Tidak ada kategori';
            $text = $kategori . ' - Rp ' . number_format($saldoSetelahHpp, 0, ',', '.');

            return [
                'id' => $dompetKategoriId,
                'text' => $text,
                'saldo' => $saldoSetelahHpp
            ];
        });

        return [
            'data' => $data,
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function getSaldo($limit = 10, $search = null)
    {
        $query = $this->repository->getSaldo($limit, $search);

        $data = collect($query->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->saldo,
            ];
        });

        return [
            'data' => $data,
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function create(array $data)
    {
        [$kasValue, $limitTotal] = explode('/', $data['kas']) + [null, null];

        $data['kas'] = $kasValue;

        if ($limitTotal !== null && $data['harga_beli'] > (float)$limitTotal) {
            throw ValidationException::withMessages([
                'harga_beli' => [
                    "Harga beli (" . number_format($data['harga_beli'], 0, ',', '.') .
                        ") melebihi sisa kas (" . number_format($limitTotal, 0, ',', '.') . ")"
                ]
            ]);
        }

        return $this->repository->create($data);
    }

    public function update($id, array $data)
    {
        [$kasValue, $limitTotal] = explode('/', $data['kas']) + [null, null];

        $data['kas'] = $kasValue;

        if ($limitTotal !== null && $data['harga_beli'] > (float)$limitTotal) {
            throw ValidationException::withMessages([
                'harga_beli' => [
                    "Harga beli (" . number_format($data['harga_beli'], 0, ',', '.') .
                        ") melebihi sisa kas (" . number_format($limitTotal, 0, ',', '.') . ")"
                ]
            ]);
        }

        return $this->repository->update($id, $data);
    }

    public function delete($id, array $data)
    {
        return $this->repository->delete($id, $data);
    }
}
