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

    private function updateStok(
        int $tokoId,
        int $jenisBarangId,
        int $qty,
        float $nilaiAset,
        string $tipe,
        ?string $tanggal = null
    ): void {
        $date = $tanggal ? Carbon::parse($tanggal) : Carbon::now();
        $tahun = $date->year;
        $bulan = $date->month;

        // Cek apakah data bulan ini sudah ada di DB
        $stokBulanan = StockBarangBulanan::where([
            'toko_id' => $tokoId,
            'jenis_barang_id' => $jenisBarangId,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ])->first();

        // 🟢 JIKA BELUM ADA (TRANSAKSI PERTAMA BULAN INI)
        if (! $stokBulanan) {
            // Cari data bulan sebelumnya
            $prevDate = $date->copy()->subMonth();

            $stokLalu = StockBarangBulanan::where([
                'toko_id' => $tokoId,
                'jenis_barang_id' => $jenisBarangId,
                'tahun' => $prevDate->year,
                'bulan' => $prevDate->month,
            ])->first();

            // Ambil sisa stok & nilai aset bulan lalu (jika tidak ada, set 0)
            $qtyAwal = $stokLalu ? $stokLalu->qty_sisa : 0;
            $nilaiAwal = $stokLalu ? $stokLalu->nilai_aset : 0;

            $stokBulanan = StockBarangBulanan::create([
                'toko_id' => $tokoId,
                'jenis_barang_id' => $jenisBarangId,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'qty_awal' => $qtyAwal,     // 🟢 Saldo awal dari bulan lalu
                'qty_masuk' => 0,
                'qty_keluar' => 0,
                'qty_sisa' => $qtyAwal,     // 🟢 Sisa awal dari bulan lalu
                'nilai_aset' => $nilaiAwal,   // 🟢 Nilai aset awal dari bulan lalu
                'qty_retur' => 0,
                'nilai_retur' => 0,
            ]);
        }

        // Eksekusi kalkulasi transaksi sesuai tipe
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
        }

        $stokBulanan->save();
    }
}
