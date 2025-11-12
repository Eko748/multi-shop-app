<?php

namespace App\Services;

use App\Repositories\KasNeracaRepository;
use App\Repositories\KasRepository;
use Illuminate\Support\Facades\DB;

class KasService
{
    protected $kasRepository;
    protected $dompetDigital;
    protected $kasNeracaRepository;

    public function __construct(KasRepository $kasRepository, KasNeracaRepository $kasNeracaRepository)
    {
        $this->kasRepository = $kasRepository;
        $this->kasNeracaRepository = $kasNeracaRepository;
    }

    public function isSingleToko(): bool
    {
        return $this->kasRepository->getFirstTwoToko()->count() <= 1;
    }

    public function getKasBesar()
    {
        $isSingle = $this->isSingleToko();

        $fixOmset = 0;

        if ($isSingle) {
            $pemasukan = $this->kasRepository->getTotalPemasukan();
            $pengeluaran = $this->kasRepository->getTotalPengeluaran();
            $hutang = $this->kasRepository->getTotalHutang();
            $pelunasanHutang = $this->kasRepository->getTotalPelunasanHutang();
            $piutang = $this->kasRepository->getTotalPiutang();
            $pelunasanPiutang = $this->kasRepository->getTotalPelunasanPiutang();
        } else {
            $pemasukan = $this->kasRepository->getTotalPemasukan();
            $pengeluaran = $this->kasRepository->getTotalPengeluaran();
            $hutang = $this->kasRepository->getTotalHutang();
            $pelunasanHutang = $this->kasRepository->getTotalPelunasanHutang();
            $piutang = $this->kasRepository->getTotalPiutang();
            $pelunasanPiutang = $this->kasRepository->getTotalPelunasanPiutang();
        }

        $totalPembelianBarang = $this->kasRepository->getTotalPembelianBarang('0');
        $totalPembelianSaldoDigital = $this->kasRepository->getTotalPembelianSaldoDigital('0');
        $mutasiKeluar = $this->kasRepository->getMutasiKeluar($isSingle);
        $mutasiMasuk = $this->kasRepository->getMutasiMasuk($isSingle);

        $fixOmset += ($pemasukan - $pengeluaran);
        $fixOmset += ($hutang - $pelunasanHutang);
        $fixOmset -= ($piutang - $pelunasanPiutang);
        $fixOmset -= $totalPembelianBarang;
        $fixOmset -= $totalPembelianSaldoDigital;
        $fixOmset -= $mutasiKeluar;
        $fixOmset += $mutasiMasuk;

        $fixOmset = max($fixOmset, 0);

        return [
            'total' => $fixOmset,
            'format' => 'Rp ' . number_format($fixOmset, 0, ',', '.')
        ];
    }

