<?php

namespace App\Services;

use App\Helpers\AssetGenerate;
use App\Models\Barang;
use App\Repositories\KasirDetailRepository;
use App\Repositories\ReturMemberDetailBatchRepository;
use App\Repositories\ReturMemberDetailRepository;
use App\Repositories\ReturMemberRepository;
use App\Repositories\StokBarangDetailRepository;
use App\Repositories\StokBarangRepository;
use App\Traits\PaginateResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturMemberService
{
    use PaginateResponse;

    protected $repository;

    protected $detailRepo;

    protected $kasirRepo;

    protected $stokRepo;

    protected $stokDetailRepo;

    protected $returMemberDetailBatchRepository;

    public function __construct(ReturMemberRepository $repository, ReturMemberDetailRepository $detailRepo, KasirDetailRepository $kasirRepo, StokBarangDetailRepository $stokDetailRepo, StokBarangRepository $stokRepo, ReturMemberDetailBatchRepository $returMemberDetailBatchRepository)
    {
        $this->repository = $repository;
        $this->detailRepo = $detailRepo;
        $this->kasirRepo = $kasirRepo;
        $this->stokRepo = $stokRepo;
        $this->stokDetailRepo = $stokDetailRepo;
        $this->returMemberDetailBatchRepository = $returMemberDetailBatchRepository;
    }

    public function getTotalHarga()
    {
        $hpp = $this->detailRepo->sumHppBarang();
        $refund = $this->detailRepo->sumRefund();

        return [
            'hpp' => [
                'total' => $hpp,
                'format' => 'Rp '.number_format($hpp, 0, ',', '.'),
            ],
            'refund' => [
                'total' => $refund,
                'format' => 'Rp '.number_format($refund, 0, ',', '.'),
            ],
        ];
    }

    public function getAll($filter)
    {
        $query = $this->repository->getAll($filter);

        $data = collect($query->items())->map(function ($item) {
            // ambil semua detail untuk retur ini
            $details = $this->detailRepo->getByReturId($item->id);

            $totalDiganti = 0;
            $totalRefundQty = 0;
            $totalHpp = 0;
            $totalRefund = 0;

            foreach ($details as $d) {
                if (! empty($d->qty_barang)) {
                    $totalDiganti += $d->qty_barang;
                }
                if (! empty($d->qty_refund)) {
                    $totalRefundQty += $d->qty_refund;
                }

                // sum nilai uang
                $totalHpp += (float) $d->total_hpp;
                $totalRefund += (float) $d->total_refund;
            }

            // susun keterangan
            $keterangan = [];
            if ($totalDiganti > 0) {
                $keterangan[] = "{$totalDiganti} diganti";
            }
            if ($totalRefundQty > 0) {
                $keterangan[] = "{$totalRefundQty} direfund";
            }

            return [
                'id' => $item->id,
                'status' => $item->status,
                'toko' => $item->toko->nama ?? null,
                'member' => $item->member->nama ?? 'Guest',
                'tanggal' => $item->tanggal->format('d-m-Y H:i:s'),
                'created_by' => $item->createdBy->nama ?? 'System',
                'keterangan' => implode(', ', $keterangan),
                'total_hpp_barang' => 'Rp '.number_format($totalHpp, 0, ',', '.'),
                'total_refund' => 'Rp '.number_format($totalRefund, 0, ',', '.'),
            ];
        });

        return [
            'data' => [
                'item' => $data,
                'total' => $this->getTotalHarga(), // total keseluruhan
            ],
            'pagination' => $this->setPaginate($query),
        ];
    }

    public function getDetail($filter)
    {
        $item = $this->repository->getDetailById($filter->id);
        $itemFormatted = [
            'id' => $item->id,
            'status' => $item->status,
            'member' => $item->member->nama ?? 'Guest',
            'tanggal' => $item->tanggal->format('d-m-Y H:i:s') ?? null,
            'created_by' => $item->createdBy->nama,
            'nama_toko' => $item->createdBy->toko->nama,
        ];

        $query = $this->detailRepo->getAll($filter);
        $data = collect($query->items())->map(function ($detail) {

            $stock = $detail->batch?->map(function ($batch) {

                $stokDetail = $batch->stokDetail;

                $qty = $batch->qty ?? 0;
                $qrcode = $stokDetail?->qrcode ?? '-';
                $tanggal = $stokDetail?->created_at
                    ? $stokDetail->created_at->format('d-m-Y H:i:s')
                    : '-';

                $img = $qrcode !== '-'
                    ? AssetGenerate::build("qrcodes/pembelian/{$qrcode}.png")
                    : null;

                return [
                    'qty' => $qty,
                    'qrcode' => $qrcode,
                    'created_at' => $tanggal,
                    'html' => "
                <div style='display:flex;align-items:center;gap:8px;' class='p-1'>
                    ".($img ? "<img src='{$img}' width='28' height='28' style='border-radius:3px;'>" : '')."
                    <div style='display:flex;flex-direction:column;line-height:1.2;'>
                        <span style='font-weight:550;font-size:12px;'>{$qrcode}</span>
                        <small class='text-dark'>
                            Stok: {$qty} — Tgl masuk: {$tanggal}
                        </small>
                    </div>
                </div>
            ",
                ];
            })->values();

            return [
                'id' => $detail->id,
                'barang' => $detail->barang->nama ?? null,
                'supplier' => $detail->supplier->nama ?? null,
                'tipe_kompensasi' => $detail->tipe_kompensasi,
                'format_harga_jual' => 'Rp '.number_format($detail->harga_jual, 0, ',', '.'),
                'format_hpp' => 'Rp '.number_format($detail->hpp, 0, ',', '.'),
                'format_total_hpp_barang' => 'Rp '.number_format($detail->total_hpp_barang, 0, ',', '.'),
                'format_jumlah_refund' => 'Rp '.number_format($detail->jumlah_refund, 0, ',', '.'),
                'format_total_refund' => 'Rp '.number_format($detail->total_refund, 0, ',', '.'),
                'qty_request' => $detail->qty_request,
                'qty_barang' => $detail->qty_barang,
                'qty_refund' => $detail->qty_refund,
                'qty_ke_supplier' => $detail->qty_ke_supplier ?? 0,
                'stock' => $stock,
            ];
        });

        return [
            'data' => [
                'item' => $itemFormatted,
                'detail' => $data,
            ],
            'pagination' => $this->setPaginate($query),
        ];
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $stockBulananService = app(\App\Services\StockBulananService::class);

            $retur = $this->repository->create([
                'toko_id' => $data['toko_id'] ?? null,
                'member_id' => ($data['member_id'] ?? null) === 'guest' ? null : $data['member_id'],
                'status' => $data['status'] ?? 'draft',
                'tanggal' => $data['tanggal'],
                'created_by' => $data['created_by'],
            ]);

            $kasGrouped = [];

            foreach ($data['items'] as $detail) {
                $detail['retur_id'] = $retur->id;

                $kasirList = $this->kasirRepo->findByDetailId($detail['transaksi_kasir_detail_id']);

                foreach ($kasirList as $kasir) {
                    $newRetureQty = ($kasir->retur_qty ?? 0) + $detail['qty_request'];

                    $this->kasirRepo->update($kasir->id, [
                        'retur_qty' => $newRetureQty,
                        'retur_by' => $data['created_by'],
                    ]);
                }
                $detail['total_hpp'] = ($detail['qty_request'] ?? 0) * ($detail['hpp'] ?? 0);

                $returDetail = $this->detailRepo->create($detail);

                $barang = Barang::with('jenis')->find($detail['barang_id']);

                if ($barang) {
                    $qtyRefund = $detail['qty_refund'] ?? 0;
                    $totalRefund = $detail['total_refund'] ?? 0;
                    $hargaJual = $detail['harga_jual'] ?? 0;
                    $hpp = $detail['hpp'] ?? 0;

                    if ($qtyRefund > 0 && $totalRefund > 0) {
                        $totalHargaJual = $hargaJual * $qtyRefund;
                        $totalHpp = $hpp * $qtyRefund;

                        if ($totalHargaJual < $totalHpp) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'harga_jual' => "Data anomali: Total harga jual lebih kecil dari total HPP untuk barang ID {$detail['barang_id']}.",
                            ]);
                        }

                        $margin = $totalHargaJual - $totalHpp;
                        $jenisId = $barang->jenis_barang_id;

                        if (! isset($kasGrouped[$jenisId])) {
                            $kasGrouped[$jenisId] = [
                                'jenis_barang_id' => $jenisId,
                                'nama_jenis' => $barang->jenis->nama_jenis_barang ?? '',
                                'total_nominal' => 0,
                                'margin' => 0,
                            ];
                        }

                        $kasGrouped[$jenisId]['total_nominal'] += $totalRefund;
                        $kasGrouped[$jenisId]['margin'] += $margin;
                    }

                    // =========================================================
                    // 🟢 REKAP RETUR MASUK (BARANG YG DIRETUR KONSUMEN)
                    // =========================================================
                    if (! empty($detail['qty_request']) && $detail['qty_request'] > 0) {
                        $stockBulananService->tambahStokMasuk(
                            tokoId: $data['toko_id'],
                            jenisBarangId: $barang->jenis_barang_id,
                            qty: $detail['qty_request'],
                            nilaiAset: $detail['total_hpp'],
                            tanggal: $data['tanggal']
                        );
                    }
                }

                // =========================================================
                // 🔴 PENGELUARAN BARANG PENGGANTI (STOK KELUAR)
                // =========================================================
                if (! empty($detail['qty_barang']) && $detail['qty_barang'] > 0) {
                    $stok = $this->stokRepo->findByBarangId($detail['barang_id']);
                    if (! $stok || $stok->stok < $detail['qty_barang']) {
                        throw ValidationException::withMessages([
                            'qty_barang' => "Stok barang tidak mencukupi untuk barang ID {$detail['barang_id']}.",
                        ]);
                    }

                    $this->stokRepo->update($stok->id, [
                        'stok' => $stok->stok - $detail['qty_barang'],
                    ]);

                    $qtyNeeded = $detail['qty_barang'];
                    $stokDetails = $this->stokDetailRepo->findAvailableByBarangId($detail['barang_id'], $data['toko_id']);
                    $totalNilaiKeluar = 0;

                    foreach ($stokDetails as $ds) {
                        if ($qtyNeeded <= 0) {
                            break;
                        }

                        if ($ds->qty_sisa >= $qtyNeeded) {
                            $this->stokDetailRepo->update($ds->id, [
                                'qty_sisa' => $ds->qty_sisa - $qtyNeeded,
                            ]);

                            $this->returMemberDetailBatchRepository->create([
                                'retur_member_detail_id' => $returDetail->id,
                                'stock_barang_batch_id' => $ds->id,
                                'qty' => $qtyNeeded,
                            ]);

                            $totalNilaiKeluar += $qtyNeeded * ($ds->harga_beli ?? 0);
                            $qtyNeeded = 0;
                        } else {
                            $usedQty = $ds->qty_sisa;
                            $this->stokDetailRepo->update($ds->id, [
                                'qty_sisa' => 0,
                            ]);

                            $this->returMemberDetailBatchRepository->create([
                                'retur_member_detail_id' => $returDetail->id,
                                'stock_barang_batch_id' => $ds->id,
                                'qty' => $usedQty,
                            ]);

                            $totalNilaiKeluar += $usedQty * ($ds->harga_beli ?? 0);
                            $qtyNeeded -= $usedQty;
                        }
                    }

                    if ($qtyNeeded > 0) {
                        throw ValidationException::withMessages([
                            'qty_sisa' => "Stok detail barang ID {$detail['barang_id']} tidak cukup. Sisa stok global sudah tidak valid.",
                        ]);
                    }

                    // Update rekap stok keluar bulanan
                    if ($barang) {
                        $stockBulananService->tambahStokKeluar(
                            tokoId: $data['toko_id'],
                            jenisBarangId: $barang->jenis_barang_id,
                            qty: $detail['qty_barang'],
                            nilaiAset: $totalNilaiKeluar,
                            tanggal: $data['tanggal']
                        );
                    }
                }
            }

            // Handle Kas & Laba Rugi
            foreach ($kasGrouped as $group) {
                if ($group['total_nominal'] <= 0) {
                    continue;
                }

                KasService::out(
                    toko_id: $data['toko_id'],
                    jenis_barang_id: $group['jenis_barang_id'],
                    tipe_kas: 'kecil',
                    total_nominal: $group['total_nominal'],
                    item: 'kecil',
                    kategori: 'Retur Transaksi Kasir',
                    keterangan: 'Retur '.$group['nama_jenis'],
                    sumber: $retur,
                    tanggal: $data['tanggal'],
                    laba: false
                );

                if ($group['margin'] <= 0) {
                    continue;
                }

                $tanggal = \Carbon\Carbon::parse($data['tanggal']);
                KasService::updateLabaRugi(
                    tokoId: $data['toko_id'],
                    tahun: $tanggal->year,
                    bulan: $tanggal->month,
                    tipe: 'out',
                    nominal: $group['margin']
                );
            }

            // 🟢 PERBAIKAN DI SINI: panggil 'detail.batch' (bukan 'detail.stokDetails')
            return $retur->load('detail.batch');
        });
    }

    public function getQRCode($filter)
    {
        $query = $this->kasirRepo->getQRCode($filter);

        $data = collect($query->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => "{$item->qrcode} => {$item->stockBarangBatch->stockBarang->barang->nama} ~ (Sisa: $item->qty_selisih)",
            ];
        });

        return [
            'data' => $data,
            'pagination' => $this->setPaginate($query),
        ];
    }

    public function getHargaBarang($filter)
    {
        $query = $this->kasirRepo->getHargaBarang($filter);

        $data = collect($query->items())->map(function ($item) {
            $stokDetailId = optional($item->stockBarangBatch)->id;
            $qtyNowDetail = optional($item->stockBarangBatch)->qty_sisa;
            $qtyStok = optional($item->stockBarangBatch->stockBarang)->stok;

            $returQty = $item->qty;

            // Kalau qty_now lebih kecil dari qty retur
            if ($qtyNowDetail !== null && $qtyNowDetail < $returQty) {
                // Misal: retur 5, qty_now = 2 →
                // hasil: retur 5, dengan 2 dari stok kasir, sisanya 3 kompensasi ke pembelian
                $kompensasi = $returQty - $qtyNowDetail;
            } else {
                $kompensasi = 0;
            }

            return [
                'id' => $item->id,
                'qrcode' => $item->qrcode,
                'supplier_id' => $item->stockBarangBatch->supplier_id,
                'barang' => $item->stockBarangBatch->stockBarang->barang->nama,
                'barang_id' => $item->stockBarangBatch->stockBarang->barang->id,
                'qty' => $item->qty_selisih,
                'qty_detail' => $qtyNowDetail,
                'qty_now' => $qtyStok,
                'qty_retur' => $returQty,
                'kompensasi' => $kompensasi,
                'harga' => $item->nominal,
                'hpp' => $item->stockBarangBatch->harga_beli,
                'stok_detail_id' => $stokDetailId,
            ];
        });

        return [
            'data' => $data,
            'pagination' => $this->setPaginate($query),
        ];
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $retur = $this->repository->update($id, [
                'status' => $data['status'] ?? null,
                'tanggal' => $data['tanggal'] ?? null,
                'updated_by' => $data['updated_by'],
            ]);

            if (! empty($data['items'])) {
                foreach ($data['items'] as $detail) {
                    if (isset($detail['id'])) {
                        $this->detailRepo->update($detail['id'], $detail);
                    } else {
                        $detail['retur_id'] = $retur->id;
                        $this->detailRepo->create($detail);
                    }
                }
            }

            return $retur->load('detail');
        });
    }

    public function delete($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $stockBulananService = app(\App\Services\StockBulananService::class);

            // 1. Ambil data retur lengkap dengan relasi detail -> batch
            $retur = $this->repository->findWithDetails($id);

            if (! $retur) {
                throw new \Exception("Data retur member dengan ID {$id} tidak ditemukan.");
            }

            foreach ($retur->detail as $detail) {
                // A. Rollback retur_qty pada transaksi kasir asal
                // A. Rollback retur_qty dan retur_by pada transaksi kasir asal
                if ($detail->transaksi_kasir_detail_id) {
                    $kasirList = $this->kasirRepo->findByDetailId($detail->transaksi_kasir_detail_id);
                    $kasirItems = is_iterable($kasirList) ? $kasirList : array_filter([$kasirList]);

                    foreach ($kasirItems as $kasir) {
                        // 1. Kurangi akumulasi retur_qty
                        $newReturQty = max(0, ($kasir->retur_qty ?? 0) - $detail->qty_request);

                        $updateData = [
                            'retur_qty' => $newReturQty,
                        ];

                        // 2. Jika sisa retur_qty = 0, otomatis retur_by menjadi NULL
                        if ($newReturQty === 0) {
                            $updateData['retur_by'] = null;
                        } else {
                            // 3. Jika MASIH ADA sisa retur lain, cari user dari retur terakhir yang MASIH AKTIF
                            // (Mencari retur lain selain retur yang sedang dihapus saat ini)
                            $lastActiveRetur = $this->detailRepo->getModel()::where('transaksi_kasir_detail_id', $detail->transaksi_kasir_detail_id)
                                ->where('retur_id', '!=', $retur->id) // Abaikan retur yang sedang dihapus
                                ->whereHas('retur', function ($q) {
                                    $q->whereNull('deleted_at'); // Pastikan transaksi retur utamanya masih aktif
                                })
                                ->latest()
                                ->first();

                            if ($lastActiveRetur && isset($lastActiveRetur->retur->created_by)) {
                                // Kembalikan retur_by ke ID user dari retur sebelumnya (User A)
                                $updateData['retur_by'] = $lastActiveRetur->retur->created_by;
                            }
                        }

                        $this->kasirRepo->update($kasir->id, $updateData);
                    }
                }

                // B. Rollback Rekap Stok Masuk (Barang yang dikembalikan konsumen)
                if ($detail->barang && $detail->qty_request > 0) {
                    $stockBulananService->kurangiStokMasuk(
                        tokoId: $retur->toko_id,
                        jenisBarangId: $detail->barang->jenis_barang_id,
                        qty: $detail->qty_request,
                        nilaiAset: $detail->total_hpp ?? ($detail->qty_request * $detail->hpp),
                        tanggal: $retur->tanggal
                    );
                }

                // C. Rollback Barang Pengganti (Detail -> Batch -> StockBarangBatch)
                if (! empty($detail->qty_barang) && $detail->qty_barang > 0) {
                    // Kembalikan ke master stok barang
                    $stok = $this->stokRepo->findByBarangId($detail->barang_id);
                    if ($stok) {
                        $this->stokRepo->update($stok->id, [
                            'stok' => $stok->stok + $detail->qty_barang,
                        ]);
                    }

                    $totalNilaiKeluar = 0;

                    // Penelusuran dari Detail -> Batch (ReturMemberDetailBatch) -> StockBarangBatch
                    foreach ($detail->batch as $detailBatch) {
                        $batch = $this->stokDetailRepo->find($detailBatch->stock_barang_batch_id);
                        if ($batch) {
                            $this->stokDetailRepo->update($batch->id, [
                                'qty_sisa' => $batch->qty_sisa + $detailBatch->qty,
                            ]);
                            $totalNilaiKeluar += $detailBatch->qty * ($batch->harga_beli ?? 0);
                        }
                    }

                    // Rollback Rekap Stok Keluar Bulanan
                    if ($detail->barang) {
                        $stockBulananService->kurangiStokKeluar(
                            tokoId: $retur->toko_id,
                            jenisBarangId: $detail->barang->jenis_barang_id,
                            qty: $detail->qty_barang,
                            nilaiAset: $totalNilaiKeluar,
                            tanggal: $retur->tanggal
                        );
                    }
                }

                // D. Rollback Laba Rugi jika ada Refund
                if (($detail->qty_refund ?? 0) > 0 && ($detail->total_refund ?? 0) > 0) {
                    $totalHargaJual = ($detail->harga_jual ?? 0) * $detail->qty_refund;
                    $totalHpp = ($detail->hpp ?? 0) * $detail->qty_refund;
                    $margin = $totalHargaJual - $totalHpp;

                    if ($margin > 0) {
                        $tanggal = \Carbon\Carbon::parse($retur->tanggal);
                        KasService::updateLabaRugi(
                            tokoId: $retur->toko_id,
                            tahun: $tanggal->year,
                            bulan: $tanggal->month,
                            tipe: 'in', // Kembalikan pemotongan margin
                            nominal: $margin
                        );
                    }
                }
            }

            // 2. Rollback/Hapus Transaksi Kas terkait menggunakan function khusus retur
            KasService::deleteRetur(
                sumberId: $retur->id,
                sumberType: get_class($retur),
                tanggal: $retur->tanggal,
                laba: false
            );

            // 3. Eksekusi penghapusan record di Repository
            return $this->repository->delete($retur, $data);
        });
    }
}
