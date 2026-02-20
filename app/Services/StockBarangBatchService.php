<?php

namespace App\Services;

use App\Helpers\AssetGenerate;
use App\Helpers\RupiahGenerate;
use App\Helpers\TextGenerate;
use App\Models\Member;
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

            $img  = AssetGenerate::build("qrcodes/pembelian/{$item->qrcode}.png");
            $nama = TextGenerate::short($item->stockBarang->barang->nama);
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

        $img = AssetGenerate::build("qrcodes/pembelian/{$item->qrcode}.png");
        $nama = TextGenerate::short($item->stockBarang->barang->nama);
        $stok = $item->qty_sisa ?? 0;
        $tanggal = $item->created_at ? $item->created_at->format('d-m-Y H:i:s') : '-';

        return [
            'data' => [
                'id' => $item->id,
                'qty_sisa' => $item->qty_sisa,
                'hpp_baru' => $item->hpp_baru,
                'format_hpp_baru' => RupiahGenerate::build($item->harga_beli),
                'barang_id' => $item->stockBarang->barang_id ?? null,
                'barang' => $item->stockBarang->barang->nama ?? null,
                'harga_beli' => $item->harga_beli ?? 0,
                'format_harga_beli' => RupiahGenerate::build($item->harga_beli),
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

    public function getHargaJual($filter)
    {
        $item = $this->repository->getByQR($filter);
        if (!$item) {
            return ['data' => null];
        }

        // =========================
        // LEVEL HARGA (STRING JSON)
        // contoh: "[35000,40000,45000,50000]"
        // =========================
        $rawHarga = $item->stockBarang->level_harga ?? null;

        $hargaList = [];

        if (is_string($rawHarga)) {
            $decoded = json_decode($rawHarga, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $hargaList = $decoded;
            }
        } elseif (is_array($rawHarga)) {
            // fallback kalau suatu saat sudah cast array
            $hargaList = $rawHarga;
        }

        $hargaList = collect($hargaList)
            ->filter(fn($h) => is_numeric($h) && $h > 0)
            ->map(fn($h) => (int) $h)
            ->values()
            ->toArray();

        if (empty($hargaList)) {
            return ['data' => null];
        }

        $priceOptions = [];
        $finalPrice   = null;

        // =========================
        // GUEST
        // =========================
        if ($filter->member_id === 'guest' || empty($filter->member_id)) {

            $priceOptions = $hargaList;
        } else {

            // =========================
            // MEMBER
            // =========================
            $member = Member::find($filter->member_id);
            if (!$member) {
                return ['data' => null];
            }

            $jenisBarangId = (int) ($item->stockBarang->barang->jenis_barang_id ?? 0);
            $levelMap      = $member->level_map ?? [];

            if (isset($levelMap[$jenisBarangId])) {

                $level = (int) $levelMap[$jenisBarangId];
                $index = $level - 1;

                if (isset($hargaList[$index])) {
                    $priceOptions[] = $hargaList[$index];
                }
            }

            if (empty($priceOptions)) {
                return [
                    'data'    => null,
                    'message' => 'Harga member tidak tersedia untuk jenis barang ini'
                ];
            }
        }

        // =========================
        // FINAL PRICE
        // =========================
        $finalPrice = collect($priceOptions)->min();

        // =========================
        // FORMAT OPTION
        // =========================
        $priceOptionsFormatted = collect($priceOptions)
            ->unique()
            ->sortDesc()
            ->map(fn($price) => [
                'id'   => $price,
                'text' => RupiahGenerate::build($price),
            ])
            ->values()
            ->toArray();

        // =========================
        // INFO BARANG
        // =========================
        $img     = AssetGenerate::build("qrcodes/pembelian/{$item->qrcode}.png");
        $nama    = TextGenerate::short($item->stockBarang->barang->nama);
        $stok    = $item->qty_sisa ?? 0;
        $tanggal = $item->created_at?->format('d-m-Y H:i:s') ?? '-';

        return [
            'data' => [
                'id'            => $item->id,
                'barang_id'     => $item->stockBarang->barang_id ?? null,
                'barang'        => $nama,
                'qrcode'        => $item->qrcode,
                'qty'           => $stok,
                'harga'         => $finalPrice,
                'format_harga'  => RupiahGenerate::build($finalPrice),
                'is_member_price' => $priceOptionsFormatted,
                'text' => "
            <div style='display:flex;align-items:center;gap:8px' class='p-1'>
                <img src='{$img}' width='28' height='28' style='border-radius:3px'>
                <div style='display:flex;flex-direction:column;gap:4px;line-height:1.2'>
                    <span style='font-weight:550;font-size:12px'>{$nama}</span>
                    <small class='text-dark'>
                        Tanggal masuk: {$tanggal} —
                        <span class='font-weight-bold'>Stok: {$stok}</span>
                    </small>
                </div>
            </div>
            "
            ]
        ];
    }
}
