<?php

namespace App\Services;

use App\Helpers\AssetGenerate;
use App\Helpers\RupiahGenerate;
use App\Helpers\TextGenerate;
use App\Models\Barang;
use App\Models\Kas;
use App\Models\PembelianBarangDetail;
use App\Models\StockBarangBatch;
use App\Repositories\KasirDetailRepository;
use App\Repositories\ReturMemberDetailRepository;
use App\Repositories\ReturSupplierDetailRepository;
use App\Repositories\ReturSupplierRepository;
use App\Repositories\ReturSupplierSummaryRepository;
use App\Repositories\StockBarangBatchRepo;
use App\Repositories\StokBarangDetailRepository;
use App\Repositories\StokBarangRepository;
use App\Traits\PaginateResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturSupplierService
{
    use PaginateResponse;

    protected $repository;

    protected $detailRepo;

    protected $returSupplierSummaryRepo;

    protected $returMemberDetailRepo;

    protected $stockBarangBatchRepo;

    protected $stokDetailRepo;

    protected $stokRepo;

    protected $detailKasirRepo;

    public function __construct(
        ReturSupplierRepository $repository,
        ReturSupplierDetailRepository $detailRepo,
        ReturSupplierSummaryRepository $returSupplierSummaryRepo,
        ReturMemberDetailRepository $returMemberDetailRepo,
        StockBarangBatchRepo $stockBarangBatchRepo,
        StokBarangDetailRepository $stokDetailRepo,
        StokBarangRepository $stokRepo,
        KasirDetailRepository $detailKasirRepo
    ) {
        $this->repository = $repository;
        $this->detailRepo = $detailRepo;
        $this->returSupplierSummaryRepo = $returSupplierSummaryRepo;
        $this->returMemberDetailRepo = $returMemberDetailRepo;
        $this->stockBarangBatchRepo = $stockBarangBatchRepo;
        $this->stokDetailRepo = $stokDetailRepo;
        $this->stokRepo = $stokRepo;
        $this->detailKasirRepo = $detailKasirRepo;
    }

    public function getTotalHarga()
    {
        $data = $this->repository->sumHppDanRefund();

        $totalBarang = 0;
        $totalRefund = 0;

        foreach ($data as $item) {
            if (! empty($item->detail)) {
                foreach ($item->detail as $detail) {
                    $totalBarang += (float) ($detail->total_hpp_barang ?? 0);
                    $totalRefund += (float) ($detail->total_refund_real ?? 0);
                }
            }
        }

        return [
            'hpp' => [
                'total' => $totalBarang,
                'format' => 'Rp ' . number_format($totalBarang, 0, ',', '.'),
            ],
            'refund' => [
                'total' => $totalRefund,
                'format' => 'Rp ' . number_format($totalRefund, 0, ',', '.'),
            ],
            'selisih' => [
                'total' => $totalBarang - $totalRefund,
                'format' => 'Rp ' . number_format($totalBarang - $totalRefund, 0, ',', '.'),
            ],
        ];
    }

    public function getAll($filter)
    {
        $query = $this->repository->getAll($filter);

        $data = collect($query->items())->map(function ($item) {
            $totalQtyDetail = 0;
            $refund = 0;
            $barang = 0;
            if (! empty($item->detail)) {
                foreach ($item->detail as $detail) {
                    $qtyBarang = $detail->qty_barang ?? 0;
                    $qtyRefund = $detail->qty_refund ?? 0;
                    $totalQtyDetail += ($qtyBarang + $qtyRefund);
                    $refund += (float) ($detail->total_refund_real ?? 0);
                    $barang += (float) ($detail->total_hpp_barang ?? 0);
                }
            }

            $verified = null;
            if ($item->status === 'proses') {
                $verified = ($totalQtyDetail === (int) $item->qty);
            }

            return [
                'id' => $item->id,
                'no_retur' => "R-{$item->id}",
                'supplier' => $item->supplier->nama ?? '-',
                'supplier_id' => $item->supplier_id ?? '-',
                'tipe_retur' => $item->tipe_retur === 'member' ? 'Retur Member' : 'Pembelian Barang',
                'tanggal' => $item->tanggal,
                'qty' => $item->qty,
                'total_refund' => RupiahGenerate::build($refund),
                'total_hpp' => RupiahGenerate::build($barang),
                'total_selisih' => RupiahGenerate::build($item->total_selisih),
                'status' => $item->status,
                'keterangan' => $item->keterangan,
                'created_by' => $item->createdBy->nama ?? 'System',
                'verified' => $verified,
            ];
        });

        return [
            'data' => [
                'item' => $data,
                'total' => $this->getTotalHarga(),
            ],
            'pagination' => ! empty($filter->limit) ? $this->setPaginate($query) : null,
        ];
    }

    public function getDetail($filter)
    {
        $item = $this->repository->getDetailById($filter->id, $filter->toko_id);
        $itemFormatted = [
            'id' => $item->id,
            'tipe_retur' => $item->tipe_retur,
            'supplier' => $item->supplier->nama ?? 'Guest',
            'tanggal' => $item->tanggal ?? null,
            'total_refund' => 'Rp ' . number_format($item->total_refund, 0, ',', '.'),
            'total_hpp' => 'Rp ' . number_format($item->total_hpp, 0, ',', '.'),
            'total_selisih' => 'Rp ' . number_format($item->total_selisih, 0, ',', '.'),
            'status' => $item->status,
            'created_by' => $item->createdBy->nama,
            'nama_toko' => $item->toko->nama,
        ];

        $query = $this->detailRepo->getAll($filter);
        $data = collect($query->items())->map(function ($detail) {
            return [
                'id' => $detail->public_id,
                'barang' => TextGenerate::smartTail($detail->barang->nama),
                'tipe_kompensasi' => $detail->tipe_kompensasi,
                'format_harga_jual' => 'Rp ' . number_format($detail->harga_jual, 0, ',', '.'),
                'format_hpp' => 'Rp ' . number_format($detail->hpp, 0, ',', '.'),
                'format_total_hpp_barang' => 'Rp ' . number_format($detail->total_hpp_barang, 0, ',', '.'),
                'format_jumlah_refund' => 'Rp ' . number_format($detail->jumlah_refund, 0, ',', '.'),
                'format_total_refund' => 'Rp ' . number_format($detail->total_refund, 0, ',', '.'),
                'format_total_hpp' => 'Rp ' . number_format($detail->total_hpp, 0, ',', '.'),
                'format_selisih' => 'Rp ' . number_format($detail->selisih, 0, ',', '.'),
                'qty_request' => $detail->qty,
                'qty_barang' => $detail->qty_barang,
                'qty_refund' => $detail->qty_refund,
                'status' => $detail->status ?? 0,
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

    public function getData($filter)
    {
        if (empty($filter->id)) {
            throw ValidationException::withMessages([
                'id' => 'Parameter ID wajib diisi.',
            ]);
        }

        return [
            'data' => $this->repository->find($filter->id, $filter->toko_id),
        ];
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            /** @var StockBulananService $stockBulananService */
            $stockBulananService = app(StockBulananService::class);

            $created = [];
            $tokoId = (int) ($data['toko_id'] ?? 0);
            $tanggalStr = $this->normalizeTanggal($data['tanggal'] ?? now());

            foreach ($data['retur'] as $returSupplier) {
                $totalQty = array_sum(array_column($returSupplier['detail'], 'qty'));
                $totalHppSupplier = 0.0;

                foreach ($returSupplier['detail'] as $d) {
                    $totalHppSupplier += ((float) ($d['hpp'] ?? 0)) * ((float) ($d['qty'] ?? 0));
                }

                $retur = $this->repository->create([
                    'toko_id' => $tokoId ?: null,
                    'supplier_id' => $returSupplier['supplier_id'],
                    'tipe_retur' => $data['tipe_retur'],
                    'tanggal' => $tanggalStr,
                    'qty' => $totalQty,
                    'total_hpp' => $totalHppSupplier > 0 ? $totalHppSupplier : ((float) ($data['total_hpp'] ?? 0)),
                    'status' => 'proses',
                    'created_by' => $data['created_by'],
                ]);

                foreach ($returSupplier['detail'] as $detail) {
                    $qtyBarang = (float) ($detail['qty'] ?? 0);
                    $hpp = (float) ($detail['hpp'] ?? 0);

                    $payload = [
                        'retur_supplier_id' => $retur->id,
                        'barang_id' => $detail['barang_id'],
                        'qty' => $qtyBarang,
                        'hpp' => $hpp,
                        'harga_jual' => (float) ($detail['harga_jual'] ?? 0),
                        'total_hpp' => $hpp * $qtyBarang,
                    ];

                    if ($data['tipe_retur'] === 'pembelian') {
                        $payload['pembelian_barang_detail_id'] = $detail['id'];
                    } elseif ($data['tipe_retur'] === 'member') {
                        $payload['retur_member_detail_id'] = $detail['id'];

                        $returMemberDetail = $this->returMemberDetailRepo->find($detail['id']);
                        if (! $returMemberDetail) {
                            throw new \Exception('Data retur member detail tidak ditemukan.');
                        }

                        $qtySebelumnya = (float) ($returMemberDetail->qty_ke_supplier ?? 0);
                        $qtyRequest = (float) ($returMemberDetail->qty_request ?? 0);
                        $qtyBaru = $qtySebelumnya + $qtyBarang;

                        if ($qtyBaru > $qtyRequest) {
                            throw new \Exception(
                                "Qty retur suplier melebihi qty request. Maksimal {$qtyRequest}, saat ini {$qtyBaru}."
                            );
                        }

                        $this->returMemberDetailRepo->update($payload['retur_member_detail_id'], [
                            'qty_ke_supplier' => $qtyBaru,
                        ]);
                    }

                    $this->detailRepo->create($payload);

                    // Retur dari stok pembelian: kurangi stok aktif & catat stok keluar bulanan
                    if ($data['tipe_retur'] === 'pembelian' && ! empty($detail['id'])) {
                        $pembelian = PembelianBarangDetail::find($detail['id']);
                        $batch = $pembelian ? StockBarangBatch::find($pembelian->stock_barang_batch_id) : null;

                        if ($batch) {
                            $stokDetail = $this->stokDetailRepo->findByPembelianWithZero($batch->id, $detail['id']);

                            if ($stokDetail) {
                                $this->stokDetailRepo->updateWithPembelian(
                                    $batch->id,
                                    $detail['id'],
                                    [
                                        'qty_sisa' => max(0, (float) $stokDetail->qty_sisa - $qtyBarang),
                                    ]
                                );

                                $stok = $this->stokRepo->find($stokDetail->stock_barang_id);
                                if ($stok) {
                                    $this->stokRepo->update($stok->id, [
                                        'stok' => max(0, (float) $stok->stok - $qtyBarang),
                                    ]);
                                }

                                $jenisBarangId = $this->resolveJenisBarangId((int) $detail['barang_id'], $stok);
                                if ($qtyBarang > 0 && $tokoId > 0 && $jenisBarangId) {
                                    $stockBulananService->tambahStokKeluar(
                                        tokoId: $tokoId,
                                        jenisBarangId: $jenisBarangId,
                                        qty: (int) round($qtyBarang),
                                        nilaiAset: $hpp * $qtyBarang,
                                        tanggal: $tanggalStr
                                    );
                                }
                            }
                        }
                    }
                }

                $created[] = $retur;
            }

            return $created;
        });
    }

    public function update(array $data)
    {
        return DB::transaction(function () use ($data) {
            $returUtama = $this->repository->find($data['id'], $data['toko_id']);
            if (! $returUtama) {
                throw ValidationException::withMessages([
                    'retur_id' => "Data retur dengan ID {$data['id']} tidak ditemukan.",
                ]);
            }

            $total_refund = (float) $data['subtotal_refund'];
            $total_hpp = (float) $data['subtotal_hpp'];
            $keteranganUtama = 'seimbang';

            if ($total_refund < $total_hpp) {
                $keteranganUtama = 'rugi';
            } elseif ($total_refund > $total_hpp) {
                $keteranganUtama = 'untung';
            }

            $this->repository->update($data['id'], [
                'toko_id' => $data['toko_id'],
                'qty' => $data['qty'],
                'total_refund' => $total_refund,
                'total_hpp' => $total_hpp,
                'total_selisih' => $data['subtotal_selisih'],
                'keterangan' => $keteranganUtama,
                'updated_by' => $data['updated_by'],
            ]);

            foreach ($data['retur'] as $returGroup) {
                foreach ($returGroup['detail'] as $detail) {
                    $existingDetail = $this->detailRepo->find($detail['id']);
                    if (! $existingDetail) {
                        throw ValidationException::withMessages([
                            'detail_id' => "Detail retur dengan ID {$detail['id']} tidak ditemukan.",
                        ]);
                    }

                    $totalRefundDetail = (float) $detail['total_refund'];
                    $totalHppDetail = (float) $detail['total_hpp'];
                    $keteranganDetail = 'seimbang';

                    if ($totalRefundDetail < $totalHppDetail) {
                        $keteranganDetail = 'rugi';
                    } elseif ($totalRefundDetail > $totalHppDetail) {
                        $keteranganDetail = 'untung';
                    }

                    $this->detailRepo->update($detail['id'], [
                        'retur_supplier_id' => $detail['retur_id'] ?? $data['id'],
                        'barang_id' => $detail['barang_id'],
                        'tipe_kompensasi' => $detail['kompensasi'],
                        'hpp' => $detail['hpp'],
                        'harga_jual' => $detail['harga_jual'],
                        'qty_refund' => $detail['qty_refund'],
                        'qty_barang' => $detail['qty_barang'],
                        'jumlah_refund' => $detail['jumlah_refund'],
                        'total_refund_real' => ((float) ($detail['qty_refund'] ?? 0)) * ((float) ($detail['jumlah_refund'] ?? 0)),
                        'total_refund' => $totalRefundDetail,
                        'total_hpp' => $totalHppDetail,
                        'total_hpp_barang' => ((float) ($detail['qty_barang'] ?? 0)) * ((float) ($detail['hpp'] ?? 0)),
                        'selisih' => $detail['selisih'],
                        'keterangan' => $keteranganDetail,
                    ]);
                }
            }

            return true;
        });
    }

    public function verify(array $data)
    {
        return DB::transaction(function () use ($data) {
            /** @var StockBulananService $stockBulananService */
            $stockBulananService = app(StockBulananService::class);

            $tokoId = (int) $data['toko_id'];
            $returUtama = $this->repository->find($data['id'], $tokoId);

            if (! $returUtama) {
                throw ValidationException::withMessages([
                    'retur_id' => "Data retur dengan ID {$data['id']} tidak ditemukan.",
                ]);
            }

            if ($returUtama->status === 'selesai') {
                throw ValidationException::withMessages([
                    'status' => 'Data retur ini sudah diverifikasi sebelumnya.',
                ]);
            }

            $verifyDate = Carbon::now();

            $this->repository->update($data['id'], [
                'status' => 'selesai',
                'updated_by' => $data['updated_by'],
                'verify_date' => $verifyDate,
            ]);

            $detailRetur = $this->detailRepo->getByRetur($data['id']);

            foreach ($detailRetur as $detail) {
                $qtyBarang = (float) ($detail->qty_barang ?? 0);
                $qtyRefund = (float) ($detail->qty_refund ?? 0);
                $hpp = (float) ($detail->hpp ?? 0);
                $jenisBarangId = $this->resolveJenisBarangId((int) $detail->barang_id, null, $detail->barang ?? null);

                // 1. Jika supplier mengirim barang pengganti (qty_barang > 0):
                // tambah stok batch & master stok, lalu tambah stock_barang_bulanan (IN)
                if ($qtyBarang > 0) {
                    if ($returUtama->tipe_retur === 'pembelian' && ! empty($detail->pembelian_barang_detail_id)) {
                        $pembelian = PembelianBarangDetail::find($detail->pembelian_barang_detail_id);
                        $batch = $pembelian ? StockBarangBatch::find($pembelian->stock_barang_batch_id) : null;

                        if ($batch) {
                            $stokDetail = $this->stokDetailRepo->findByPembelianWithZero(
                                $batch->id,
                                $detail->pembelian_barang_detail_id
                            );

                            if ($stokDetail) {
                                $this->stokDetailRepo->updateWithPembelian(
                                    $batch->id,
                                    $detail->pembelian_barang_detail_id,
                                    [
                                        'qty_sisa' => (float) $stokDetail->qty_sisa + $qtyBarang,
                                    ]
                                );

                                if (! empty($stokDetail->stock_barang_id)) {
                                    $stok = $this->stokRepo->find($stokDetail->stock_barang_id);
                                    if ($stok) {
                                        $this->stokRepo->update($stok->id, [
                                            'stok' => (float) $stok->stok + $qtyBarang,
                                        ]);
                                        $jenisBarangId = $this->resolveJenisBarangId((int) $detail->barang_id, $stok, $detail->barang ?? null);
                                    }
                                }
                            }
                        }
                    } elseif ($returUtama->tipe_retur === 'member' && ! empty($detail->retur_member_detail_id)) {
                        $memberDetail = $this->returMemberDetailRepo->find($detail->retur_member_detail_id);
                        if (! $memberDetail) {
                            throw new \Exception('Retur member detail tidak ditemukan');
                        }
                        if (empty($memberDetail->transaksi_kasir_detail_id)) {
                            throw new \Exception('transaksi_kasir_detail_id tidak ditemukan');
                        }

                        $detailKasir = $this->detailKasirRepo->findById($memberDetail->transaksi_kasir_detail_id);
                        if (! $detailKasir) {
                            throw new \Exception('Detail kasir tidak ditemukan');
                        }

                        $batch = StockBarangBatch::find($detailKasir->stock_barang_batch_id);
                        if (! $batch) {
                            throw new \Exception('Stock barang batch tidak ditemukan');
                        }
                        if (empty($batch->sumber_id)) {
                            throw new \Exception('sumber_id batch tidak ditemukan');
                        }

                        $pembelianBarangDetailId = $batch->sumber_id;
                        if (! empty($batch->sumber_type) && $batch->sumber_type !== PembelianBarangDetail::class) {
                            throw new \Exception('Sumber batch bukan pembelian barang detail');
                        }

                        $stokDetail = $this->stokDetailRepo->findByPembelianWithZero(
                            $batch->id,
                            $pembelianBarangDetailId
                        );
                        if (! $stokDetail) {
                            throw new \Exception('Stock detail tidak ditemukan');
                        }

                        $this->stokDetailRepo->updateWithPembelian(
                            $batch->id,
                            $pembelianBarangDetailId,
                            [
                                'qty_sisa' => (float) $stokDetail->qty_sisa + $qtyBarang,
                            ]
                        );

                        if (! empty($stokDetail->stock_barang_id)) {
                            $stok = $this->stokRepo->find($stokDetail->stock_barang_id);
                            if ($stok) {
                                $this->stokRepo->update($stok->id, [
                                    'stok' => (float) $stok->stok + $qtyBarang,
                                ]);
                                $jenisBarangId = $this->resolveJenisBarangId((int) $detail->barang_id, $stok, $detail->barang ?? null);
                            }
                        }
                    }

                    if ($tokoId > 0 && $jenisBarangId) {
                        $stockBulananService->tambahStokMasuk(
                            tokoId: $tokoId,
                            jenisBarangId: $jenisBarangId,
                            qty: (int) round($qtyBarang),
                            nilaiAset: $hpp * $qtyBarang,
                            tanggal: $verifyDate->toDateTimeString()
                        );
                    }
                }

                // 2. Jika kompensasi refund uang (qty_refund > 0):
                // tambah kas & proses laba rugi lewat KasService, tanpa menambah stock_barang_bulanan
                if ($qtyRefund > 0) {
                    $kas = Kas::firstOrCreate(
                        [
                            'jenis_barang_id' => $jenisBarangId,
                            'tipe_kas' => 'kecil',
                            'toko_id' => $tokoId,
                        ],
                        [
                            'saldo_awal' => 0,
                            'saldo' => 0,
                            'tanggal' => $verifyDate,
                        ]
                    );

                    $namaJenis = $detail->barang->jenis->nama_jenis_barang
                        ?? (Barang::with('jenis')->find($detail->barang_id)?->jenis->nama_jenis_barang ?? '');

                    KasService::returSupplier(
                        kas: $kas,
                        refundNominal: $qtyRefund * ((float) ($detail->jumlah_refund ?? 0)),
                        hppNominal: $qtyRefund * $hpp,
                        item: 'kecil',
                        kategori: "Retur Suplier {$returUtama->supplier->nama}",
                        keterangan: "Retur {$namaJenis}",
                        sumber: $detail,
                        tanggal: $verifyDate
                    );
                }
            }

            return true;
        });
    }

    public function getReturMember($filter)
    {
        $query = $this->returMemberDetailRepo->getAll($filter);

        $data = collect($query->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'barang_id' => $item->barang_id,
                'retur_member_detail_id' => $item->retur_member_detail_id,
                'pembelian_barang_detail_id' => $item->detailKasir->detailPembelian->id,
                'hpp' => $item->hpp,
                'harga' => $item->harga,
                'qty_request' => $item->qty_request,
            ];
        });

        return [
            'data' => [
                'item' => $data,
                'total' => $this->getTotalHarga(),
            ],
            'pagination' => ! empty($filter->limit) ? $this->setPaginate($query) : null,
        ];
    }

    public function getSupplier($filter)
    {
        $query = $this->returMemberDetailRepo->getDistinctSuppliers($filter);

        $data = collect(method_exists($query, 'items') ? $query->items() : $query)->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => $item->telepon
                    ? "{$item->nama}/{$item->telepon} ~ ({$item->total_item_retur} Item)"
                    : "{$item->nama} ~ ({$item->total_item_retur} Item)",
            ];
        });

        return [
            'data' => $data,
            'pagination' => ! empty($filter->limit) ? $this->setPaginate($query) : null,
        ];
    }

    public function getQRCode($filter)
    {
        $query = $this->stockBarangBatchRepo->getQRCode($filter);

        $data = collect($query->items())->map(function ($item) {
            $qrcode = $item->stockBarang->barang->qrcode ?? null;
            $barang = TextGenerate::smartTail($item->stockBarang->barang->nama);
            $tanggal = $item->created_at
                ? Carbon::parse($item->created_at)->format('d-m-Y H:i:s')
                : '-';
            $img = $qrcode
                ? AssetGenerate::build("qrcodes/barang/{$qrcode}.png")
                : '';

            return [
                'id' => $item->id,
                'text' => "
                    <div style='display:flex;align-items:center;gap:8px' class='p-1'>
                        <img src='{$img}' width='28' height='28' style='border-radius:3px'>

                        <div style='display:flex;flex-direction:column;line-height:1.2'>
                            <span style='font-weight:550;font-size:12px'>{$barang}</span>
                            <small class='text-dark' style='display:flex;flex-direction:column'>
                                <span>
                                    {$qrcode} — <span class='font-weight-bold'>Stok: {$item->qty_sisa}</span>
                                    <span style='font-size:11px;color:#666'>
                                        Tanggal Masuk: {$tanggal}
                                    </span>
                                </span>
                            </small>
                        </div>
                    </div>
                ",
            ];
        });

        return [
            'data' => $data,
            'pagination' => $this->setPaginate($query),
        ];
    }

    public function getHargaBarang($filter)
    {
        $query = null;

        if ($filter->tipe == 'pembelian') {
            $query = $this->stockBarangBatchRepo->getHargaBarang($filter);
        } elseif ($filter->tipe == 'member') {
            $query = $this->returMemberDetailRepo->getHargaBarang($filter);
        }

        if (! $query) {
            throw new \Exception('Tipe tidak valid');
        }

        $data = collect($query->items())->map(function ($item) use ($filter) {
            $stokDetailId = null;
            $supplierId = null;
            $namaSupplier = null;

            if ($filter->tipe == 'pembelian') {
                $stokDetailId = $item->id;
                $qtyNowDetail = $item->qty_sisa;
                $tgl = "Tgl Pembelian:<br>{$item->created_at}";
                $qrcode = $item->stockBarang->barang->qrcode;

                $supplier = optional($item->sumber?->pembelianBarang?->supplier);
                $supplierId = $supplier->id;
                $namaSupplier = $supplier->nama;

                $barang = optional($item->stockBarang?->barang);
                $barangId = $barang->id;
                $namaBarang = $barang->nama;
                $hpp = $item->stockBarang->hpp_baru;
            } else {
                $qtyNowDetail = $item->qty;
                $tgl = "Tgl Retur:<br>{$item->created_at}";
                $qrcode = $item->transaksiKasirDetail->qrcode;

                $supplier = optional($item->supplier);
                $supplierId = $supplier->id;
                $namaSupplier = $supplier->nama;

                $barang = optional($item->barang);
                $barangId = $barang->id;
                $namaBarang = $barang->nama;
                $hpp = $item->hpp;
            }

            return [
                'id' => $item->id,
                'tgl' => $tgl,
                'qrcode' => $qrcode,
                'supplier_id' => $supplierId,
                'nama_supplier' => $namaSupplier,
                'barang' => TextGenerate::smartTail($namaBarang),
                'barang_id' => $barangId,
                'qty_now' => $qtyNowDetail,
                'hpp' => $hpp,
                'harga_jual' => $item->harga_jual ?? 0,
                'stok_detail_id' => $stokDetailId,
            ];
        });

        return [
            'data' => $data,
            'pagination' => $this->setPaginate($query),
        ];
    }

    public function delete($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            /** @var StockBulananService $stockBulananService */
            $stockBulananService = app(StockBulananService::class);

            $retur = $this->repository->find($id, $data['toko_id'] ?? null);

            if ($retur) {
                $tokoId = (int) ($retur->toko_id ?? ($data['toko_id'] ?? 0));
                $tanggalStr = $this->normalizeTanggal($retur->tanggal ?? now());
                $detailList = $this->detailRepo->getByRetur($id);

                foreach ($detailList as $detail) {
                    $qtyDetail = (float) ($detail->qty ?? 0);
                    $hpp = (float) ($detail->hpp ?? 0);
                    $jenisBarangId = $this->resolveJenisBarangId((int) $detail->barang_id, null, $detail->barang ?? null);

                    if ($retur->tipe_retur === 'member' && ! empty($detail->retur_member_detail_id)) {
                        $memberDetail = $this->returMemberDetailRepo->find($detail->retur_member_detail_id);
                        if ($memberDetail) {
                            $newQty = max(0, (float) ($memberDetail->qty_ke_supplier ?? 0) - $qtyDetail);
                            $this->returMemberDetailRepo->update($memberDetail->id, [
                                'qty_ke_supplier' => $newQty,
                            ]);
                        }
                    }

                    if ($retur->tipe_retur === 'pembelian' && ! empty($detail->pembelian_barang_detail_id)) {
                        $pembelian = PembelianBarangDetail::find($detail->pembelian_barang_detail_id);
                        $batch = $pembelian ? StockBarangBatch::find($pembelian->stock_barang_batch_id) : null;

                        if ($batch) {
                            $stokDetail = $this->stokDetailRepo->findByPembelianWithZero(
                                $batch->id,
                                $detail->pembelian_barang_detail_id
                            );

                            if ($stokDetail) {
                                $this->stokDetailRepo->updateWithPembelian(
                                    $batch->id,
                                    $detail->pembelian_barang_detail_id,
                                    [
                                        'qty_sisa' => (float) $stokDetail->qty_sisa + $qtyDetail,
                                    ]
                                );

                                if (! empty($stokDetail->stock_barang_id)) {
                                    $stok = $this->stokRepo->find($stokDetail->stock_barang_id);
                                    if ($stok) {
                                        $this->stokRepo->update($stok->id, [
                                            'stok' => (float) $stok->stok + $qtyDetail,
                                        ]);
                                        $jenisBarangId = $this->resolveJenisBarangId((int) $detail->barang_id, $stok, $detail->barang ?? null);
                                    }
                                }
                            }
                        }

                        if ($qtyDetail > 0 && $tokoId > 0 && $jenisBarangId) {
                            $stockBulananService->kurangiStokKeluar(
                                tokoId: $tokoId,
                                jenisBarangId: $jenisBarangId,
                                qty: (int) round($qtyDetail),
                                nilaiAset: $hpp * $qtyDetail,
                                tanggal: $tanggalStr
                            );
                        }
                    }

                    // Jika retur sudah sempat diverifikasi ('selesai') dan ada barang pengganti masuk (qty_barang > 0),
                    // rollback penambahan stok bulanan (IN) yang dilakukan saat verify()
                    $qtyBarang = (float) ($detail->qty_barang ?? 0);
                    if ($retur->status === 'selesai' && $qtyBarang > 0 && $tokoId > 0 && $jenisBarangId) {
                        $verifyDateStr = $this->normalizeTanggal($retur->verify_date ?? $tanggalStr);
                        $stockBulananService->kurangiStokMasuk(
                            tokoId: $tokoId,
                            jenisBarangId: $jenisBarangId,
                            qty: (int) round($qtyBarang),
                            nilaiAset: $hpp * $qtyBarang,
                            tanggal: $verifyDateStr
                        );
                    }
                }
            }

            return $this->repository->delete($id, $data);
        });
    }

    private function resolveJenisBarangId(int $barangId, $stok = null, $barang = null): ?int
    {
        $jenisId = $barang?->jenis_barang_id
            ?? $stok?->barang?->jenis_barang_id
            ?? Barang::find($barangId)?->jenis_barang_id;

        return $jenisId ? (int) $jenisId : null;
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
