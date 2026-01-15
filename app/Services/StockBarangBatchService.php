<?php

namespace App\Services;

use App\Repositories\StockBarangBatchRepository;
use App\Traits\PaginateResponse;

class StockBarangBatchService
{
    use PaginateResponse;
    protected $repository;

    public function __construct(StockBarangBatchRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll($filter)
    {
        $query = $this->repository->getAll($filter);

        $data = collect(method_exists($query, 'items') ? $query->items() : $query)->map(function ($item) {

            $img = asset("storage/qrcodes/pembelian/{$item->qrcode}.png");
            $nama = $item->stockBarang->barang->nama;
            $stok = $item->qty_sisa ?? 0;
            $tanggal = $item->created_at ? $item->created_at->format('d-m-Y H:i:s') : '-';

            return [
                'id' => $item->qrcode,
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
            'data' => $data,
            'pagination' => !empty($filter->limit) ? $this->setPaginate($query) : null,
        ];
    }

    public function getByQR($filter)
    {
        $item = $this->repository->getByQR($filter);

        if (!$item) {
            return [
                'data' => null
            ];
        }

        $img = asset("storage/qrcodes/pembelian/{$item->qrcode}.png");
        $nama = $item->stockBarang->barang->nama;
        $stok = $item->qty_sisa ?? 0;
        $tanggal = $item->created_at ? $item->created_at->format('d-m-Y') : '-';

        return [
            'data' => [
                'id' => $item->id,
                'qty_sisa' => $item->qty_sisa,
                'hpp_baru' => $item->hpp_baru,
                'format_hpp_baru' => 'Rp ' . number_format($item->hpp_baru, 0, ',', '.'),
                'barang_id' => $item->stockBarang->barang_id ?? null,
                'barang' => $item->stockBarang->barang->nama ?? null,
                'harga_beli' => $item->harga_beli ?? 0,
                'format_harga_beli' => 'Rp ' . number_format($item->harga_beli, 0, ',', '.'),
                'qrcode' => $item->qrcode ?? null,
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
            ]
        ];
    }
}
