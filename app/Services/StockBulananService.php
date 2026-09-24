<?php

namespace App\Services;

use App\Models\StockBarangBulanan;
use Carbon\Carbon;

class StockBulananService
{
    /**
     * Tambah Stok Masuk (IN) -> Pembelian / Barang Masuk
     */
    public function tambahStokMasuk(
        int $tokoId,
        int $jenisBarangId,
        int $qty,
        float $nilaiAset,
        ?string $tanggal = null
    ): void {
        $this->updateStok($tokoId, $jenisBarangId, $qty, $nilaiAset, 'IN', $tanggal);
    }

    /**
     * Kurangi Stok Masuk (ROLLBACK IN) -> Hapus Pembelian / Cancel Barang Masuk
     */
    public function kurangiStokMasuk(
        int $tokoId,
        int $jenisBarangId,
        int $qty,
        float $nilaiAset,
        ?string $tanggal = null
    ): void {
        $this->updateStok($tokoId, $jenisBarangId, $qty, $nilaiAset, 'ROLLBACK_IN', $tanggal);
    }

    /**
     * Tambah Stok Keluar (OUT) -> Penjualan / Pengeluaran Barang Pengganti
     */
    public function tambahStokKeluar(
        int $tokoId,
        int $jenisBarangId,
        int $qty,
        float $nilaiAset,
        ?string $tanggal = null
    ): void {
        $this->updateStok($tokoId, $jenisBarangId, $qty, $nilaiAset, 'OUT', $tanggal);
    }

    /**
     * Kurangi / Rollback Stok Keluar (ROLLBACK OUT) -> Hapus Penjualan / Batal Retur
     */
    public function kurangiStokKeluar(
        int $tokoId,
        int $jenisBarangId,
        int $qty,
        float $nilaiAset,
        ?string $tanggal = null
    ): void {
        $this->updateStok($tokoId, $jenisBarangId, $qty, $nilaiAset, 'ROLLBACK_OUT', $tanggal);
    }

    /**
     * Alias untuk rollbackStokKeluar
     */
    public function rollbackStokKeluar(
        int $tokoId,
        int $jenisBarangId,
        int $qty,
        float $nilaiAset,
        ?string $tanggal = null
    ): void {
        $this->kurangiStokKeluar($tokoId, $jenisBarangId, $qty, $nilaiAset, $tanggal);
    }

    /**
     * Core Function untuk Kalkulasi Stok Bulanan
     */
    private function updateStok(
        int $tokoId,
        int $jenisBarangId,
        int $qty,
        float $nilaiAset,
        string $tipe,
        ?string $tanggal = null
    ): void {
        $date  = $tanggal ? Carbon::parse($tanggal) : Carbon::now();
        $tahun = $date->year;
        $bulan = $date->month;

        // Cari atau buat baris baru
        $stokBulanan = StockBarangBulanan::firstOrCreate(
            [
                'toko_id'         => $tokoId,
                'jenis_barang_id' => $jenisBarangId,
                'tahun'           => $tahun,
                'bulan'           => $bulan,
            ],
            [
                'qty_awal'    => 0,
                'qty_masuk'   => 0,
                'qty_keluar'  => 0,
                'qty_sisa'    => 0,
                'nilai_aset'  => 0,
                'qty_retur'   => 0,
                'nilai_retur' => 0,
            ]
        );

        switch ($tipe) {
            case 'IN':
                // Barang Masuk: Tambah Qty Masuk, Sisa, & Aset
                $stokBulanan->qty_masuk  += $qty;
                $stokBulanan->qty_sisa   += $qty;
                $stokBulanan->nilai_aset += $nilaiAset;
                break;

            case 'ROLLBACK_IN':
                // Batal Barang Masuk: Kurangi Qty Masuk, Sisa, & Aset
                $stokBulanan->qty_masuk  = max(0, $stokBulanan->qty_masuk - $qty);
                $stokBulanan->qty_sisa   = max(0, $stokBulanan->qty_sisa - $qty);
                $stokBulanan->nilai_aset = max(0, $stokBulanan->nilai_aset - $nilaiAset);
                break;

            case 'OUT':
                // Barang Keluar: Tambah Qty Keluar, Kurangi Sisa & Aset
                $stokBulanan->qty_keluar += $qty;
                $stokBulanan->qty_sisa    = max(0, $stokBulanan->qty_sisa - $qty);
                $stokBulanan->nilai_aset  = max(0, $stokBulanan->nilai_aset - $nilaiAset);
                break;

            case 'ROLLBACK_OUT':
                // Batal Barang Keluar: Kurangi Qty Keluar, Menambah Sisa & Aset
                $stokBulanan->qty_keluar  = max(0, $stokBulanan->qty_keluar - $qty);
                $stokBulanan->qty_sisa   += $qty;
                $stokBulanan->nilai_aset += $nilaiAset;
                break;
        }

        $stokBulanan->save();
    }
}
