<?php

namespace App\Services\TransaksiBarang;

use App\Helpers\KasRekapHelper;
use App\Helpers\NotaGenerate;
use App\Helpers\QrGenerator;
use App\Helpers\RupiahGenerate;
use App\Models\Kas;
use App\Models\KasSaldoHistory;
use App\Models\KasTransaksi;
use App\Models\LabaRugi;
use App\Models\LabaRugiTahunan;
use App\Models\StockBarangBatch;
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
                'member' => $item->member ? $item->member->nama : 'Guest',
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
                'total' => $this->repository->sumNominal($filter),
            ],
            'pagination' => $this->setPaginate($query),
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
            'pagination' => $this->setPaginate($query),
        ];
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            $memberId = $data['member_id'] === 'guest'
                ? null
                : $data['member_id'];

            $header = $this->repository->create([
                'toko_id' => $data['toko_id'],
                'nota' => NotaGenerate::build($data['toko_id'], $data['tanggal']),
                'tanggal' => $data['tanggal'],
                'total_qty' => $data['total_qty'],
                'total_nominal' => $data['total_nominal'],
                'total_bayar' => $data['total_bayar'],
                'total_diskon' => $data['total_diskon'] ?? 0,
                'metode' => $data['metode'],
                'member_id' => $memberId,
                'created_by' => $data['created_by'],
            ]);

            $savedDetails = collect($data['details'])->flatMap(function ($detail) use ($header, $data) {

                $barangId = $detail['barang_id'];
                $qtyNeed = $detail['qty'];

                $batches = StockBarangBatch::whereHas('stockBarang', function ($q) use ($barangId) {
                    $q->where('barang_id', $barangId);
                })
                    ->where('qty_sisa', '>', 0)
                    ->where('toko_id', $data['toko_id'])
                    ->orderBy('created_at')
                    ->get();

                $totalAvailable = $batches->sum('qty_sisa');

                if ($totalAvailable < $qtyNeed) {
                    throw new \Exception("Stok tidak cukup untuk barang_id {$barangId}");
                }

                $results = collect();

                foreach ($batches as $batch) {

                    if ($qtyNeed <= 0) {
                        break;
                    }

                    $ambil = min($batch->qty_sisa, $qtyNeed);

                    $child = $this->repo2->create([
                        'qrcode' => QrGenerator::build('QR-TRX-'),
                        'transaksi_kasir_id' => $header->id,
                        'stock_barang_batch_id' => $batch->id,
                        'qty' => $ambil,
                        'hpp' => $batch->stockBarang->hpp_baru,
                        'hpp_batch' => $batch->hpp_baru,
                        'harga_beli' => $batch->harga_beli,
                        'nominal' => $detail['nominal'],
                        'subtotal' => $ambil * $detail['nominal'],
                    ]);

                    $child->load('stockBarangBatch.stockBarang.barang');

                    $jenisBarangId = $child->stockBarangBatch->stockBarang->barang->jenis_barang_id;

                    if (! $jenisBarangId) {
                        throw new \Exception('Jenis barang tidak ditemukan');
                    }

                    $child->jenis_barang_id = $jenisBarangId;

                    $batch->qty_sisa -= $ambil;
                    $batch->save();

                    $stockBarang = $batch->stockBarang;
                    $stockBarang->stok -= $ambil;
                    if ($stockBarang->stok < 0) {
                        $stockBarang->stok = 0;
                    }
                    $stockBarang->save();

                    $child->total_hpp = $ambil * $stockBarang->hpp_baru;
                    $child->total_harga_beli = $ambil * $batch->harga_beli;
                    $child->total_hpp_batch = $ambil * $batch->hpp_baru;

                    $results->push($child);

                    $qtyNeed -= $ambil;
                }

                return $results;
            });

            $grouped = $savedDetails->groupBy(fn ($d) => $d->jenis_barang_id);

            foreach ($grouped as $jenisId => $rows) {

                $kas = Kas::where('toko_id', $data['toko_id'])
                    ->where('jenis_barang_id', $jenisId)
                    ->where('tipe_kas', 'kecil')
                    ->first();

                if (! $kas) {
                    throw new \Exception("Kas kecil untuk jenis_barang_id {$jenisId} belum dibuat.");
                }

                $total_qty = $rows->sum('qty');
                $total_nominal = $rows->sum('subtotal');
                $total_hpp = $rows->sum('total_hpp');

                // hasil dari harga_beli tiap batch
                $total_beban = $rows->sum('total_hpp_batch');
                $total_harga_beli = $rows->sum('total_harga_beli');

                $rekap = TransaksiKasirHarian::firstOrNew([
                    'toko_id' => $header->toko_id,
                    'tanggal' => Carbon::parse($header->tanggal)->toDateString(),
                    'jenis_barang_id' => $jenisId,
                    'kas_id' => $kas->id,
                ]);

                if (! $rekap->exists) {
                    $rekap->kas_id = $kas->id;
                    $rekap->total_transaksi = 1;
                    $rekap->total_qty = $total_qty;
                    $rekap->total_nominal = $total_nominal;
                    $rekap->total_diskon = 0;
                    $rekap->total_bayar = $total_nominal;
                    $rekap->total_hpp = $total_hpp;
                    $rekap->total_hpp_batch = $total_beban;
                    $rekap->total_harga_beli = $total_harga_beli;
                } else {
                    $rekap->total_transaksi += 1;
                    $rekap->total_qty += $total_qty;
                    $rekap->total_nominal += $total_nominal;
                    $rekap->total_bayar += $total_nominal;
                    $rekap->total_hpp += $total_hpp;
                    $rekap->total_hpp_batch += $total_beban;
                    $rekap->total_harga_beli += $total_harga_beli;
                }

                $rekap->updated_by = $header->created_by;
                $rekap->save();

                KasRekapHelper::syncKasFromKasir(
                    toko_id: $header->toko_id,
                    jenis_barang_id: $jenisId,
                    kas_id: $kas->id,
                    tanggal: $header->tanggal,
                    pendapatan: $total_nominal,

                    // beban pakai harga_beli masing-masing batch
                    beban: $total_harga_beli,

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

            $tokoId = $header->toko_id;
            $tanggal = Carbon::parse($header->tanggal);
            $tahun = $tanggal->year;
            $bulan = $tanggal->month;

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

                if (! $jenisId) {
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
                    'toko_id' => $tokoId,
                    'tanggal' => $tanggal->toDateString(),
                    'jenis_barang_id' => $jenisId,
                ])->first();

                if (! $rekap) {
                    continue;
                }

                $kasTransaksi = KasTransaksi::where([
                    'kas_id' => $rekap->kas_id,
                    'sumber_type' => TransaksiKasirHarian::class,
                    'sumber_id' => $rekap->id,
                ])->first();

                /**
                 * -------------------------------
                 * HITUNG NILAI TRANSAKSI INI
                 * -------------------------------
                 */
                $nominalTrx = $rows->sum('subtotal');
                $qtyTrx = $rows->sum('qty');

                $hppTrx = $rows->sum(function ($detail) {
                    return $detail->qty * $detail->hpp;
                });

                $hppBatchTrx = $rows->sum(function ($detail) {
                    return $detail->qty * $detail->hpp_batch;
                });

                $hargaBeliTrx = $rows->sum(function ($detail) {
                    return $detail->qty * $detail->harga_beli;
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
                    'tahun' => $tahun,
                    'bulan' => $bulan,
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
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                ])->first();

                if ($labaRugi) {
                    $labaRugi->pendapatan = max(0, $labaRugi->pendapatan - $nominalTrx);
                    $labaRugi->beban = max(0, $labaRugi->beban - $hargaBeliTrx);
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
                    'tahun' => $tahun,
                ])->first();

                if ($labaRugiTahunan) {
                    $labaRugiTahunan->pendapatan = max(
                        0,
                        $labaRugiTahunan->pendapatan - $nominalTrx
                    );
                    $labaRugiTahunan->beban = max(
                        0,
                        $labaRugiTahunan->beban - $hargaBeliTrx
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
                $rekap->total_qty = max(0, $rekap->total_qty - $qtyTrx);
                $rekap->total_nominal = max(0, $rekap->total_nominal - $nominalTrx);
                $rekap->total_bayar = max(0, $rekap->total_bayar - $nominalTrx);
                $rekap->total_hpp = max(0, $rekap->total_hpp - $hppTrx);
                $rekap->total_hpp_batch = max(0, $rekap->total_hpp_batch - $hppBatchTrx);
                $rekap->total_harga_beli = max(0, $rekap->total_harga_beli - $hargaBeliTrx);

                $rekap->save();
            }

            /**
             * =====================================================
             * 3. KURANGI TOTAL_TRANSAKSI SEKALI SAJA
             * =====================================================
             */
            foreach ($grouped as $jenisId => $rows) {

                $rekap = TransaksiKasirHarian::where([
                    'toko_id' => $tokoId,
                    'tanggal' => $tanggal->toDateString(),
                    'jenis_barang_id' => $jenisId,
                ])->first();

                if (! $rekap) {
                    continue;
                }

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
                $batch = $detail->stockBarangBatch;
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

    public function deleteDetail(int $detailId, array $data): array
    {
        return DB::transaction(function () use ($detailId, $data) {

            // 1. Ambil detail yang mau dihapus berserta relasinya
            $detail = TransaksiKasirDetail::with([
                'transaksiKasir',
                'stockBarangBatch.stockBarang.barang',
            ])->lockForUpdate()->find($detailId);

            if (! $detail) {
                throw new \Exception('Data detail transaksi tidak ditemukan.');
            }

            $header = $detail->transaksiKasir;
            if (! $header) {
                throw new \Exception('Data transaksi kasir tidak ditemukan.');
            }

            $tokoId = $header->toko_id;
            $tanggal = Carbon::parse($header->tanggal);
            $tahun = $tanggal->year;
            $bulan = $tanggal->month;

            // Ambil jenis_barang_id dari relasi item ini
            $jenisId = $detail->stockBarangBatch->stockBarang->barang->jenis_barang_id ?? null;
            if (! $jenisId) {
                throw new \Exception("Jenis barang tidak ditemukan untuk detail ID {$detail->id}");
            }

            // 2. Hitung nilai nominal rollback khusus dari ITEM INI saja
            $nominalTrx = $detail->subtotal;
            $qtyTrx = $detail->qty;
            $hppTrx = $detail->qty * $detail->hpp;
            $hppBatchTrx = $detail->qty * $detail->hpp_batch;
            $hargaBeliTrx = $detail->qty * $detail->harga_beli;

            /**
             * =====================================================
             * ROLLBACK KAS & SEJARAH SALDO
             * =====================================================
             */
            $rekap = TransaksiKasirHarian::where([
                'toko_id' => $tokoId,
                'tanggal' => $tanggal->toDateString(),
                'jenis_barang_id' => $jenisId,
            ])->first();

            if ($rekap) {
                // A. Kurangi Kas Transaksi yang terikat ke rekap harian ini
                $kasTransaksi = KasTransaksi::where([
                    'kas_id' => $rekap->kas_id,
                    'sumber_type' => TransaksiKasirHarian::class,
                    'sumber_id' => $rekap->id,
                ])->first();

                if ($kasTransaksi) {
                    $kasTransaksi->total_nominal = max(0, $kasTransaksi->total_nominal - $nominalTrx);
                    $kasTransaksi->save();
                }

                // B. Kurangi Saldo Kas
                $kas = Kas::find($rekap->kas_id);
                if ($kas) {
                    $kas->saldo = max(0, $kas->saldo - $nominalTrx);
                    $kas->save();

                    // C. Update History Saldo Akhir Bulanan
                    $history = KasSaldoHistory::where([
                        'kas_id' => $kas->id,
                        'tahun' => $tahun,
                        'bulan' => $bulan,
                    ])->first();

                    if ($history) {
                        $history->saldo_akhir = max(0, $kas->saldo);
                        $history->save();
                    }
                }
            }

            /**
             * =====================================================
             * ROLLBACK LABA RUGI BULANAN & TAHUNAN
             * =====================================================
             */
            $labaRugi = LabaRugi::where([
                'toko_id' => $tokoId,
                'tahun' => $tahun,
                'bulan' => $bulan,
            ])->first();

            if ($labaRugi) {
                $labaRugi->pendapatan = max(0, $labaRugi->pendapatan - $nominalTrx);
                $labaRugi->beban = max(0, $labaRugi->beban - $hargaBeliTrx);
                $labaRugi->laba_bersih = $labaRugi->pendapatan - $labaRugi->beban;
                $labaRugi->save();
            }

            $labaRugiTahunan = LabaRugiTahunan::where([
                'toko_id' => $tokoId,
                'tahun' => $tahun,
            ])->first();

            if ($labaRugiTahunan) {
                $labaRugiTahunan->pendapatan = max(0, $labaRugiTahunan->pendapatan - $nominalTrx);
                $labaRugiTahunan->beban = max(0, $labaRugiTahunan->beban - $hargaBeliTrx);
                $labaRugiTahunan->laba_bersih = $labaRugiTahunan->pendapatan - $labaRugiTahunan->beban;
                $labaRugiTahunan->save();
            }

            /**
             * =====================================================
             * UPDATE REKAP HARIAN (KASIR HARIAN)
             * =====================================================
             */
            if ($rekap) {
                $rekap->total_qty = max(0, $rekap->total_qty - $qtyTrx);
                $rekap->total_nominal = max(0, $rekap->total_nominal - $nominalTrx);
                $rekap->total_bayar = max(0, $rekap->total_bayar - $nominalTrx);
                $rekap->total_hpp = max(0, $rekap->total_hpp - $hppTrx);
                $rekap->total_hpp_batch = max(0, $rekap->total_hpp_batch - $hppBatchTrx);
                $rekap->total_harga_beli = max(0, $rekap->total_harga_beli - $hargaBeliTrx);

                // Cek apakah item ini adalah satu-satunya item di transaksi tersebut?
                // Kita hitung jumlah detail item yang tersisa di transaksi ini.
                $totalDetailTersisa = TransaksiKasirDetail::where('transaksi_kasir_id', $header->id)->count();

                // Jika item ini adalah item terakhir yang tersisa, maka total_transaksi di rekap berkurang 1
                if ($totalDetailTersisa <= 1) {
                    if ($rekap->total_transaksi > 0) {
                        $rekap->total_transaksi -= 1;
                    }
                }

                $rekap->save();
            }

            /**
             * =====================================================
             * ROLLBACK STOK BARANG & BATCH
             * =====================================================
             */
            $batch = $detail->stockBarangBatch;
            $barang = $batch->stockBarang;

            $batch->qty_sisa += $qtyTrx;
            $batch->save();

            $barang->stok += $qtyTrx;
            $barang->save();

            /**
             * =====================================================
             * PROSES APUS DETAIL & MANIPULASI HEADER
             * =====================================================
             */
            // Soft-delete / Hard-delete item detailnya terlebih dahulu
            $detail->deleted_by = $data['deleted_by'] ?? null;
            $detail->save();
            $detail->delete();

            // Hitung ulang sisa detail di database setelah item di atas terhapus
            $sisaDetailCount = TransaksiKasirDetail::where('transaksi_kasir_id', $header->id)->count();

            if ($sisaDetailCount === 0) {
                // Jika sudah tidak ada item sama sekali, hapus total headernya
                $header->deleted_by = $data['deleted_by'] ?? null;
                $header->save();
                $header->delete();

                return [
                    'status' => true,
                    'message' => 'Item detail berhasil dihapus. Karena merupakan item terakhir, seluruh transaksi otomatis dihapus.',
                ];
            } else {
                // Jika masih ada item lain, kurangi nominal di level header agar sinkron
                $header->total_nominal = max(0, $header->total_nominal - $nominalTrx);
                $header->total_bayar = max(0, $header->total_bayar - $nominalTrx);
                // Anda juga bisa mengurangi total_qty di header jika field tersebut ada:
                // $header->total_qty  = max(0, $header->total_qty - $qtyTrx);

                $header->save();

                return [
                    'status' => true,
                    'message' => 'Item detail berhasil dihapus. Nominal nota belanja diperbarui.',
                ];
            }
        });
    }

    public function deleteGroupedDetail(int $headerId, int $barangId, array $data): array
    {
        return DB::transaction(function () use ($headerId, $barangId, $data) {

            // 1. Kunci data header untuk mencegah race condition
            $header = TransaksiKasir::lockForUpdate()->find($headerId);
            if (! $header) {
                throw new \Exception('Data transaksi utama tidak ditemukan.');
            }

            // 2. Cari semua detail pecahan/batch yang memiliki barang_id terkait di nota ini
            $details = TransaksiKasirDetail::with(['stockBarangBatch.stockBarang.barang'])
                ->where('transaksi_kasir_id', $headerId)
                ->whereHas('stockBarangBatch.stockBarang', function ($query) use ($barangId) {
                    $query->where('barang_id', $barangId);
                })
                ->lockForUpdate()
                ->get();

            if ($details->isEmpty()) {
                throw new \Exception('Detail barang tidak ditemukan pada transaksi ini.');
            }

            $tokoId = $header->toko_id;
            $tanggal = Carbon::parse($header->tanggal);
            $tahun = $tanggal->year;
            $bulan = $tanggal->month;

            // Ambil jenis_barang_id (diasumsikan satu jenis_barang untuk barang_id yang sama)
            $jenisId = $details->first()->stockBarangBatch->stockBarang->barang->jenis_barang_id ?? null;
            if (! $jenisId) {
                throw new \Exception('Jenis barang tidak ditemukan.');
            }

            /**
             * =====================================================
             * HITUNG AKUMULASI DARI SEMUA BATCH BARANG YANG DIHAPUS
             * =====================================================
             */
            $nominalTrx = $details->sum('subtotal');
            $qtyTrx = $details->sum('qty');

            $hppTrx = $details->sum(function ($d) {
                return $d->qty * $d->hpp;
            });
            $hppBatchTrx = $details->sum(function ($d) {
                return $d->qty * $d->hpp_batch;
            });
            $hargaBeliTrx = $details->sum(function ($d) {
                return $d->qty * $d->harga_beli;
            });

            /**
             * =====================================================
             * ROLLBACK KAS & HISTORY SALDO
             * =====================================================
             */
            $rekap = TransaksiKasirHarian::where([
                'toko_id' => $tokoId,
                'tanggal' => $tanggal->toDateString(),
                'jenis_barang_id' => $jenisId,
            ])->first();

            if ($rekap) {
                // A. Kurangi Kas Transaksi
                $kasTransaksi = KasTransaksi::where([
                    'kas_id' => $rekap->kas_id,
                    'sumber_type' => TransaksiKasirHarian::class,
                    'sumber_id' => $rekap->id,
                ])->first();

                if ($kasTransaksi) {
                    $kasTransaksi->total_nominal = max(0, $kasTransaksi->total_nominal - $nominalTrx);
                    $kasTransaksi->save();
                }

                // B. Kurangi Saldo Kas Utama
                $kas = Kas::find($rekap->kas_id);
                if ($kas) {
                    $kas->saldo = max(0, $kas->saldo - $nominalTrx);
                    $kas->save();

                    // C. Update History Saldo Bulanan
                    $history = KasSaldoHistory::where([
                        'kas_id' => $kas->id,
                        'tahun' => $tahun,
                        'bulan' => $bulan,
                    ])->first();

                    if ($history) {
                        $history->saldo_akhir = max(0, $kas->saldo);
                        $history->save();
                    }
                }
            }

            /**
             * =====================================================
             * ROLLBACK LABA RUGI BULANAN & TAHUNAN
             * =====================================================
             */
            $labaRugi = LabaRugi::where(['toko_id' => $tokoId, 'tahun' => $tahun, 'bulan' => $bulan])->first();
            if ($labaRugi) {
                $labaRugi->pendapatan = max(0, $labaRugi->pendapatan - $nominalTrx);
                $labaRugi->beban = max(0, $labaRugi->beban - $hargaBeliTrx);
                $labaRugi->laba_bersih = $labaRugi->pendapatan - $labaRugi->beban;
                $labaRugi->save();
            }

            $labaRugiTahunan = LabaRugiTahunan::where(['toko_id' => $tokoId, 'tahun' => $tahun])->first();
            if ($labaRugiTahunan) {
                $labaRugiTahunan->pendapatan = max(0, $labaRugiTahunan->pendapatan - $nominalTrx);
                $labaRugiTahunan->beban = max(0, $labaRugiTahunan->beban - $hargaBeliTrx);
                $labaRugiTahunan->laba_bersih = $labaRugiTahunan->pendapatan - $labaRugiTahunan->beban;
                $labaRugiTahunan->save();
            }

            /**
             * =====================================================
             * UPDATE REKAP TRANSAKSI HARIAN
             * =====================================================
             */
            if ($rekap) {
                $rekap->total_qty = max(0, $rekap->total_qty - $qtyTrx);
                $rekap->total_nominal = max(0, $rekap->total_nominal - $nominalTrx);
                $rekap->total_bayar = max(0, $rekap->total_bayar - $nominalTrx);
                $rekap->total_hpp = max(0, $rekap->total_hpp - $hppTrx);
                $rekap->total_hpp_batch = max(0, $rekap->total_hpp_batch - $hppBatchTrx);
                $rekap->total_harga_beli = max(0, $rekap->total_harga_beli - $hargaBeliTrx);

                // Cek jumlah total detail item (all produk) yang ada di nota ini sekarang
                $totalDetailNota = TransaksiKasirDetail::where('transaksi_kasir_id', $headerId)->count();

                // Jika jumlah detail yang akan dihapus sama dengan total detail di nota,
                // artinya seluruh isi nota habis terhapus. Maka total_transaksi berkurang 1.
                if ($details->count() === $totalDetailNota) {
                    if ($rekap->total_transaksi > 0) {
                        $rekap->total_transaksi -= 1;
                    }
                }

                $rekap->save();
            }

            /**
             * =====================================================
             * ROLLBACK STOK PER MASING-MASING BATCH/PECAHAN
             * =====================================================
             */
            foreach ($details as $detail) {
                $batch = $detail->stockBarangBatch;
                $barang = $batch->stockBarang;

                // Kembalikan stok ke batch asal masing-masing
                $batch->qty_sisa += $detail->qty;
                $batch->save();

                // Kembalikan ke stok global barang
                $barang->stok += $detail->qty;
                $barang->save();

                // Soft-delete / Hard-delete item detail
                $detail->deleted_by = $data['deleted_by'] ?? null;
                $detail->save();
                $detail->delete();
            }

            /**
             * =====================================================
             * MANIPULASI / DELETE HEADER NOTA
             * =====================================================
             */
            // Cek sisa item lain di nota setelah penghapusan di atas
            $sisaDetailCount = TransaksiKasirDetail::where('transaksi_kasir_id', $headerId)->count();

            if ($sisaDetailCount === 0) {
                // Jika barang ini habis dan tidak ada barang lain di nota ini, hapus nota utama
                $header->deleted_by = $data['deleted_by'] ?? null;
                $header->save();
                $header->delete();

                return [
                    'status' => true,
                    'message' => 'Barang berhasil dihapus. Karena tidak ada produk lain, nota transaksi otomatis dihapus.',
                ];
            } else {
                // Jika masih ada jenis barang lain di nota tersebut, update akumulasi header
                $header->total_qty = max(0, $header->total_qty - $qtyTrx);
                $header->total_nominal = max(0, $header->total_nominal - $nominalTrx);
                $header->total_bayar = max(0, $header->total_bayar - $nominalTrx);
                $header->save();

                return [
                    'status' => true,
                    'message' => 'Barang berhasil dihapus dari nota. Total tagihan nota diperbarui.',
                ];
            }
        });
    }

    public function count()
    {
        return $this->repository->count();
    }
}
