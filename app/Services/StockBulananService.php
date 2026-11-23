<?php

namespace App\Services;

use App\Models\StockBarangBulanan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockBulananService
{
    public function tambahStokMasuk(
        int $tokoId,
        int $jenisBarangId,
        int $qty,
        float $nilaiAset,
        ?string $tanggal = null
    ): void {
        $this->updateStok($tokoId, $jenisBarangId, $qty, $nilaiAset, 'IN', $tanggal);
    }

    public function kurangiStokMasuk(
        int $tokoId,
        int $jenisBarangId,
        int $qty,
        float $nilaiAset,
        ?string $tanggal = null
    ): void {
        $this->updateStok($tokoId, $jenisBarangId, $qty, $nilaiAset, 'ROLLBACK_IN', $tanggal);
    }

    public function tambahStokKeluar(
        int $tokoId,
        int $jenisBarangId,
        int $qty,
        float $nilaiAset,
        ?string $tanggal = null
    ): void {
        $this->updateStok($tokoId, $jenisBarangId, $qty, $nilaiAset, 'OUT', $tanggal);
    }

    public function kurangiStokKeluar(
        int $tokoId,
        int $jenisBarangId,
        int $qty,
        float $nilaiAset,
        ?string $tanggal = null
    ): void {
        $this->updateStok($tokoId, $jenisBarangId, $qty, $nilaiAset, 'ROLLBACK_OUT', $tanggal);
    }

    public function rollbackStokKeluar(
        int $tokoId,
        int $jenisBarangId,
        int $qty,
        float $nilaiAset,
        ?string $tanggal = null
    ): void {
        $this->kurangiStokKeluar($tokoId, $jenisBarangId, $qty, $nilaiAset, $tanggal);
    }

    public function catatRetur(
        int $tokoId,
        int $jenisBarangId,
        int $qty,
        float $nilaiRetur,
        ?string $tanggal = null
    ): void {
        $this->updateStok($tokoId, $jenisBarangId, $qty, $nilaiRetur, 'RETUR', $tanggal);
    }

    public function rollbackRetur(
        int $tokoId,
        int $jenisBarangId,
        int $qty,
        float $nilaiRetur,
        ?string $tanggal = null
    ): void {
        $this->updateStok($tokoId, $jenisBarangId, $qty, $nilaiRetur, 'ROLLBACK_RETUR', $tanggal);
    }

    private function updateStok(
        int $tokoId,
        int $jenisBarangId,
        int $qty,
        float $nilaiAset,
        string $tipe,
        ?string $tanggal = null
    ): void {
        if ($qty <= 0) {
            return;
        }

        $date = $tanggal ? Carbon::parse($tanggal) : Carbon::now();
        $tahun = (int) $date->year;
        $bulan = (int) $date->month;

        $stokBulanan = StockBarangBulanan::where([
            'toko_id' => $tokoId,
            'jenis_barang_id' => $jenisBarangId,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ])->lockForUpdate()->first();

        if (! $stokBulanan) {
            $realBatch = DB::table('stock_barang_batch as sbb')
                ->join('stock_barang as sb', 'sbb.stock_barang_id', '=', 'sb.id')
                ->join('barang as b', 'sb.barang_id', '=', 'b.id')
                ->where('sbb.toko_id', $tokoId)
                ->where('b.jenis_barang_id', $jenisBarangId)
                ->where('sbb.qty_sisa', '>', 0)
                ->whereNull('sb.deleted_at')
                ->whereNull('b.deleted_at')
                ->select(
                    DB::raw('COALESCE(SUM(sbb.qty_sisa), 0) as total_qty'),
                    DB::raw('COALESCE(SUM(sbb.qty_sisa * sbb.harga_beli), 0) as total_aset')
                )
                ->first();

            $qtyAwal = (int) ($realBatch->total_qty ?? 0);
            $nilaiAwal = (float) ($realBatch->total_aset ?? 0);

            $stokBulanan = StockBarangBulanan::create([
                'toko_id' => $tokoId,
                'jenis_barang_id' => $jenisBarangId,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'qty_awal' => $qtyAwal,
                'qty_masuk' => 0,
                'qty_keluar' => 0,
                'qty_sisa' => $qtyAwal,
                'nilai_aset' => $nilaiAwal,
                'qty_retur' => 0,
                'nilai_retur' => 0,
            ]);
        }

        switch ($tipe) {
            case 'IN':
                $stokBulanan->qty_masuk += $qty;
                $stokBulanan->qty_sisa += $qty;
                $stokBulanan->nilai_aset += $nilaiAset;
                break;

            case 'ROLLBACK_IN':
                $stokBulanan->qty_masuk = max(0, $stokBulanan->qty_masuk - $qty);
                $stokBulanan->qty_sisa = max(0, $stokBulanan->qty_sisa - $qty);
                $stokBulanan->nilai_aset = max(0, $stokBulanan->nilai_aset - $nilaiAset);
                break;

            case 'OUT':
                $stokBulanan->qty_keluar += $qty;
                $stokBulanan->qty_sisa = max(0, $stokBulanan->qty_sisa - $qty);
                $stokBulanan->nilai_aset = max(0, $stokBulanan->nilai_aset - $nilaiAset);
                break;

            case 'ROLLBACK_OUT':
                $stokBulanan->qty_keluar = max(0, $stokBulanan->qty_keluar - $qty);
                $stokBulanan->qty_sisa += $qty;
                $stokBulanan->nilai_aset += $nilaiAset;
                break;

            case 'RETUR':
                $stokBulanan->qty_retur += $qty;
                $stokBulanan->nilai_retur += $nilaiAset;
                break;

            case 'ROLLBACK_RETUR':
                $stokBulanan->qty_retur = max(0, $stokBulanan->qty_retur - $qty);
                $stokBulanan->nilai_retur = max(0, $stokBulanan->nilai_retur - $nilaiAset);
                break;
        }

        $stokBulanan->save();
    }
}
