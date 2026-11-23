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
use Carbon\Carbon;
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

    public function __construct(
        ReturMemberRepository $repository,
        ReturMemberDetailRepository $detailRepo,
        KasirDetailRepository $kasirRepo,
        StokBarangDetailRepository $stokDetailRepo,
        StokBarangRepository $stokRepo,
        ReturMemberDetailBatchRepository $returMemberDetailBatchRepository
    ) {
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

                $totalHpp += (float) $d->total_hpp;
                $totalRefund += (float) $d->total_refund;
            }

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
                'tanggal' => $item->tanggal?->format('d-m-Y H:i:s'),
                'created_by' => $item->createdBy->nama ?? 'System',
                'keterangan' => implode(', ', $keterangan),
                'total_hpp_barang' => 'Rp '.number_format($totalHpp, 0, ',', '.'),
                'total_refund' => 'Rp '.number_format($totalRefund, 0, ',', '.'),
            ];
        });

        return [
            'data' => [
                'item' => $data,
                'total' => $this->getTotalHarga(),
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
            'tanggal' => $item->tanggal?->format('d-m-Y H:i:s'),
            'created_by' => $item->createdBy->nama ?? null,
            'nama_toko' => $item->createdBy->toko->nama ?? ($item->toko->nama ?? null),
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
            /** @var StockBulananService $stockBulananService */
            $stockBulananService = app(StockBulananService::class);

            $tokoId = (int) ($data['toko_id'] ?? 0);
            $tanggalStr = $this->normalizeTanggal($data['tanggal'] ?? now());

            $retur = $this->repository->create([
                'toko_id' => $tokoId ?: null,
                'member_id' => ($data['member_id'] ?? null) === 'guest' ? null : ($data['member_id'] ?? null),
                'status' => $data['status'] ?? 'draft',
                'tanggal' => $tanggalStr,
                'created_by' => $data['created_by'],
            ]);

            $kasGrouped = [];

            foreach ($data['items'] as $detail) {
                $tipeKompensasi = $this->resolveTipeKompensasi($detail);
                $qtyRequest = (int) ($detail['qty_request'] ?? 0);
                $qtyBarang = (int) ($detail['qty_barang'] ?? 0);
                $qtyRefund = (int) ($detail['qty_refund'] ?? 0);
                $totalRefund = (float) ($detail['total_refund'] ?? 0);
                $hargaJual = (float) ($detail['harga_jual'] ?? 0);
                $hpp = (float) ($detail['hpp'] ?? 0);
                $nilaiRetur = (float) ($detail['total_hpp'] ?? ($qtyRequest * $hpp));

                $detail['tipe_kompensasi'] = $tipeKompensasi;
                $detail['retur_id'] = $retur->id;
                $detail['total_hpp'] = $nilaiRetur;

                if (! empty($detail['transaksi_kasir_detail_id'])) {
                    $kasirList = $this->kasirRepo->findByDetailId($detail['transaksi_kasir_detail_id']);
                    $kasirItems = is_iterable($kasirList) ? $kasirList : array_filter([$kasirList]);

                    foreach ($kasirItems as $kasir) {
                        $newRetureQty = ($kasir->retur_qty ?? 0) + $qtyRequest;

                        $this->kasirRepo->update($kasir->id, [
                            'retur_qty' => $newRetureQty,
                            'retur_by' => $data['created_by'],
                        ]);
                    }
                }

                $returDetail = $this->detailRepo->create($detail);

                $barang = Barang::with('jenis')->find($detail['barang_id']);

                // Kompensasi REFUND: tidak mengubah stock_barang_bulanan, hanya kas & margin laba rugi
                if ($tipeKompensasi === 'refund') {
                    if ($barang && $qtyRefund > 0 && $totalRefund > 0) {
                        $totalHargaJual = $hargaJual * $qtyRefund;
                        $totalHppRefund = $hpp * $qtyRefund;

                        if ($totalHargaJual < $totalHppRefund) {
                            throw ValidationException::withMessages([
                                'harga_jual' => "Data anomali: Total harga jual lebih kecil dari total HPP untuk barang ID {$detail['barang_id']}.",
                            ]);
                        }

                        $margin = $totalHargaJual - $totalHppRefund;
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

                    continue;
                }

                // Kompensasi BARANG: proses pengeluaran stok/batch barang pengganti & rekap bulanan
                if ($qtyBarang > 0) {
                    $stok = $this->stokRepo->findByBarangId($detail['barang_id']);
                    if (! $stok || $stok->stok < $qtyBarang) {
                        throw ValidationException::withMessages([
                            'qty_barang' => "Stok barang tidak mencukupi untuk barang ID {$detail['barang_id']}.",
                        ]);
                    }

                    $this->stokRepo->update($stok->id, [
                        'stok' => $stok->stok - $qtyBarang,
                    ]);

                    $qtyNeeded = $qtyBarang;
                    $stokDetails = $this->stokDetailRepo->findAvailableByBarangId($detail['barang_id'], $tokoId);
                    $totalNilaiKeluar = 0.0;

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

                            $totalNilaiKeluar += (float) ($qtyNeeded * ($ds->harga_beli ?? 0));
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

                            $totalNilaiKeluar += (float) ($usedQty * ($ds->harga_beli ?? 0));
                            $qtyNeeded -= $usedQty;
                        }
                    }

                    if ($qtyNeeded > 0) {
                        throw ValidationException::withMessages([
                            'qty_sisa' => "Stok detail barang ID {$detail['barang_id']} tidak cukup. Sisa stok global sudah tidak valid.",
                        ]);
                    }

                    if ($barang && $tokoId > 0 && $barang->jenis_barang_id) {
                        $stockBulananService->tambahStokKeluar(
                            tokoId: $tokoId,
                            jenisBarangId: (int) $barang->jenis_barang_id,
                            qty: $qtyBarang,
                            nilaiAset: $totalNilaiKeluar,
                            tanggal: $tanggalStr
                        );
                    }
                }

                if ($barang && $tokoId > 0 && $barang->jenis_barang_id && $qtyRequest > 0) {
                    $stockBulananService->catatRetur(
                        tokoId: $tokoId,
                        jenisBarangId: (int) $barang->jenis_barang_id,
                        qty: $qtyRequest,
                        nilaiRetur: $nilaiRetur,
                        tanggal: $tanggalStr
                    );
                }
            }

            foreach ($kasGrouped as $group) {
                if ($group['total_nominal'] <= 0) {
                    continue;
                }

                KasService::out(
                    toko_id: $tokoId,
                    jenis_barang_id: $group['jenis_barang_id'],
                    tipe_kas: 'kecil',
                    total_nominal: $group['total_nominal'],
                    item: 'kecil',
                    kategori: 'Retur Transaksi Kasir',
                    keterangan: 'Retur '.$group['nama_jenis'],
                    sumber: $retur,
                    tanggal: $tanggalStr,
                    laba: false
                );

                if ($group['margin'] <= 0) {
                    continue;
                }

                $carbonDate = Carbon::parse($tanggalStr);
                KasService::updateLabaRugi(
                    tokoId: $tokoId,
                    tahun: $carbonDate->year,
                    bulan: $carbonDate->month,
                    tipe: 'out',
                    nominal: $group['margin']
                );
            }

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

            if ($qtyNowDetail !== null && $qtyNowDetail < $returQty) {
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
            $existingRetur = $this->repository->findWithDetails($id);
            if (! $existingRetur) {
                throw new \Exception("Data retur member dengan ID {$id} tidak ditemukan.");
            }

            $newTanggalStr = isset($data['tanggal'])
                ? $this->normalizeTanggal($data['tanggal'])
                : $this->normalizeTanggal($existingRetur->tanggal);

            $retur = $this->repository->update($id, [
                'status' => $data['status'] ?? $existingRetur->status,
                'tanggal' => $newTanggalStr,
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

            return $retur->load('detail.batch');
        });
    }

    public function delete($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            /** @var StockBulananService $stockBulananService */
            $stockBulananService = app(StockBulananService::class);

            $retur = $this->repository->findWithDetails($id);

            if (! $retur) {
                throw new \Exception("Data retur member dengan ID {$id} tidak ditemukan.");
            }

            $tokoId = (int) ($retur->toko_id ?? 0);
            $tanggalRetur = $this->normalizeTanggal($retur->tanggal);

            foreach ($retur->detail as $detail) {
                $barang = $detail->barang ?? Barang::with('jenis')->find($detail->barang_id);
                $tipeKompensasi = $this->resolveTipeKompensasi((array) $detail->toArray());

                // A. Rollback retur_qty dan retur_by pada transaksi kasir asal
                if ($detail->transaksi_kasir_detail_id) {
                    $kasirList = $this->kasirRepo->findByDetailId($detail->transaksi_kasir_detail_id);
                    $kasirItems = is_iterable($kasirList) ? $kasirList : array_filter([$kasirList]);

                    foreach ($kasirItems as $kasir) {
                        $newReturQty = max(0, ($kasir->retur_qty ?? 0) - ($detail->qty_request ?? 0));

                        $updateData = [
                            'retur_qty' => $newReturQty,
                        ];

                        if ($newReturQty === 0) {
                            $updateData['retur_by'] = null;
                        } else {
                            $lastActiveRetur = $this->detailRepo->getModel()::where('transaksi_kasir_detail_id', $detail->transaksi_kasir_detail_id)
                                ->where('retur_id', '!=', $retur->id)
                                ->whereHas('retur', function ($q) {
                                    $q->whereNull('deleted_at');
                                })
                                ->latest()
                                ->first();

                            if ($lastActiveRetur && isset($lastActiveRetur->retur->created_by)) {
                                $updateData['retur_by'] = $lastActiveRetur->retur->created_by;
                            }
                        }

                        $this->kasirRepo->update($kasir->id, $updateData);
                    }
                }

                // B. Rollback margin laba rugi jika item ini kompensasi refund
                if ($tipeKompensasi === 'refund') {
                    if (($detail->qty_refund ?? 0) > 0 && ($detail->total_refund ?? 0) > 0) {
                        $totalHargaJual = ($detail->harga_jual ?? 0) * $detail->qty_refund;
                        $totalHpp = ($detail->hpp ?? 0) * $detail->qty_refund;
                        $margin = $totalHargaJual - $totalHpp;

                        if ($margin > 0 && $tokoId > 0) {
                            $carbonDate = Carbon::parse($tanggalRetur);
                            KasService::updateLabaRugi(
                                tokoId: $tokoId,
                                tahun: $carbonDate->year,
                                bulan: $carbonDate->month,
                                tipe: 'in',
                                nominal: $margin
                            );
                        }
                    }

                    continue;
                }

                // C. Rollback barang pengganti (master stok, batch, dan rekap keluar bulanan)
                $qtyBarang = (int) ($detail->qty_barang ?? 0);
                if ($qtyBarang > 0) {
                    $stok = $this->stokRepo->findByBarangId($detail->barang_id);
                    if ($stok) {
                        $this->stokRepo->update($stok->id, [
                            'stok' => $stok->stok + $qtyBarang,
                        ]);
                    }

                    $totalNilaiKeluar = 0.0;
                    $hasBatch = false;

                    foreach ($detail->batch as $detailBatch) {
                        $hasBatch = true;
                        $batch = $this->stokDetailRepo->find($detailBatch->stock_barang_batch_id);
                        if ($batch) {
                            $this->stokDetailRepo->update($batch->id, [
                                'qty_sisa' => $batch->qty_sisa + $detailBatch->qty,
                            ]);
                            $totalNilaiKeluar += (float) ($detailBatch->qty * ($batch->harga_beli ?? 0));
                        }
                    }

                    if (! $hasBatch) {
                        $totalNilaiKeluar = (float) ($qtyBarang * ($detail->hpp ?? 0));
                    }

                    if ($barang && $barang->jenis_barang_id && $tokoId > 0) {
                        $stockBulananService->kurangiStokKeluar(
                            tokoId: $tokoId,
                            jenisBarangId: (int) $barang->jenis_barang_id,
                            qty: $qtyBarang,
                            nilaiAset: $totalNilaiKeluar,
                            tanggal: $tanggalRetur
                        );
                    }
                }

                // D. Rollback rekap retur bulanan untuk item kompensasi barang
                $qtyRequest = (int) ($detail->qty_request ?? 0);
                if ($barang && $barang->jenis_barang_id && $qtyRequest > 0 && $tokoId > 0) {
                    $nilaiRetur = (float) ($detail->total_hpp ?? ($qtyRequest * ($detail->hpp ?? 0)));

                    $stockBulananService->rollbackRetur(
                        tokoId: $tokoId,
                        jenisBarangId: (int) $barang->jenis_barang_id,
                        qty: $qtyRequest,
                        nilaiRetur: $nilaiRetur,
                        tanggal: $tanggalRetur
                    );
                }
            }

            // Rollback/Hapus Transaksi Kas terkait retur
            KasService::deleteRetur(
                sumberId: $retur->id,
                sumberType: get_class($retur),
                tanggal: $tanggalRetur,
                laba: false
            );

            return $this->repository->delete($retur, $data);
        });
    }

    private function resolveTipeKompensasi(array $detail): string
    {
        if (! empty($detail['tipe_kompensasi'])) {
            return (string) $detail['tipe_kompensasi'];
        }

        $qtyBarang = (int) ($detail['qty_barang'] ?? 0);
        $qtyRefund = (int) ($detail['qty_refund'] ?? 0);

        if ($qtyRefund > 0 && $qtyBarang <= 0) {
            return 'refund';
        }

        return 'barang';
    }

    private function normalizeTanggal($tanggal): string
    {
        if ($tanggal instanceof Carbon) {
            return $tanggal->toDateTimeString();
        }

        if (empty($tanggal)) {
            return Carbon::now()->toDateTimeString();
        }

        return Carbon::parse((string) $tanggal)->toDateTimeString();
    }
}