    public function getKasKecil()
    {
        $isSingle = $this->isSingleToko();

        $totalOmset = $this->kasRepository->getTotalOmset(null);
        $totalRetur = $this->kasRepository->getTotalBiayaRetur(null);
        $totalKasbon = $this->kasRepository->getTotalKasbon(null);

        $fixKasKecil = $totalOmset - $totalRetur - $totalKasbon;

        if ($isSingle) {
            $pemasukan = $this->kasRepository->getTotalPemasukan(null, true, null);
            $pengeluaran = $this->kasRepository->getTotalPengeluaran(null, true, null);
            $hutang = $this->kasRepository->getTotalHutang(null, true, null);
            $pelunasanHutang = $this->kasRepository->getTotalPelunasanHutang(null, true);
            $piutang = $this->kasRepository->getTotalPiutang(null, true, null);
            $pelunasanPiutang = $this->kasRepository->getTotalPelunasanPiutang(null, true);

            $mutasiMasuk = $this->kasRepository->getMutasiMasukKasKecilSingle();
            $mutasiKeluar = $this->kasRepository->getMutasiKeluarKasKecilSingle();
        } else {
            $pemasukan = $this->kasRepository->getTotalPemasukan(null, false, null);
            $pengeluaran = $this->kasRepository->getTotalPengeluaran(null, false, null);
            $hutang = $this->kasRepository->getTotalHutang(null);
            $pelunasanHutang = $this->kasRepository->getTotalPelunasanHutang(null);
            $piutang = $this->kasRepository->getTotalPiutang(null);
            $pelunasanPiutang = $this->kasRepository->getTotalPelunasanPiutang(null);

            $mutasiMasuk = $this->kasRepository->getMutasiMasukKasKecilMulti();
            $mutasiKeluar = $this->kasRepository->getMutasiKeluarKasKecilMulti();
        }
        $totalPembelianBarang = $this->kasRepository->getTotalPembelianBarang('1');
        $totalPembelianSaldoDigital = $this->kasRepository->getTotalPembelianSaldoDigital('1');
        $pendapatanNonFisik = $this->kasRepository->getPendapatanNonFisik();

        $fixKasKecil += ($pemasukan - $pengeluaran);
        $fixKasKecil += ($hutang - $pelunasanHutang);
        $fixKasKecil -= ($piutang - $pelunasanPiutang);
        $fixKasKecil -= $totalPembelianBarang;
        $fixKasKecil -= $totalPembelianSaldoDigital;
        $fixKasKecil += $mutasiMasuk;
        $fixKasKecil -= $mutasiKeluar;
        $fixKasKecil += $pendapatanNonFisik;

        $fixKasKecil = max($fixKasKecil, 0);

        return [
            'total' => $fixKasKecil,
            'format' => 'Rp ' . number_format($fixKasKecil, 0, ',', '.')
        ];
    }

