<?php

namespace App\Services;

use App\Repositories\PembelianBarangDetailRepository;
use App\Repositories\ReturSupplierRepository;
use App\Repositories\ReturSupplierDetailRepository;
use App\Repositories\ReturSupplierSummaryRepository;
use App\Repositories\ReturMemberDetailRepository;
use App\Repositories\KasirDetailRepository;
use App\Repositories\StockBarangBatchRepo;
use App\Repositories\StokBarangDetailRepository;
use App\Repositories\StokBarangRepository;
use App\Traits\PaginateResponse;
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
    protected $stokRepo;
    protected $stokDetailRepo;
    protected $detailKasirRepo;

    public function __construct(ReturSupplierRepository $repository, ReturSupplierDetailRepository $detailRepo, ReturSupplierSummaryRepository $returSupplierSummaryRepo, ReturMemberDetailRepository $returMemberDetailRepo, StockBarangBatchRepo $stockBarangBatchRepo, StokBarangDetailRepository $stokDetailRepo, StokBarangRepository $stokRepo, KasirDetailRepository $detailKasirRepo,)
    {
        $this->repository = $repository;
        $this->detailRepo = $detailRepo;
        $this->returSupplierSummaryRepo = $returSupplierSummaryRepo;
        $this->returMemberDetailRepo = $returMemberDetailRepo;
        $this->stockBarangBatchRepo = $stockBarangBatchRepo;
        $this->stokRepo = $stokRepo;
        $this->stokDetailRepo = $stokDetailRepo;
        $this->detailKasirRepo = $detailKasirRepo;
    }

    public function getTotalHarga()
    {
        $data = $this->repository->sumHppDanRefund();

        $totalBarang = 0;
        $totalRefund = 0;

        foreach ($data as $item) {
            if (!empty($item->detail)) {
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
            if (!empty($item->detail)) {
                foreach ($item->detail as $detail) {
                    $qtyBarang = $detail->qty_barang ?? 0;
                    $qtyRefund = $detail->qty_refund ?? 0;
                    $totalQtyDetail += ($qtyBarang + $qtyRefund);
                    $refund += $detail->total_refund_real;
                    $barang += $detail->total_hpp_barang;
                }
            }

            // Default verified = null
            $verified = null;

            // Jika status masih proses baru lakukan pengecekan
            if ($item->status === 'proses') {
                $verified = ($totalQtyDetail === (int) $item->qty);
            }

            return [
                'id' => $item->id,
                'no_retur' => "R-{$item->id}",
                'supplier' => $item->supplier->nama_supplier ?? '-',
                'tipe_retur' => $item->tipe_retur === 'member' ? 'Retur Member' : 'Pembelian Barang',
                'tanggal' => $item->tanggal,
                'qty' => $item->qty,
                'total_refund' => 'Rp ' . number_format($refund, 0, ',', '.'),
                'total_hpp' => 'Rp ' . number_format($barang, 0, ',', '.'),
                'total_selisih' => 'Rp ' . number_format($item->total_selisih, 0, ',', '.'),
                'status' => $item->status,
                'keterangan' => $item->keterangan,
                'created_by' => $item->createdBy->nama ?? 'System',
                'verified' => $verified,
            ];
        });

        return [
            'data' => [
                'item' => $data,
                'total' => $this->getTotalHarga()
            ],
            'pagination' => !empty($filter->limit) ? $this->setPaginate($query) : null,
        ];
    }

    public function getDetail($filter)
    {
        $item = $this->repository->getDetailById($filter->id);
        $itemFormatted = [
            'id' => $item->id,
            'tipe_retur' => $item->tipe_retur,
            'supplier' => $item->supplier->nama_supplier ?? 'Guest',
            'tanggal' => $item->tanggal ?? null,
            'total_refund' => 'Rp ' . number_format($item->total_refund, 0, ',', '.'),
            'total_hpp' => 'Rp ' . number_format($item->total_hpp, 0, ',', '.'),
            'total_selisih' => 'Rp ' . number_format($item->total_selisih, 0, ',', '.'),
            'status' => $item->status,
            'created_by' => $item->createdBy->nama,
            'nama_toko' => $item->createdBy->toko->nama_toko,
        ];

        $query = $this->detailRepo->getAll($filter);
        $data = collect($query->items())->map(function ($detail) {
            return [
                'id' => $detail->public_id,
                'barang' => $detail->barang->nama_barang ?? null,
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
                'detail' => $data
            ],
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function getData($filter)
    {
        if (empty($filter->id)) {
            throw ValidationException::withMessages([
                'id' => 'Parameter ID wajib diisi.'
            ]);
        }

        return [
            'data' => $this->repository->find($filter->id)
        ];
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $created = [];

            foreach ($data['retur'] as $returSupplier) {
                // 🔹 Hitung total qty dari semua detail barang supplier ini
                $totalQty = array_sum(array_column($returSupplier['detail'], 'qty'));

                // ============ SIMPAN DATA UTAMA RETUR ============
                $retur = $this->repository->create([
                    'toko_id'     => $data['toko_id'],
                    'supplier_id' => $returSupplier['supplier_id'],
                    'tipe_retur'  => $data['tipe_retur'],
                    'tanggal'     => $data['tanggal'],
                    'qty'         => $totalQty, // ✅ ambil dari total qty detail
                    'total_hpp'   => $data['total_hpp'],
                    'status'      => 'proses',
                    'created_by'  => $data['created_by'],
                ]);

                // ============ SIMPAN DETAIL RETUR ============
                foreach ($returSupplier['detail'] as $detail) {
                    $payload = [
                        'retur_supplier_id' => $retur->id,
                        'barang_id'         => $detail['barang_id'],
                        'qty'               => $detail['qty'],
                        'hpp'               => $detail['hpp'],
                        'harga_jual'        => $detail['harga_jual'],
                        'total_hpp'         => $detail['hpp'] * $detail['qty'],
                    ];

                    if ($data['tipe_retur'] === 'pembelian') {
                        $payload['detail_pembelian_barang_id'] = $detail['id'];
                    } elseif ($data['tipe_retur'] === 'member') {
                        $payload['retur_member_detail_id'] = $detail['id'];
                    }

                    $this->detailRepo->create($payload);

                    // ============ UPDATE STOK DETAIL & UTAMA ============
                    $qtyBarang = (float) $detail['qty'];

                    // CASE 1: RETUR PEMBELIAN → stok berkurang
                    if ($data['tipe_retur'] === 'pembelian' && !empty($detail['id'])) {
                        $stokDetail = $this->stokDetailRepo->findByPembelianWithZero($detail['id']);
                        if ($stokDetail) {
                            $updateData = [
                                'qty_now' => max($stokDetail->qty_now - $qtyBarang, 0),
                                'qty_out' => $stokDetail->qty_out + $qtyBarang,
                            ];
                            $this->stokDetailRepo->updateWithPembelian($detail['id'], $updateData);

                            // Kurangi stok utama juga
                            $stok = $this->stokRepo->find($stokDetail->id_stock);
                            if ($stok) {
                                $this->stokRepo->update($stok->id, [
                                    'stock' => max($stok->stock - $qtyBarang, 0),
                                ]);
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
            // 🔹 Ambil retur utama
            $returUtama = $this->repository->find($data['id']);
            if (!$returUtama) {
                throw ValidationException::withMessages([
                    'retur_id' => "Data retur dengan ID {$data['id']} tidak ditemukan."
                ]);
            }

            // =========================
            // Hitung keterangan retur utama
            // =========================
            $total_refund = (float) $data['subtotal_refund'];
            $total_hpp = (float) $data['subtotal_hpp'];
            $keteranganUtama = 'seimbang';

            if ($total_refund < $total_hpp) {
                $keteranganUtama = 'rugi';
            } elseif ($total_refund > $total_hpp) {
                $keteranganUtama = 'untung';
            }

            // 🔹 Update tabel retur utama
            $this->repository->update($data['id'], [
                'toko_id'         => $data['toko_id'],
                'qty'             => $data['qty'],
                'total_refund'    => $total_refund,
                'total_hpp'       => $total_hpp,
                'total_selisih'   => $data['subtotal_selisih'],
                'keterangan'      => $keteranganUtama,
                'updated_by'      => $data['updated_by'],
            ]);

            // 🔹 Loop setiap supplier (grup retur)
            foreach ($data['retur'] as $returGroup) {
                foreach ($returGroup['detail'] as $detail) {
                    $existingDetail = $this->detailRepo->find($detail['id']);
                    if (!$existingDetail) {
                        throw ValidationException::withMessages([
                            'detail_id' => "Detail retur dengan ID {$detail['id']} tidak ditemukan."
                        ]);
                    }

                    // =========================
                    // Hitung keterangan per detail
                    // =========================
                    $totalRefundDetail = (float) $detail['total_refund'];
                    $totalHppDetail = (float) $detail['total_hpp'];
                    $keteranganDetail = 'seimbang';

                    if ($totalRefundDetail < $totalHppDetail) {
                        $keteranganDetail = 'rugi';
                    } elseif ($totalRefundDetail > $totalHppDetail) {
                        $keteranganDetail = 'untung';
                    }

                    // 🔹 Update detail item retur
                    $this->detailRepo->update($detail['id'], [
                        'retur_supplier_id' => $detail['retur_id'],
                        'barang_id'         => $detail['barang_id'],
                        'tipe_kompensasi'   => $detail['kompensasi'],
                        'hpp'               => $detail['hpp'],
                        'harga_jual'        => $detail['harga_jual'],
                        'qty_refund'        => $detail['qty_refund'],
                        'qty_barang'        => $detail['qty_barang'],
                        'jumlah_refund'     => $detail['jumlah_refund'],
                        'total_refund_real' => $detail['qty_refund'] * $detail['jumlah_refund'],
                        'total_refund'      => $totalRefundDetail,
                        'total_hpp'         => $totalHppDetail,
                        'total_hpp_barang'  => $detail['qty_barang'] * $detail['hpp'],
                        'selisih'           => $detail['selisih'],
                        'keterangan'        => $keteranganDetail,
                        // 'updated_by'        => $data['updated_by'],
                    ]);
                }
            }

            return true;
        });
    }

    public function verify(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Ambil retur utama
            $returUtama = $this->repository->find($data['id']);
            if (!$returUtama) {
                throw ValidationException::withMessages([
                    'retur_id' => "Data retur dengan ID {$data['id']} tidak ditemukan."
                ]);
            }

            // Update status retur ke selesai
            $this->repository->update($data['id'], [
                'status'      => 'selesai',
                'updated_by'  => $data['updated_by'],
                'verify_date' => now(),
            ]);

            // Ambil semua detail retur
            $detailRetur = $this->detailRepo->getByRetur($data['id']);

            foreach ($detailRetur as $detail) {

                // ===============================
                // CASE 1: RETUR PEMBELIAN
                // ===============================
                if (
                    $returUtama->tipe_retur === 'pembelian' &&
                    $detail->detail_pembelian_barang_id &&
                    $detail->qty_barang > 0
                ) {
                    $qtyBarang = (float) $detail->qty_barang;

                    // Ambil stok detail terkait
                    $stokDetail = $this->stokDetailRepo->findByPembelianWithZero($detail->detail_pembelian_barang_id);

                    if ($stokDetail) {
                        $updateData = [
                            'qty_now' => $stokDetail->qty_now + $qtyBarang,
                            'qty_out' => max($stokDetail->qty_out - $qtyBarang, 0),
                        ];

                        $this->stokDetailRepo->updateWithPembelian($detail->detail_pembelian_barang_id, $updateData);

                        // Update stok utama
                        if (!empty($stokDetail->id_stock)) {
                            $stok = $this->stokRepo->find($stokDetail->id_stock);
                            if ($stok) {
                                $this->stokRepo->update($stok->id, [
                                    'stock' => $stok->stock + $qtyBarang,
                                ]);
                            }
                        }
                    } else {
                        info('stokDetail tidak ditemukan:', [
                            'detail_pembelian_barang_id' => $detail->detail_pembelian_barang_id
                        ]);
                    }
                }

                // ===============================
                // CASE 2: RETUR MEMBER
                // ===============================
                if (
                    $returUtama->tipe_retur === 'member' &&
                    $detail->retur_member_detail_id
                ) {
                    $memberDetail = $this->returMemberDetailRepo->find($detail->retur_member_detail_id);

                    if ($memberDetail) {
                        // Update qty_ke_supplier = qty_request
                        $this->returMemberDetailRepo->update($detail->retur_member_detail_id, [
                            'qty_ke_supplier' => $memberDetail->qty_request,
                        ]);

                        // Ambil id_detail_pembelian dari tabel detail_kasir
                        if (!empty($memberDetail->detail_kasir_id)) {
                            $detailKasir = $this->detailKasirRepo->findById($memberDetail->detail_kasir_id);

                            if ($detailKasir && !empty($detailKasir->id_detail_pembelian)) {
                                $qtyBarang = (float) $detail->qty_barang;

                                $stokDetail = $this->stokDetailRepo->findByPembelianWithZero($detailKasir->id_detail_pembelian);

                                if ($stokDetail) {
                                    $updateData = [
                                        'qty_now' => $stokDetail->qty_now + $qtyBarang,
                                        'qty_out' => max($stokDetail->qty_out - $qtyBarang, 0),
                                    ];

                                    $this->stokDetailRepo->updateWithPembelian($detailKasir->id_detail_pembelian, $updateData);

                                    // Update stok utama
                                    $stok = $this->stokRepo->find($stokDetail->id_stock);
                                    if ($stok) {
                                        $this->stokRepo->update($stok->id, [
                                            'stock' => $stok->stock + $qtyBarang,
                                        ]);
                                    }
                                }
                            }
                        }
                    }
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
                'detail_pembelian_barang_id' => $item->detailKasir->detailPembelian->id,
                'hpp' => $item->hpp,
                'harga' => $item->harga,
                'qty_request' => $item->qty_request
            ];
        });

        return [
            'data' => [
                'item' => $data,
                'total' => $this->getTotalHarga()
            ],
            'pagination' => !empty($filter->limit) ? $this->setPaginate($query) : null,
        ];
    }

    public function getSupplier($filter)
    {
        $query = $this->returMemberDetailRepo->getDistinctSuppliers($filter);

        $data = collect(method_exists($query, 'items') ? $query->items() : $query)->map(function ($item) {
            return [
                'id'   => $item->id,
                'text' => $item->telepon
                    ? "{$item->nama}/{$item->telepon} ~ ({$item->total_item_retur} Item)"
                    : "{$item->nama} ~ ({$item->total_item_retur} Item)",
            ];
        });

        return [
            'data'       => $data,
            'pagination' => !empty($filter->limit) ? $this->setPaginate($query) : null,
        ];
    }

    public function getQRCode($filter)
    {
        $query = $this->stockBarangBatchRepo->getQRCode($filter);

        $data = collect($query->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => "{$item->qrcode} => {$item->stockBarang->barang->nama} ~ (Sisa: {$item->qty_sisa})",
            ];
        });

        return [
            'data' => $data,
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function getHargaBarang($filter)
    {
        if ($filter->tipe == 'pembelian') {
            $query = $this->stockBarangBatchRepo->getHargaBarang($filter);
        } elseif ($filter->tipe == 'member') {
            $query = $this->returMemberDetailRepo->getHargaBarang($filter);
        }

        $data = collect($query->items())->map(function ($item) use ($filter) {
            if ($filter->tipe == 'pembelian') {
                $stokDetailId = optional($item->detailStock)->id;
                $qtyNowDetail = optional($item->detailStock)->qty_now;
                $tgl = "Tgl Pembelian:<br>{$item->created_at}";
                $supplierId = $item->id_supplier;
                $qrcode = $item->qrcode;
            } elseif ($filter->tipe == 'member') {
                $qtyNowDetail = $item->qty;
                $supplierId = $item->supplier_id;
                $tgl = "Tgl Retur:<br>{$item->created_at}";
                $qrcode = $item->detailKasir->detailPembelian->qrcode;
            }

            return [
                'id'          => $item->id,
                'tgl'         => $tgl,
                'qrcode'      => $qrcode,
                'supplier_id' => $supplierId,
                'nama_supplier' => $item->supplier->nama_supplier,
                'barang'      => $item->barang->nama_barang,
                'barang_id'   => $item->barang->id,
                'qty_now'     => $qtyNowDetail,
                'hpp'         => $item->hpp,
                'harga_jual'     => $item->harga_jual ?? 0,
                'stok_detail_id' => $stokDetailId ?? null,
            ];
        });

        return [
            'data'       => $data,
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function delete($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            return $this->repository->delete($id, $data);
        });
    }
}
