<?php

namespace App\Services\TransaksiBarang;

use App\Helpers\KasGenerate;
use App\Helpers\KasJenisBarangGenerate;
use App\Helpers\NotaGenerate;
use App\Helpers\RupiahGenerate;
use App\Helpers\TextGenerate;
use App\Models\JenisBarang;
use App\Models\Kas;
use App\Models\Toko;
use App\Models\TransaksiKasirHarian;
use App\Repositories\TransaksiBarang\TransaksiKasirDetailRepo;
use App\Repositories\TransaksiBarang\PembelianBarangRepo;
use App\Services\KasService;
use App\Traits\PaginateResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PembelianBarangService
{
    use PaginateResponse;
    protected $repository;
    protected $repo2;

    public function __construct(PembelianBarangRepo $repository, TransaksiKasirDetailRepo $repo2)
    {
        $this->repository = $repository;
        $this->repo2 = $repo2;
    }

    public function getAll($filter)
    {
        $query = $this->repository->getAll($filter);

        $data = collect(method_exists($query, 'items') ? $query->items() : $query)->map(function ($item) {
            $status = match ($item->status) {
                'success' => 'Sukses',
                'progess' => 'Progres',
                'success_debt' => 'Sukses',
                'completed_debt' => 'Sukses - Hutang',
                default => $item->status,
            };
            return [
                'id' => $item->id,
                'nota' => $item->nota,
                'qty' => $item->qty ?? 0,
                'total' => RupiahGenerate::build($item->total),
                'tanggal' => $item->tanggal->format('d-m-Y H:i:s'),
                'kas' => KasJenisBarangGenerate::labelForKas($item),
                'status' => "{$status}",
                'suplier' => optional($item->supplier)->nama ?? 'Tidak Ada',
                'created_at' => $item->created_at ?? null,
                'created_by' => $item->createdBy->nama ?? 'System',
            ];
        });

        return [
            'data' => [
                'item' => $data,
                'total' => $this->repository->sumNominal($filter)
            ],
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function getDetail($filter)
    {
        $item = $this->repository->getDetail($filter);

        if (!$item) {
            return null;
        }

        $status = match ($item->status) {
            'success'         => 'Sukses',
            'progress'        => 'Progres',
            'success_debt'    => 'Sukses',
            'completed_debt'  => 'Sukses - Hutang',
            default           => $item->status,
        };

        // Ambil detail sesuai status
        if ($item->status === 'progress') {
            $detail = $item->temp;
        } else {
            $detail = $item->detail;
        }

        if ($detail) {
            $detail = $detail->map(function ($d) {
                $nama    = $d->barang->nama ? TextGenerate::smartTail($d->barang->nama) : '-';
                $barcode = $d->barang->barcode ?? '-';

                $d->barang_label = "{$nama} (Barcode: {$barcode})";
                return $d;
            });
        }

        return [
            'id'              => $item->id,
            'nota'            => $item->nota,
            'tipe'            => $item->tipe,
            'jenis_barang_id' => $item->kas->jenis_barang_id,
            'tanggal'         => $item->tanggal->format('Y-m-d H:i:s'),
            'kas'             => KasJenisBarangGenerate::labelForKas($item, null, true),
            'kas_id'          => $item->kas_id,
            'status'          => $status,
            'suplier'         => optional($item->supplier)->nama ?? 'Tidak Ada',
            'suplier_id'       => optional($item->supplier)->id ?? null,
            'toko_group'       => optional($item->tokoGroup)->nama ?? 'Tidak Ada',
            'toko_group_id'    => optional($item->tokoGroup)->id ?? null,
            'created_at'       => $item->created_at,
            'created_by'       => $item->createdBy->nama ?? 'System',
            'detail'           => $detail,
        ];
    }

    public function getTotalNominal($filter)
    {
        return $this->repository->sumNominal($filter);
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

            $memberId = $data['member_id'] === 'guest'
                ? null
                : $data['member_id'];

            $header = $this->repository->create([
                'toko_id'       => $data['toko_id'],
                'nota'          => NotaGenerate::build($data['toko_id'], $data['tanggal']),
                'tanggal'       => $data['tanggal'],
                'total_qty'     => $data['total_qty'],
                'total_nominal' => $data['total_nominal'],
                'total_bayar'   => $data['total_bayar'],
                'total_diskon'  => $data['total_diskon'] ?? 0,
                'metode'        => $data['metode'],
                'member_id'     => $memberId,
                'created_by'    => $data['created_by'],
            ]);

            $savedDetails = collect($data['details'])->map(function ($detail) use ($header) {
                $child = $this->repo2->create([
                    'transaksi_kasir_id'     => $header->id,
                    'stock_barang_batch_id'  => $detail['stock_barang_batch_id'],
                    'qty'                    => $detail['qty'],
                    'nominal'                => $detail['nominal'],
                    'subtotal'               => $detail['qty'] * $detail['nominal'],
                ]);

                $child->load('stockBarangBatch.stockBarang.barang');
                $child->jenis_barang_id = $child->stockBarangBatch->stockBarang->barang->jenis_barang_id;

                if (!$child->jenis_barang_id) {
                    throw new \Exception("Jenis barang tidak ditemukan untuk stock_barang_batch_id {$detail['stock_barang_batch_id']}");
                }

                // ----------------------------
                // Update stok batch & stok barang
                // ----------------------------
                $batch = $child->stockBarangBatch;
                $stockBarang = $batch->stockBarang;

                if ($batch->qty_sisa < $child->qty) {
                    throw new \Exception("Stok batch tidak cukup untuk stock_barang_batch_id {$batch->id}");
                }

                $batch->qty_sisa -= $child->qty;
                $batch->save();

                $stockBarang->stok -= $child->qty;
                if ($stockBarang->stok < 0) $stockBarang->stok = 0; // aman
                $stockBarang->save();

                // ----------------------------
                // Hitung total HPP per detail
                // ----------------------------
                $child->total_hpp = $child->qty * $stockBarang->hpp_baru;
                $child->total_hpp_batch = $child->qty * $batch->hpp_baru;

                return $child;
            });

            $grouped = $savedDetails->groupBy(fn($d) => $d->jenis_barang_id);

            foreach ($grouped as $jenisId => $rows) {
                $kas = Kas::where('toko_id', $data['toko_id'])
                    ->where('jenis_barang_id', $jenisId)
                    ->where('tipe_kas', 'kecil')
                    ->first();

                if (!$kas) {
                    throw new \Exception("Kas kecil untuk jenis_barang_id {$jenisId} belum dibuat.");
                }

                $total_qty = $rows->sum(fn($r) => $r->qty);
                $total_nominal = $rows->sum(fn($r) => $r->subtotal);
                $total_hpp = $rows->sum(fn($r) => $r->total_hpp);
                $total_hpp_batch = $rows->sum(fn($r) => $r->total_hpp_batch);

                $rekap = TransaksiKasirHarian::firstOrNew([
                    'toko_id'        => $header->toko_id,
                    'tanggal'        => Carbon::parse($header->tanggal)->toDateString(),
                    'jenis_barang_id' => $jenisId,
                    'kas_id'         => $kas->id,
                ]);

                if (!$rekap->exists) {
                    $rekap->kas_id = $kas->id;
                    $rekap->total_transaksi = 1;
                    $rekap->total_qty = $total_qty;
                    $rekap->total_nominal = $total_nominal;
                    $rekap->total_diskon = 0;
                    $rekap->total_bayar = $total_nominal;
                    $rekap->total_hpp = $total_hpp;
                    $rekap->total_hpp_batch = $total_hpp_batch;
                } else {
                    $rekap->total_transaksi += 1;
                    $rekap->total_qty += $total_qty;
                    $rekap->total_nominal += $total_nominal;
                    $rekap->total_bayar += $total_nominal;
                    $rekap->total_hpp += $total_hpp;
                    $rekap->total_hpp_batch = $total_hpp_batch;
                }

                $rekap->updated_by = $header->created_by;
                $rekap->save();

                $this->syncKasForRekap($rekap, $header->tanggal);
            }

            return $header->load('details');
        });
    }

    protected function syncKasForRekap(TransaksiKasirHarian $rekap, $tanggal = null)
    {
        $toko = Toko::find($rekap->toko_id);

        if (!$toko) {
            throw new \Exception("Toko tidak ditemukan.");
        }

        $kas = Kas::where('toko_id', $toko->id)
            ->where('tipe_kas', 'kecil')
            ->where('jenis_barang_id', $rekap->jenis_barang_id)
            ->first();

        if (!$kas) {
            throw new \Exception(
                "Kas kecil untuk jenis_barang_id {$rekap->jenis_barang_id} belum dibuat."
            );
        }

        $jenisNama = JenisBarang::find($rekap->jenis_barang_id)?->nama_jenis_barang ?? "-";

        KasService::kasir(
            toko_id: $toko->id,
            jenis_barang_id: $rekap->jenis_barang_id,
            tipe_kas: $kas->tipe_kas,
            total_nominal: $rekap->total_bayar,
            item: 'kecil',
            kategori: 'Transaksi Kasir',
            keterangan: "Kas {$jenisNama}",
            sumber: $rekap,
        );
    }

    public function count()
    {
        return $this->repository->count();
    }
}