    public function getKasNeracaJenisBarang($kas, $jenis, ?int $month = null, ?int $year = null)
    {
        $isSingle = $this->isSingleToko();
        $fixKas = 0;

        // ==============================
        // === CASE: DOMPET DIGITAL ====
        // ==============================
        if ($jenis == 0) {
            $namaJenisBarang = 'Dompet Digital';
            $totalPembelianBarang = 0;
            if ($kas == 1) {
                // Hanya kas kecil yang memiliki dompet digital
                $fixKas = $this->kasNeracaRepository->getPendapatanNonFisik($month, $year);

                // Mutasi dompet digital antar kas kecil dan besar
                $mutasiKeluar = $this->kasNeracaRepository->getMutasiOut(1, 0, $month, $year);
                $mutasiMasuk  = $this->kasNeracaRepository->getMutasiIn(1, 0, $month, $year);

                $totalPembelianSaldoDigital = $this->kasNeracaRepository->getTotalPembelianSaldoDigital(1, 0, $month, $year);
            } else {
                // Kas besar tidak memiliki saldo dompet digital
                $mutasiKeluar = $this->kasNeracaRepository->getMutasiOut(0, 0, $month, $year);
                $mutasiMasuk  = $this->kasNeracaRepository->getMutasiIn(0, 0, $month, $year);
                $totalPembelianSaldoDigital = 0;
            }
        }

        // ==============================
        // === CASE: JENIS BARANG FISIK ====
        // ==============================
        else {
            if ($kas == 1) {
                $totalOmset  = $this->kasNeracaRepository->getTotalOmset(null, $jenis, $month, $year);
                $totalRetur  = $this->kasNeracaRepository->getTotalBiayaRetur(null, $jenis, $month, $year);
                $totalRefund = $this->kasNeracaRepository->getTotalRefundSuplier(null, $jenis, $month, $year);
                $totalKasbon = $this->kasNeracaRepository->getTotalKasbon(null, $month, $year);

                $fixKas = $totalOmset - $totalRetur + $totalRefund - $totalKasbon;
            }

            // Hitung mutasi antar kas untuk jenis barang ini
            $mutasiKeluar = $this->kasNeracaRepository->getMutasiOut($kas, $jenis, $month, $year);
            $mutasiMasuk  = $this->kasNeracaRepository->getMutasiIn($kas, $jenis, $month, $year);

            // Pembelian saldo digital tidak relevan di sini
            $totalPembelianSaldoDigital = 0;

            $namaJenisBarang = DB::table('jenis_barang')
                ->where('id', $jenis)
                ->whereNull('deleted_at')
                ->value('nama_jenis_barang');

            $totalPembelianBarang = $this->kasNeracaRepository->getTotalPembelianBarang($kas, $jenis, $month, $year);
        }

        // ==============================
        // === PERHITUNGAN UMUM KAS ====
        // ==============================

        if ($isSingle) {
            $pemasukan         = $this->kasNeracaRepository->getTotalPemasukan(null, $kas, $jenis, $month, $year);
            $pengeluaran       = $this->kasNeracaRepository->getTotalPengeluaran(null, $kas, $jenis, $month, $year);
            $hutang            = $this->kasNeracaRepository->getTotalHutang(null, $kas, $jenis, $month, $year);
            $pelunasanHutang   = $this->kasNeracaRepository->getTotalPelunasanHutang(null, $kas, $jenis, $month, $year);
            $piutang           = $this->kasNeracaRepository->getTotalPiutang(null, $kas, $jenis, $month, $year);
            $pelunasanPiutang  = $this->kasNeracaRepository->getTotalPelunasanPiutang(null, $kas, null, $month, $year);
        } else {
            $pemasukan         = $this->kasNeracaRepository->getTotalPemasukan(null, false, $jenis, $month, $year);
            $pengeluaran       = $this->kasNeracaRepository->getTotalPengeluaran(null, false, $jenis, $month, $year);
            $hutang            = $this->kasNeracaRepository->getTotalHutang(null, false, null, $month, $year);
            $pelunasanHutang   = $this->kasNeracaRepository->getTotalPelunasanHutang(null, false, $jenis, $month, $year);
            $piutang           = $this->kasNeracaRepository->getTotalPiutang(null, false, $jenis, $month, $year);
            $pelunasanPiutang  = $this->kasNeracaRepository->getTotalPelunasanPiutang(null, false, null, $month, $year);
        }

        // ==============================
        // === HITUNG SALDO AKHIR ====
        // ==============================
        $fixKas += ($pemasukan - $pengeluaran);
        $fixKas += ($hutang - $pelunasanHutang);
        $fixKas -= ($piutang - $pelunasanPiutang);
        $fixKas -= $totalPembelianBarang;
        $fixKas += $mutasiMasuk;
        $fixKas -= $mutasiKeluar;
        $fixKas -= $totalPembelianSaldoDigital;

        return [
            'jenis_barang' => $namaJenisBarang,
            'total' => $fixKas,
            'format' => 'Rp ' . number_format($fixKas, 0, ',', '.')
        ];
    }

