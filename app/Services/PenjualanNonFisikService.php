<?php

namespace App\Services;

use App\Helpers\KasGenerate;
use App\Models\TransaksiKasirHarian;
use App\Helpers\KasRekapHelper;
use App\Models\Kas;
use App\Services\DompetSaldoService;
use App\Repositories\PenjualanNonFisikDetailRepository;
use App\Repositories\PenjualanNonFisikRepository;
use App\Traits\PaginateResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenjualanNonFisikService
{
    use PaginateResponse;
    protected $repository;
    protected $repository2;
    protected $service;

    public function __construct(PenjualanNonFisikRepository $repository, PenjualanNonFisikDetailRepository $repository2, DompetSaldoService $service)
    {
        $this->repository = $repository;
        $this->repository2 = $repository2;
        $this->service = $service;
    }

    public function getTotalHarga($filter)
    {
        $totals = $this->repository2->sumTotalHarga($filter);

        return [
            'hpp' => [
                'total' => $totals['hpp'],
                'format' => 'Rp ' . number_format($totals['hpp'], 0, ',', '.')
            ],
            'harga_jual' => [
                'total' => $totals['harga_jual'],
                'format' => 'Rp ' . number_format($totals['harga_jual'], 0, ',', '.')
            ],
        ];
    }

    public function getAll($filter)
    {
        $query = $this->repository->getAll($filter);

        $data = collect(method_exists($query, 'items') ? $query->items() : $query)->map(function ($item) {
            return [
                'id' => $item->public_id,
                'nota' => $item->nota,
                'dompul' => $item->dompetKategori->nama,
                'total_item' => $item->total_item,
                'total_hpp' => $item->total_hpp,
                'format_total_hpp' => 'Rp ' . number_format($item->total_hpp, 0, ',', '.'),
                'total_harga_jual' => $item->total_harga_jual,
                'format_total_harga_jual' => 'Rp ' . number_format($item->total_harga_jual, 0, ',', '.'),
                'total_bayar' => $item->total_bayar,
                'format_total_bayar' => 'Rp ' . number_format($item->total_bayar, 0, ',', '.'),
                'created_at' => $item->created_at ?? null,
                'created_by' => $item->createdBy->nama ?? 'System',
                'updated_at' => $item->updated_at ?? null,
                'updated_by' => $item->updatedBy->nama ?? null,
                'toko' => $item->createdBy->toko->singkatan ?? '-',
            ];
        });

        return [
            'data' => [
                'item' => $data,
                'total' => $this->getTotalHarga($filter)
            ],
            'pagination' => !empty($filter->limit) ? $this->setPaginate($query) : null,
        ];
    }

    public function getDetail($filter)
    {
        $item = $this->repository->getDetailByPublicId($filter->id);
        $itemFormatted = [
            'id' => $item->public_id,
            'nota' => $item->nota,
            'total_bayar' => 'Rp ' . number_format($item->total_bayar, 0, ',', '.'),
            'total_kembalian' => 'Rp ' . number_format($item->total_bayar - $item->total_harga_jual, 0, ',', '.'),
            'total_hpp' => 'Rp ' . number_format($item->total_hpp, 0, ',', '.'),
            'total_harga_jual' => 'Rp ' . number_format($item->total_harga_jual, 0, ',', '.'),
            'created_at' => $item->created_at ?? null,
            'created_by' => $item->createdBy->nama,
            'nama_toko' => $item->createdBy->toko->nama_toko,
            'alamat_toko' => $item->createdBy->toko->alamat,
        ];

        $filter->penjualan_nonfisik_id = $item->id;
        $query = $this->repository2->getAll($filter);
        $data = collect($query->items())->map(function ($detail) {
            $format_total = $detail->qty * (float) $detail->harga_jual;
            return [
                'id' => $detail->public_id,
                'item' => $detail->item->nama ?? null,
                'tipe' => $detail->item->tipe->nama ?? null,
                'hpp' => $detail->hpp,
                'format_hpp' => 'Rp ' . number_format($detail->hpp, 0, ',', '.'),
                'harga_jual' => (float) $detail->harga_jual,
                'format_harga_jual' => 'Rp ' . number_format($detail->harga_jual, 0, ',', '.'),
                'total_harga_jual' => $format_total,
                'format_total_harga_jual' => 'Rp ' . number_format($format_total, 0, ',', '.'),
                'qty' => $detail->qty,
            ];
        });

        return [
            'data' => [
                'item' => $itemFormatted,
                'detail' => $data
            ],
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function getNota($limit = 10, $search = null)
    {
        $query = $this->repository->getNota($limit, $search);

        $data = collect($query->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->nota,
            ];
        });

        return [
            'data' => $data,
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            $validatedData['kas_id'] = KasGenerate::resolveKasId(
                kasId: $data['kas_id'],
                tokoId: $data['toko_id'],
                jenisBarangId: 0,
                tipeKas: 'kecil',
                tanggal: now(),
            );

            $nota = 'TNF-' . date('dmY-His');

            // =========================
            // VALIDASI
            // =========================
            if ((float) $data['total_bayar'] < $data['total_harga_jual']) {
                throw ValidationException::withMessages([
                    'total_bayar' => "Total bayar tidak mencukupi."
                ]);
            }

            if ((float) $data['total_hpp'] > $data['saldo']) {
                throw ValidationException::withMessages([
                    'saldo' => "Saldo tidak mencukupi."
                ]);
            }

            // =========================
            // HEADER
            // =========================
            $penjualan = $this->repository->create([
                'dompet_kategori_id' => $data['dompet_kategori_id'],
                'total_bayar'        => $data['total_bayar'],
                'total_hpp'          => $data['total_hpp'],
                'total_harga_jual'   => $data['total_harga_jual'],
                'created_by'         => $data['created_by'],
                'nota'               => $nota,
            ]);

            // =========================
            // DETAIL
            // =========================
            foreach ($data['items'] as $item) {
                $this->repository2->create([
                    'penjualan_nonfisik_id' => $penjualan->id,
                    'item_nonfisik_id'      => $item['id'],
                    'qty'                   => $item['qty'],
                    'hpp'                   => $item['hpp'],
                    'harga_jual'            => $item['harga_jual'],
                ]);
            }

            // =========================
            // REKAP KAS HARIAN
            // jenis_barang_id = 0
            // =========================
            $jenisBarangId = 0;

            $kas = Kas::where('toko_id', $data['toko_id'])
                ->where('jenis_barang_id', $jenisBarangId)
                ->where('tipe_kas', 'kecil')
                ->first();

            if (!$kas) {
                throw new \Exception("Kas kecil untuk jenis_barang_id 0 belum dibuat.");
            }

            $rekap = TransaksiKasirHarian::firstOrNew([
                'toko_id'         => $data['toko_id'],
                'tanggal'         => Carbon::now()->toDateString(),
                'jenis_barang_id' => $jenisBarangId,
                'kas_id'          => $kas->id,
            ]);

            if (!$rekap->exists) {
                $rekap->total_transaksi   = 1;
                $rekap->total_qty         = $data['total_qty'] ?? 1;
                $rekap->total_nominal     = $data['total_harga_jual'];
                $rekap->total_bayar       = $data['total_bayar'];
                $rekap->total_diskon      = 0;
                $rekap->total_hpp         = $data['total_hpp'];
                $rekap->total_hpp_batch   = $data['total_hpp'];
            } else {
                $rekap->total_transaksi += 1;
                $rekap->total_qty       += $data['total_qty'] ?? 1;
                $rekap->total_nominal   += $data['total_harga_jual'];
                $rekap->total_bayar     += $data['total_bayar'];
                $rekap->total_hpp       += $data['total_hpp'];
                $rekap->total_hpp_batch += $data['total_hpp'];
            }

            $rekap->updated_by = $data['created_by'];
            $rekap->save();

            // =========================
            // SYNC KE KAS
            // =========================
            KasRekapHelper::syncKasForRekap($rekap);

            return $penjualan;
        });
    }


    public function update($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function delete($id, array $data)
    {
        $item = $this->repository->getByPublicId($id);

        $this->repository2->deleteByPenjualanId($item->id);

        return $this->repository->delete($id, $data);
    }
}
