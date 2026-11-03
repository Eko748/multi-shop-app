<?php

namespace App\Services;

use App\Models\StockBarangBulanan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

        // Cek apakah data bulan ini sudah ada
        $stokBulanan = StockBarangBulanan::where([
            'toko_id' => $tokoId,
            'jenis_barang_id' => $jenisBarangId,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ])->first();

        // Jika record bulan ini belum ada, buat baru dan SELALU Recalculate dari Batch Aktif
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
                    DB::raw('SUM(sbb.qty_sisa) as total_qty'),
                    DB::raw('SUM(sbb.qty_sisa * sbb.harga_beli) as total_aset')
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

        // Update Angka Transaksi Berjalan
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