    public function getKasJenisBarang($kas, $jenis, ?int $month = null, ?int $year = null)
    {
        $isSingle = $this->isSingleToko();
        $fixKas = 0;

        // ==============================
        // === CASE: DOMPET DIGITAL ====
        // ==============================
        if ($jenis == 0) {
            $namaJenisBarang = 'Dompet Digital';
            $totalPembelianBarang = 0;
            if ($kas == 1) {
                // Hanya kas kecil yang memiliki dompet digital
                $fixKas = $this->kasRepository->getPendapatanNonFisik();

                // Mutasi dompet digital antar kas kecil dan besar
                $mutasiKeluar = $this->kasRepository->getMutasiOut(1, 0);
                $mutasiMasuk  = $this->kasRepository->getMutasiIn(1, 0);

                $totalPembelianSaldoDigital = $this->kasRepository->getTotalPembelianSaldoDigital(1, 0);
            } else {
                // Kas besar tidak memiliki saldo dompet digital
                $mutasiKeluar = $this->kasRepository->getMutasiOut(0, 0);
                $mutasiMasuk  = $this->kasRepository->getMutasiIn(0, 0);
                $totalPembelianSaldoDigital = 0;
            }
        }

        // ==============================
        // === CASE: JENIS BARANG FISIK ====
        // ==============================
        else {
            if ($kas == 1) {
                $totalOmset  = $this->kasRepository->getTotalOmset(null, $jenis);
                $totalRetur  = $this->kasRepository->getTotalBiayaRetur(null, $jenis);
                $totalRefund = $this->kasRepository->getTotalRefundSuplier(null, $jenis);
                $totalKasbon = $this->kasRepository->getTotalKasbon(null);

                $fixKas = $totalOmset - $totalRetur + $totalRefund - $totalKasbon;
            }

            // Hitung mutasi antar kas untuk jenis barang ini
            $mutasiKeluar = $this->kasRepository->getMutasiOut($kas, $jenis);
            $mutasiMasuk  = $this->kasRepository->getMutasiIn($kas, $jenis);

            // Pembelian saldo digital tidak relevan di sini
            $totalPembelianSaldoDigital = 0;

            $namaJenisBarang = DB::table('jenis_barang')
                ->where('id', $jenis)
                ->whereNull('deleted_at')
                ->value('nama_jenis_barang');

            $totalPembelianBarang = $this->kasRepository->getTotalPembelianBarang($kas, $jenis);
        }

        // ==============================
        // === PERHITUNGAN UMUM KAS ====
        // ==============================

        if ($isSingle) {
            $pemasukan         = $this->kasRepository->getTotalPemasukan(null, $kas, $jenis);
            $pengeluaran       = $this->kasRepository->getTotalPengeluaran(null, $kas, $jenis);
            $hutang            = $this->kasRepository->getTotalHutang(null, $kas, $jenis);
            $pelunasanHutang   = $this->kasRepository->getTotalPelunasanHutang(null, $kas, $jenis);
            $piutang           = $this->kasRepository->getTotalPiutang(null, $kas, $jenis);
            $pelunasanPiutang  = $this->kasRepository->getTotalPelunasanPiutang(null, $kas);
        } else {
            $pemasukan         = $this->kasRepository->getTotalPemasukan(null, false, $jenis);
            $pengeluaran       = $this->kasRepository->getTotalPengeluaran(null, false, $jenis);
            $hutang            = $this->kasRepository->getTotalHutang(null, false);
            $pelunasanHutang   = $this->kasRepository->getTotalPelunasanHutang(null, false, $jenis);
            $piutang           = $this->kasRepository->getTotalPiutang(null, false, $jenis);
            $pelunasanPiutang  = $this->kasRepository->getTotalPelunasanPiutang(null, false);
        }

        // ==============================
        // === HITUNG SALDO AKHIR ====
        // ==============================
        $fixKas += ($pemasukan - $pengeluaran);
        $fixKas += ($hutang - $pelunasanHutang);
        $fixKas -= ($piutang - $pelunasanPiutang);
        $fixKas -= $totalPembelianBarang;
        $fixKas += $mutasiMasuk;
        $fixKas -= $mutasiKeluar;
        $fixKas -= $totalPembelianSaldoDigital;

        return [
            'jenis_barang' => $namaJenisBarang,
            'total' => $fixKas,
            'format' => 'Rp ' . number_format($fixKas, 0, ',', '.')
        ];
    }

