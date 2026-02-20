<?php

namespace App\Services\TransaksiBarang;

use App\Helpers\NotaGenerate;
use App\Helpers\RupiahGenerate;
use App\Helpers\KasRekapHelper;
use App\Helpers\QrGenerator;
use App\Models\Kas;
use App\Models\KasSaldoHistory;
use App\Models\KasTransaksi;
use App\Models\LabaRugi;
use App\Models\LabaRugiTahunan;
use App\Models\TransaksiKasir;
use App\Models\TransaksiKasirDetail;
use App\Models\TransaksiKasirHarian;
use App\Repositories\TransaksiBarang\TransaksiKasirDetailRepo;
use App\Repositories\TransaksiBarang\TransaksiKasirRepo;
use App\Traits\PaginateResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransaksiKasirService
{
    use PaginateResponse;
    protected $repository;
    protected $repo2;

    public function __construct(TransaksiKasirRepo $repository, TransaksiKasirDetailRepo $repo2)
    {
        $this->repository = $repository;
        $this->repo2 = $repo2;
    }

    public function getAll($filter)
    {
        $query = $this->repository->getAll($filter);

        $data = collect(method_exists($query, 'items') ? $query->items() : $query)->map(function ($item) {
            return [
                'id' => $item->public_id,
                'nota' => $item->nota,
                'qty' => $item->total_qty,
                'nominal' => RupiahGenerate::build($item->total_nominal),
                'tanggal' => $item->tanggal->format('d-m-Y H:i:s'),
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
                    'qrcode'                 => QrGenerator::build('QR-TRX-'),
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

            // ----------------------------
            // Bagian create()
            // ----------------------------
            $grouped = $savedDetails->groupBy(fn($d) => $d->jenis_barang_id);

            foreach ($grouped as $jenisId => $rows) {
                $kas = Kas::where('toko_id', $data['toko_id'])
                    ->where('jenis_barang_id', $jenisId)
                    ->where('tipe_kas', 'kecil')
                    ->first();

                if (!$kas) {
                    throw new \Exception("Kas kecil untuk jenis_barang_id {$jenisId} belum dibuat.");
                }

                $total_qty       = $rows->sum(fn($r) => $r->qty);
                $total_nominal   = $rows->sum(fn($r) => $r->subtotal);
                $total_hpp       = $rows->sum(fn($r) => $r->total_hpp);
                $total_hpp_batch = $rows->sum(fn($r) => $r->total_hpp_batch);

                // Ambil atau buat TransaksiKasirHarian
                $rekap = TransaksiKasirHarian::firstOrNew([
                    'toko_id'         => $header->toko_id,
                    'tanggal'         => Carbon::parse($header->tanggal)->toDateString(),
                    'jenis_barang_id' => $jenisId,
                    'kas_id'          => $kas->id,
                ]);

                if (!$rekap->exists) {
                    $rekap->kas_id          = $kas->id;
                    $rekap->total_transaksi = 1;
                    $rekap->total_qty       = $total_qty;
                    $rekap->total_nominal   = $total_nominal;
                    $rekap->total_diskon    = 0;
                    $rekap->total_bayar     = $total_nominal;
                    $rekap->total_hpp       = $total_hpp;
                    $rekap->total_hpp_batch = $total_hpp_batch;
                } else {
                    $rekap->total_transaksi += 1;
                    $rekap->total_qty       += $total_qty;
                    $rekap->total_nominal   += $total_nominal;
                    $rekap->total_bayar     += $total_nominal;
                    $rekap->total_hpp       += $total_hpp;
                    $rekap->total_hpp_batch  += $total_hpp_batch; // selalu latest
                }

                $rekap->updated_by = $header->created_by;
                $rekap->save();

                // Gunakan TransaksiKasirHarian sebagai sumber KasTransaksi
                KasRekapHelper::syncKasFromKasir(
                    toko_id: $header->toko_id,
                    jenis_barang_id: $jenisId,
                    kas_id: $kas->id,
                    tanggal: $header->tanggal,
                    pendapatan: $total_nominal,
                    beban: $total_hpp,
                    sumber: $rekap
                );
            }

            return $header->load('details');
        });
    }

    public function detail(string $publicId): array
    {
        return $this->repository->detailByPublicId($publicId);
    }

    public function print(string $publicId): array
    {
        return $this->repository->print($publicId);
    }

    public function delete(string $publicId, array $data): bool
    {
        return DB::transaction(function () use ($publicId, $data) {

            $header = TransaksiKasir::where('public_id', $publicId)->firstOrFail();

            $details = TransaksiKasirDetail::with(
                'stockBarangBatch.stockBarang.barang'
            )->where('transaksi_kasir_id', $header->id)->get();

            if ($details->isEmpty()) {
                return false;
            }

            $tokoId  = $header->toko_id;
            $tanggal = Carbon::parse($header->tanggal);
            $tahun   = $tanggal->year;
            $bulan   = $tanggal->month;

            /**
             * =====================================================
             * 1. GROUP DETAIL BERDASARKAN JENIS BARANG
             * =====================================================
             */
            $grouped = $details->groupBy(function ($detail) {
                $jenisId = $detail->stockBarangBatch
                    ->stockBarang
                    ->barang
                    ->jenis_barang_id ?? null;

                if (!$jenisId) {
                    throw new \Exception(
                        "Jenis barang tidak ditemukan untuk detail ID {$detail->id}"
                    );
                }

                return $jenisId;
            });

            /**
             * =====================================================
             * 2. ROLLBACK KAS, LABA RUGI, REKAP HARIAN
             * =====================================================
             */
            foreach ($grouped as $jenisId => $rows) {

                $rekap = TransaksiKasirHarian::where([
                    'toko_id'         => $tokoId,
                    'tanggal'         => $tanggal->toDateString(),
                    'jenis_barang_id' => $jenisId,
                ])->first();

                if (!$rekap) continue;

                $kasTransaksi = KasTransaksi::where([
                    'kas_id'      => $rekap->kas_id,
                    'sumber_type' => TransaksiKasirHarian::class,
                    'sumber_id'   => $rekap->id,
                ])->first();

                /**
                 * -------------------------------
                 * HITUNG NILAI TRANSAKSI INI
                 * -------------------------------
                 */
                $nominalTrx = $rows->sum('subtotal');
                $qtyTrx     = $rows->sum('qty');

                $hppTrx = $rows->sum(function ($detail) {
                    return $detail->qty * $detail->stockBarangBatch->stockBarang->hpp_baru;
                });

                $hppBatchTrx = $rows->sum(function ($detail) {
                    return $detail->qty * $detail->stockBarangBatch->hpp_baru;
                });


                /**
                 * -------------------------------
                 * 1. KURANGI KAS TRANSAKSI
                 * -------------------------------
                 */
                if ($kasTransaksi) {
                    $kasTransaksi->total_nominal = max(
                        0,
                        $kasTransaksi->total_nominal - $nominalTrx
                    );
                    $kasTransaksi->save();
                }

                /**
                 * -------------------------------
                 * 2. KURANGI SALDO KAS
                 * -------------------------------
                 */
                $kas = Kas::find($rekap->kas_id);
                if ($kas) {
                    $kas->saldo = max(0, $kas->saldo - $nominalTrx);
                    $kas->save();
                }

                $history = KasSaldoHistory::where([
                    'kas_id' => $kas->id,
                    'tahun'  => $tahun,
                    'bulan'  => $bulan,
                ])->first();

                if ($history) {
                    $history->saldo_akhir = max(0, $kas->saldo);
                    $history->save();
                }

                /**
                 * -------------------------------
                 * 3. ROLLBACK LABA RUGI BULANAN
                 * -------------------------------
                 */
                $labaRugi = LabaRugi::where([
                    'toko_id' => $tokoId,
                    'tahun'   => $tahun,
                    'bulan'   => $bulan,
                ])->first();

                if ($labaRugi) {
                    $labaRugi->pendapatan = max(0, $labaRugi->pendapatan - $nominalTrx);
                    $labaRugi->beban      = max(0, $labaRugi->beban - $hppTrx);
                    $labaRugi->laba_bersih =
                        $labaRugi->pendapatan - $labaRugi->beban;
                    $labaRugi->save();
                }

                /**
                 * -------------------------------
                 * 4. ROLLBACK LABA RUGI TAHUNAN
                 * -------------------------------
                 */
                $labaRugiTahunan = LabaRugiTahunan::where([
                    'toko_id' => $tokoId,
                    'tahun'   => $tahun,
                ])->first();

                if ($labaRugiTahunan) {
                    $labaRugiTahunan->pendapatan = max(
                        0,
                        $labaRugiTahunan->pendapatan - $nominalTrx
                    );
                    $labaRugiTahunan->beban = max(
                        0,
                        $labaRugiTahunan->beban - $hppTrx
                    );
                    $labaRugiTahunan->laba_bersih =
                        $labaRugiTahunan->pendapatan - $labaRugiTahunan->beban;
                    $labaRugiTahunan->save();
                }

                /**
                 * -------------------------------
                 * 5. UPDATE REKAP (TANPA TRANSAKSI COUNT)
                 * -------------------------------
                 */
                $rekap->total_qty       = max(0, $rekap->total_qty - $qtyTrx);
                $rekap->total_nominal   = max(0, $rekap->total_nominal - $nominalTrx);
                $rekap->total_bayar     = max(0, $rekap->total_bayar - $nominalTrx);
                $rekap->total_hpp       = max(0, $rekap->total_hpp - $hppTrx);
                $rekap->total_hpp_batch = max(0, $rekap->total_hpp_batch - $hppBatchTrx);

                $rekap->save();
            }

            /**
             * =====================================================
             * 3. KURANGI TOTAL_TRANSAKSI SEKALI SAJA
             * =====================================================
             */
            foreach ($grouped as $jenisId => $rows) {

                $rekap = TransaksiKasirHarian::where([
                    'toko_id'         => $tokoId,
                    'tanggal'         => $tanggal->toDateString(),
                    'jenis_barang_id' => $jenisId,
                ])->first();

                if (!$rekap) continue;

                // transaksi cuma dikurangin SEKALI per rekap
                if ($rekap->total_transaksi > 0) {
                    $rekap->total_transaksi -= 1;
                    $rekap->save();
                }
            }

            /**
             * =====================================================
             * 4. ROLLBACK STOK
             * =====================================================
             */
            foreach ($details as $detail) {
                $batch  = $detail->stockBarangBatch;
                $barang = $batch->stockBarang;

                $batch->qty_sisa += $detail->qty;
                $batch->save();

                $barang->stok += $detail->qty;
                $barang->save();
            }

            /**
             * =====================================================
             * 5. DELETE DETAIL & HEADER
             * =====================================================
             */
            foreach ($details as $detail) {
                $detail->deleted_by = $data['deleted_by'] ?? null;
                $detail->save();
                $detail->delete();
            }

            if ($header) {
                $header->deleted_by = $data['deleted_by'] ?? null;
                $header->save();
                $header->delete();
            }

            return true;
        });
    }

    public function count()
    {
        return $this->repository->count();
    }
}
