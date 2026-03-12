<?php

namespace App\Services\Distribusi;

use App\Helpers\AssetGenerate;
use App\Helpers\KasJenisBarangGenerate;
use App\Helpers\RupiahGenerate;
use App\Helpers\TextGenerate;
use App\Models\PengirimanBarang;
use App\Models\PengirimanBarangDetail;
use App\Models\PengirimanBarangDetailTemp;
use App\Models\StockBarangBatch;
use App\Repositories\Distribusi\{PengirimanBarangDetailRepo, PengirimanBarangDetailTempRepo, PengirimanBarangRepo};
use App\Traits\PaginateResponse;
use Illuminate\Support\Facades\DB;

class PengirimanBarangService
{
    use PaginateResponse;
    protected $repository;
    protected $repo2;
    protected $repo3;

    public function __construct(PengirimanBarangRepo $repository, PengirimanBarangDetailRepo $repo2, PengirimanBarangDetailTempRepo $repo3)
    {
        $this->repository = $repository;
        $this->repo2 = $repo2;
        $this->repo3 = $repo3;
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
            ],
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function getDetail($filter)
    {
        $query = $this->repo2->getAll($filter);

        $data = collect(method_exists($query, 'items') ? $query->items() : $query)->map(function ($item) {
            $img = AssetGenerate::build("qrcodes/pembelian/{$item->batch->qrcode}.png");
            $nama = TextGenerate::smartTail($item->barang->nama);
            $stok = $item->batch->qty_sisa ?? 0;
            $tanggal = $item->batch->created_at->format('d-m-Y H:i:s');

            return [
                'id' => $item->id,
                'barang' => $item->barang->nama,
                'qty_send' => $item->qty_send ?? 0,
                'qty_verified' => $item->qty_verified ?? 0,
                'harga_beli' => RupiahGenerate::build($item->batch->harga_beli),
                'suplier' => optional($item->batch->supplier)->nama ?? 'Tidak Ada',
                'created_at' => $item->created_at ?? null,
                'text' => "
                    <div style='display: flex; align-items: center; gap: 8px;' class='p-1'>
                        <img src='{$img}' width='28' height='28' style='border-radius: 3px;'>

                        <div style='display: flex; flex-direction: column; line-height: 1.2;'>
                            <span style='font-weight: 550; font-size: 12px;'>{$nama}</span>
                            <small class='text-dark'>
                                Tanggal pembelian: {$tanggal}
                            </small>
                        </div>
                    </div>
                "
            ];
        });

        return [
            'data' => [
                'item' => $data,
                'total' => $this->repo2->sumHargaBeli($filter)
            ],
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function getTemporary($filter)
    {
        $query = $this->repo3->getAll($filter);

        $data = collect(method_exists($query, 'items') ? $query->items() : $query)->map(function ($item) {
            $img = AssetGenerate::build("qrcodes/pembelian/{$item->batch->qrcode}.png");
            $nama = TextGenerate::smartTail($item->barang->nama);
            $stok = $item->batch->qty_sisa ?? 0;
            $tanggal = $item->created_at ? $item->created_at->format('d-m-Y H:i:s') : '-';

            return [
                'id' => $item->id,
                'barang_id' => $item->barang->id,
                'barang' => $item->barang->nama,
                'qty_send' => $item->qty_send ?? 0,
                'qty_verified' => $item->qty_verified ?? 0,
                'harga_beli' => RupiahGenerate::build($item->batch->harga_beli),
                'suplier' => optional($item->batch->supplier)->nama ?? 'Tidak Ada',
                'created_at' => $item->created_at ?? null,
                'stock_barang_batch_id' => $item->stock_barang_batch_id ?? null,
                'text' => "
                    <div style='display: flex; align-items: center; gap: 8px;' class='p-1'>
                        <img src='{$img}' width='28' height='28' style='border-radius: 3px;'>

                        <div style='display: flex; flex-direction: column; line-height: 1.2;'>
                            <span style='font-weight: 550; font-size: 12px;'>{$nama}</span>
                            <small class='text-dark'>
                                Stok: {$stok} — Tanggal masuk: {$tanggal}
                            </small>
                        </div>
                    </div>
                "
            ];
        });

        return [
            'data' => [
                'item' => $data,
                'total' => $this->repo3->sumHargaBeli($filter)
            ],
            'pagination' => $this->setPaginate($query)
        ];
    }

    public function deleteTemporary($id): bool
    {
        return DB::transaction(function () use ($id) {
            $this->repo3->delete($id);

            return true;
        });
    }

    public function delete(int $id): bool
    {
        DB::beginTransaction();

        try {

            $pengiriman = PengirimanBarang::lockForUpdate()->find($id);

            if (!$pengiriman) {
                return false;
            }

            // ❌ Tidak boleh hapus jika sudah success
            if ($pengiriman->status === 'success') {
                throw new \Exception("Data dengan status success tidak dapat dihapus.");
            }

            // ==================================================
            // 🔥 HANYA KEMBALIKAN STOK JIKA STATUS = PROGRESS
            // ==================================================
            if ($pengiriman->status === 'progress') {

                $details = PengirimanBarangDetail::where(
                    'pengiriman_barang_id',
                    $pengiriman->id
                )->lockForUpdate()->get();

                foreach ($details as $detail) {

                    $batch = StockBarangBatch::lockForUpdate()
                        ->where('id', $detail->stock_barang_batch_id)
                        ->where('toko_id', $pengiriman->toko_asal_id)
                        ->first();

                    if ($batch) {

                        // 🔁 Kembalikan qty batch
                        $batch->qty_sisa += $detail->qty_send;
                        $batch->save();

                        // 🔁 Kembalikan stok toko
                        $stock = $batch->stockBarang;

                        if ($stock) {
                            $stock->stok += $detail->qty_send;
                            $stock->save();
                        }
                    }
                }
            }

            // ==================================================
            // 🗑 HAPUS DETAIL FINAL
            // ==================================================
            PengirimanBarangDetail::where(
                'pengiriman_barang_id',
                $pengiriman->id
            )->delete();

            // 🗑 HAPUS DETAIL TEMP
            PengirimanBarangDetailTemp::where(
                'pengiriman_barang_id',
                $pengiriman->id
            )->delete();

            // 🗑 HAPUS HEADER
            $pengiriman->delete();

            DB::commit();

            return true;
        } catch (\Exception $e) {

            DB::rollBack();
            throw $e;
        }
    }

    public function getLaporan($filter)
    {
        $query = $this->repository->getLaporan($filter);

        $data = collect(method_exists($query, 'items') ? $query->items() : $query)->map(function ($item) {
            return [
                'total_qty' => $item->total_qty,
                'total_nominal' => RupiahGenerate::build($item->total_nominal),
                'toko_asal' => optional($item->tokoAsal)->nama ?? 'Tidak Ada',
                'toko_tujuan' => optional($item->tokoTujuan)->nama ?? 'Tidak Ada',
            ];
        });

        return [
            'data' => [
                'item' => $data,
            ],
            'pagination' => $this->setPaginate($query)
        ];
    }
}
