<?php

namespace App\Services;

use App\Repositories\KasirDetailRepository;
use App\Repositories\ReturMemberRepository;
use App\Repositories\ReturMemberDetailRepository;
use App\Repositories\ReturMemberDetailStokRepository;
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
    protected $returMemberDetailStokRepository;

    public function __construct(ReturMemberRepository $repository, ReturMemberDetailRepository $detailRepo, KasirDetailRepository $kasirRepo, StokBarangDetailRepository $stokDetailRepo, StokBarangRepository $stokRepo, ReturMemberDetailStokRepository $returMemberDetailStokRepository)
    {
        $this->repository = $repository;
        $this->detailRepo = $detailRepo;
        $this->kasirRepo = $kasirRepo;
        $this->stokRepo = $stokRepo;
        $this->stokDetailRepo = $stokDetailRepo;
        $this->returMemberDetailStokRepository = $returMemberDetailStokRepository;
    }

    public function getTotalHarga()
    {
        $hpp = $this->detailRepo->sumHppBarang();
        $refund = $this->detailRepo->sumRefund();
        return [
            'hpp' => [
                'total' => $hpp,
                'format' => 'Rp ' . number_format($hpp, 0, ',', '.')
            ],
            'refund' => [
                'total' => $refund,
                'format' => 'Rp ' . number_format($refund, 0, ',', '.')
            ],
        ];
    }

    public function getAll($filter)
    {
        $query = $this->repository->getAll($filter);

        $data = collect($query->items())->map(function ($item) {
            // ambil semua detail untuk retur ini
            $details = $this->detailRepo->getByReturId($item->id);

            $totalDiganti   = 0;
            $totalRefundQty = 0;
            $totalHpp       = 0;
            $totalRefund    = 0;

            foreach ($details as $d) {
                if (!empty($d->qty_barang)) {
                    $totalDiganti += $d->qty_barang;
                }
                if (!empty($d->qty_refund)) {
                    $totalRefundQty += $d->qty_refund;
                }

                // sum nilai uang
                $totalHpp    += (float) $d->total_hpp;
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
                'id'         => $item->id,
                'status'     => $item->status,
                'toko'       => $item->toko->nama_toko ?? null,
                'member'     => $item->member->nama_member ?? 'Guest',
                'tanggal'    => $item->tanggal,
                'created_by' => $item->createdBy->nama ?? 'System',
                'keterangan' => implode(', ', $keterangan),
                'total_hpp_barang' => 'Rp ' . number_format($totalHpp, 0, ',', '.'),
                'total_refund' => 'Rp ' . number_format($totalRefund, 0, ',', '.'),
            ];
        });

        return [
            'data' => [
                'item'  => $data,
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
            'member' => $item->member->nama_member ?? 'Guest',
            'tanggal' => $item->tanggal ?? null,
            'created_by' => $item->createdBy->nama,
            'nama_toko' => $item->createdBy->toko->nama_toko,
        ];

        $query = $this->detailRepo->getAll($filter);
        $data = collect($query->items())->map(function ($detail) {
            return [
                'id' => $detail->public_id,
                'barang' => $detail->barang->nama_barang ?? null,
                'supplier' => $detail->supplier->nama_supplier ?? null,
                'tipe_kompensasi' => $detail->tipe_kompensasi,
                'format_harga_jual' => 'Rp ' . number_format($detail->harga_jual, 0, ',', '.'),
                'format_hpp' => 'Rp ' . number_format($detail->hpp, 0, ',', '.'),
                'format_total_hpp_barang' => 'Rp ' . number_format($detail->total_hpp_barang, 0, ',', '.'),
                'format_jumlah_refund' => 'Rp ' . number_format($detail->jumlah_refund, 0, ',', '.'),
                'format_total_refund' => 'Rp ' . number_format($detail->total_refund, 0, ',', '.'),
                'qty_request' => $detail->qty_request,
                'qty_barang' => $detail->qty_barang,
                'qty_refund' => $detail->qty_refund,
                'qty_ke_supplier' => $detail->qty_ke_supplier ?? 0,
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

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $retur = $this->repository->create([
                'toko_id'    => $data['toko_id'] ?? null,
                'member_id'  => $data['member_id'] ?? null,
                'status'     => $data['status'] ?? 'draft',
                'tanggal'    => $data['tanggal'],
                'created_by' => $data['created_by'],
            ]);

            foreach ($data['items'] as $detail) {
                $detail['retur_id'] = $retur->id;

                $kasirList = $this->kasirRepo->findByDetailId($detail['detail_kasir_id']);

                foreach ($kasirList as $kasir) {
                    $newRetureQty = ($kasir->reture_qty ?? 0) + $detail['qty_request'];

                    $this->kasirRepo->update($kasir->id, [
                        'reture_qty' => $newRetureQty,
                        'reture_by'  => $data['created_by'],
                        'reture'     => true,
                    ]);
                }
                $detail['total_hpp'] = ($detail['qty_request'] ?? 0) * ($detail['hpp'] ?? 0);

                $returDetail = $this->detailRepo->create($detail);

                if (!empty($detail['qty_barang']) && $detail['qty_barang'] > 0) {
                    $stok = $this->stokRepo->findByBarangId($detail['barang_id']);
                    if (!$stok || $stok->stock < $detail['qty_barang']) {
                        throw ValidationException::withMessages([
                            'qty_barang' => "Stok barang tidak mencukupi untuk barang ID {$detail['barang_id']}."
                        ]);
                    }

                    $this->stokRepo->update($stok->id, [
                        'stock' => $stok->stock - $detail['qty_barang']
                    ]);

                    $qtyNeeded   = $detail['qty_barang'];
                    $stokDetails = $this->stokDetailRepo->findAvailableByBarangId($detail['barang_id']);

                    foreach ($stokDetails as $ds) {
                        if ($qtyNeeded <= 0) break;

                        if ($ds->qty_now >= $qtyNeeded) {
                            $this->stokDetailRepo->update($ds->id, [
                                'qty_out' => $ds->qty_out + $qtyNeeded,
                                'qty_now' => $ds->qty_now - $qtyNeeded
                            ]);

                            $this->returMemberDetailStokRepository->create([
                                'retur_member_detail_id' => $returDetail->id,
                                'stok_detail_id'         => $ds->id,
                                'qty'                    => $qtyNeeded,
                            ]);

                            $qtyNeeded = 0;
                        } else {
                            $this->stokDetailRepo->update($ds->id, [
                                'qty_out' => $ds->qty_out + $ds->qty_now,
                                'qty_now' => 0
                            ]);

                            $this->returMemberDetailStokRepository->create([
                                'retur_member_detail_id' => $returDetail->id,
                                'stok_detail_id'         => $ds->id,
                                'qty'                    => $ds->qty_now,
                            ]);

                            $qtyNeeded -= $ds->qty_now;
                        }
                    }

                    if ($qtyNeeded > 0) {
                        throw ValidationException::withMessages([
                            'qty_now' => "Stok detail barang ID {$detail['barang_id']} tidak cukup. Sisa stok global sudah tidak valid."
                        ]);
                    }
                }
            }

            return $retur->load('detail.stokDetails'); // eager load relasi pivot
        });
    }

    public function getQRCode($filter)
    {
        $query = $this->kasirRepo->getQRCode($filter);

        $data = collect($query->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => "{$item->qrcode} => {$item->barang->nama_barang} ~ (Sisa: $item->qty_selisih)",
            ];
        });

        return [
            'data' => $data,
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function getHargaBarang($filter)
    {
        $query = $this->kasirRepo->getHargaBarang($filter);

        $data = collect($query->items())->map(function ($item) use ($filter) {
            $stokDetailId = optional(optional($item->detailPembelian)->detailStock)->id;
            $qtyNowDetail = optional(optional($item->detailPembelian)->detailStock)->qty_now;
            $qtyStok = optional(optional($item->detailPembelian)->detailStock->stok)->stock;

            // Default retur = qty yang diminta user
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
                'id'          => $item->id,
                'qrcode'      => $item->qrcode,
                'supplier_id' => $item->id_supplier,
                'barang'      => $item->barang->nama_barang,
                'barang_id'   => $item->barang->id,
                'qty'         => $item->qty_selisih,
                'qty_detail'  => $qtyNowDetail,
                'qty_now'     => $qtyStok,
                'qty_retur'   => $returQty,
                'kompensasi'  => $kompensasi,
                'harga'       => $item->harga,
                'hpp'         => optional($item->detailPembelian)->harga_barang,
                'stok_detail_id'         => $stokDetailId,
            ];
        });

        return [
            'data'       => $data,
            'pagination' => $this->setPaginate($query)
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

            if (!empty($data['items'])) {
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

    public function delete($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            return $this->repository->delete($id, $data);
        });
    }
}
