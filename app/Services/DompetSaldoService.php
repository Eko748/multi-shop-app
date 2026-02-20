<?php

namespace App\Services;

use App\Helpers\RupiahGenerate;
use App\Models\Kas;
use App\Repositories\DompetSaldoRepository;
use App\Repositories\PenjualanNonFisikDetailRepository;
use App\Traits\PaginateResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

    public function sumSisaSaldo(?int $month = null, ?int $year  = null, ?int $tokoId = null)
    {
        $data = $this->repository->sumSaldo($month, $year, $tokoId) - $this->repository2->sumHPP($month, $year, $tokoId);
        return [
            'saldo' => $data,
            'format' => 'Rp ' . number_format($data, 0, ',', '.')
        ];
    }

    public function sumHPP(?int $month  = null, ?int $year = null, ?int $tokoId = null)
    {
        $data = $this->repository->sumHargaBeli($month, $year, $tokoId) - $this->repository2->sumHPP($month, $year, $tokoId);
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

        $kas = Kas::where('jenis_barang_id', 0)->where('tipe_kas', 'kecil')->where('toko_id', $filter->toko_id)->first();

        $data = collect(method_exists($query, 'items') ? $query->items() : $query)->map(function ($item) use ($hppData, $kas) {
            $dompetKategoriId = (int) ($item->dompet_kategori_id ?? 0);
            $hpp = $hppData[$dompetKategoriId] ?? 0;
            $totalSaldo = (float) ($item->total_saldo ?? 0);

            $saldoSetelahHpp = $totalSaldo - $hpp;
            $kategori = $item->nama_kategori ?? $item->dompetKategori->nama ?? 'Tidak ada kategori';
            $text = $kategori . ' - Rp ' . number_format($saldoSetelahHpp, 0, ',', '.');

            return [
                'id' => $dompetKategoriId,
                'text' => $text,
                'saldo' => $saldoSetelahHpp,
                'kas' => $kas->id ?? 'NEW-0',
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
        $saldoKas   = (float) ($data['saldo_kas'] ?? 0);
        $hargaBeli = (float) $data['harga_beli'];

        if ($saldoKas < $hargaBeli) {
            throw ValidationException::withMessages([
                'saldo_kas' => [
                    "Saldo kas (" . RupiahGenerate::build($saldoKas) .
                        ") tidak mencukupi untuk harga beli (" .
                        RupiahGenerate::build($hargaBeli) . ")"
                ]
            ]);
        }

        return DB::transaction(function () use ($data, $hargaBeli) {
            $item = $this->repository->create($data);

            KasService::topup(
                toko_id: $data['toko_id'],
                jenis_barang_id: $data['jenis_barang_id'],
                tipe_kas: $data['tipe_kas'],
                saldo: $data['saldo'],
                hargaBeli: $data['harga_beli'],
                item: $data['tipe_kas'],
                kategori: 'Saldo Digital',
                keterangan: 'Top-up Saldo',
                sumber: $item,
                tanggal: Carbon::parse($item->created_at)
            );

            return $item;
        });
    }


    public function update($id, array $data)
    {
        $item = $this->repository->find($id);

        $hargaLama = (float) $item->harga_beli;
        $hargaBaru = (float) ($data['harga_beli'] ?? $hargaLama);
        $saldoKas  = (float) ($data['saldo_kas'] ?? 0);

        if ($saldoKas < $hargaBaru) {
            throw ValidationException::withMessages([
                'saldo_kas' => [
                    "Saldo kas (" . RupiahGenerate::build($saldoKas) .
                        ") tidak mencukupi untuk harga beli (" .
                        RupiahGenerate::build($hargaBaru) . ")"
                ]
            ]);
        }

        return DB::transaction(function () use ($id, $data, $item, $hargaBaru) {
            $updatedItem = $this->repository->update($id, $data);

            KasService::update(
                kasId: $item->kas_id,
                id: $item->id,
                sumber: get_class($item),
                tanggal: Carbon::parse($updatedItem->updated_at),
                totalNominal: $hargaBaru
            );

            return $updatedItem;
        });
    }

    public function delete($id, array $data)
    {
        return $this->repository->delete($id, $data);
    }
}