    public function getKasJenisBarangOptimized($kas, $jenisList)
    {
        $isSingle = $this->isSingleToko();

        // Ambil semua data yang dibutuhkan dalam sekali query per tabel
        $pemasukan = $this->kasRepository->getTotalPemasukanGrouped($isSingle, $kas, $jenisList);
        $pengeluaran = $this->kasRepository->getTotalPengeluaranGrouped($isSingle, $kas, $jenisList);
        $hutang = $this->kasRepository->getTotalHutangGrouped($isSingle, $kas, $jenisList);
        $pelunasanHutang = $this->kasRepository->getTotalPelunasanHutangGrouped($isSingle, $kas, $jenisList);
        $piutang = $this->kasRepository->getTotalPiutangGrouped($isSingle, $kas, $jenisList);
        $pelunasanPiutang = $this->kasRepository->getTotalPelunasanPiutangGrouped($isSingle, $kas, $jenisList);
        $mutasiOut = $this->kasRepository->getMutasiOutGrouped($kas, $jenisList);
        $mutasiIn = $this->kasRepository->getMutasiInGrouped($kas, $jenisList);
        $pembelianBarang = $this->kasRepository->getTotalPembelianBarangGrouped($kas, $jenisList);
        $pembelianSaldoDigital = $this->kasRepository->getTotalPembelianSaldoDigitalGrouped($kas, $jenisList);

        // Simpan hasil final per jenis
        $result = [];

        foreach ($jenisList as $jenis) {
            $fixKas = 0;

            // Dompet digital (jenis = 0)
            if ($jenis == 0) {
                if ($kas == 1) {
                    $fixKas = $this->kasRepository->getPendapatanNonFisik();
                }
                $namaJenisBarang = 'Dompet Digital';
            } else {
                if ($kas == 1) {
                    $totalOmset = $this->kasRepository->getTotalOmset(null, $jenis);
                    $totalRetur = $this->kasRepository->getTotalBiayaRetur(null);
                    $totalKasbon = $this->kasRepository->getTotalKasbon(null);
                    $fixKas = $totalOmset - $totalRetur - $totalKasbon;
                }
                $namaJenisBarang = DB::table('jenis_barang')
                    ->where('id', $jenis)
                    ->value('nama_jenis_barang');
            }

            // Optimasi hitung fixKas pakai hasil group query
            $fixKas += ($pemasukan[$jenis] ?? 0) - ($pengeluaran[$jenis] ?? 0);
            $fixKas += ($hutang[$jenis] ?? 0) - ($pelunasanHutang[$jenis] ?? 0);
            $fixKas -= ($piutang[$jenis] ?? 0) - ($pelunasanPiutang[$jenis] ?? 0);
            $fixKas -= ($pembelianBarang[$jenis] ?? 0);
            $fixKas += ($mutasiIn[$jenis] ?? 0);
            $fixKas -= ($mutasiOut[$jenis] ?? 0);
            $fixKas -= ($pembelianSaldoDigital[$jenis] ?? 0);

            $result[$jenis] = [
                'jenis_barang' => $namaJenisBarang,
                'total'        => $fixKas,
                'format'       => 'Rp ' . number_format($fixKas, 0, ',', '.'),
            ];
        }

        return $result;
    }

    public function getPemasukkanLainnya()
    {
        $isSingle = $this->isSingleToko();

        $totalOmset = $this->kasRepository->getTotalOmset(null);
        $totalRetur = $this->kasRepository->getTotalBiayaRetur(null);
        $totalKasbon = $this->kasRepository->getTotalKasbon(null);

        $fixKasKecil = $totalOmset - $totalRetur - $totalKasbon;

        if ($isSingle) {
            $pemasukan = $this->kasRepository->getTotalPemasukan(null, true);
            $pengeluaran = $this->kasRepository->getTotalPengeluaran(null, true);
        } else {
            $pemasukan = $this->kasRepository->getTotalPemasukan(null);
            $pengeluaran = $this->kasRepository->getTotalPengeluaran(null);
        }

        $fixKasKecil += ($pemasukan - $pengeluaran);

        $fixKasKecil = max($fixKasKecil, 0);

        return [
            'total' => $fixKasKecil,
            'format' => 'Rp ' . number_format($fixKasKecil, 0, ',', '.')
        ];
    }

    public function getPiutang()
    {
        $isSingle = $this->isSingleToko();
        $totalKasbon = $this->kasRepository->getTotalKasbon(null);

        if ($isSingle) {
            $piutang = $this->kasRepository->getTotalPiutang(null, true);
            $pelunasanPiutang = $this->kasRepository->getTotalPelunasanPiutang(null, true);
        } else {
            $piutang = $this->kasRepository->getTotalPiutang(null);
            $pelunasanPiutang = $this->kasRepository->getTotalPelunasanPiutang(null);
        }

        $fixPiutang = $totalKasbon + ($piutang - $pelunasanPiutang);

        return [
            'total' => $fixPiutang,
            'format' => 'Rp ' . number_format($fixPiutang, 0, ',', '.')
        ];
    }
}
