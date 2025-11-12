<?php

namespace App\Services;

use App\Repositories\LaporanPenjualanRepository;
use Carbon\Carbon;

class LaporanPenjualanService
{
    protected $repository;

    public function __construct(LaporanPenjualanRepository $repository)
    {
        $this->repository = $repository;
    }

    public function generateReport($startDate, $endDate, $idToko = null)
    {
        $kasirData = $this->repository->getKasirData($startDate, $endDate, $idToko);
        $penjualanNonFisikData = $this->repository->getPenjualanNonFisik($startDate, $endDate, $idToko);

        $summaryPerToko = [];

        $totalTrx = $totalNilaiTrx = $totalHpp = $totalRetur = $totalKasbon = $totalSubLaba = 0;

        $globalTypeBarang = [];
        $globalDetailLaporan = [
            'transaksi_member' => [
                'jumlah' => 0,
                'nilai'  => 0,
                'hpp'    => 0,
                'laba'   => 0
            ],
            'transaksi_non_member' => [
                'jumlah' => 0,
                'nilai'  => 0,
                'hpp'    => 0,
                'laba'   => 0
            ],
        ];

        /**
         * === FASE 1: PROSES DATA KASIR ===
         */
        foreach ($kasirData->groupBy('id_toko') as $idTokoKey => $kasirList) {
            $jmlTrx = $kasirList->count();
            $nilaiTrx = $kasirList->sum('total_nilai');
            $nilaiHpp = $memberHpp = $nonMemberHpp = 0;
            $memberTrxCount = $memberTrxValue = $nonMemberTrxCount = $nonMemberTrxValue = 0;
            $typeBarang = [];

            foreach ($kasirList as $kasir) {
                $isMember = $kasir->id_member && $kasir->id_member != '0';

                if ($isMember) {
                    $memberTrxCount++;
                    $memberTrxValue += $kasir->total_nilai;
                } else {
                    $nonMemberTrxCount++;
                    $nonMemberTrxValue += $kasir->total_nilai;
                }

                $idJenisCollected = [];

                foreach ($kasir->detail_kasir as $detail) {
                    if (!is_object($detail)) continue;

                    $hppJual = $detail->hpp_jual ?? 0;
                    $totalHarga = $detail->total_harga ?? 0;
                    $qty = $detail->qty ?? 1;

                    $nilaiHpp += $hppJual * $qty;
                    if ($isMember) $memberHpp += $hppJual * $qty;
                    else $nonMemberHpp += $hppJual * $qty;

                    $jenisBarang = $detail->jenisBarang ?? null;
                    $idJenis = $jenisBarang->id ?? 'AAAA';
                    $namaJenis = $jenisBarang->nama_jenis_barang ?? 'LAINNYA';

                    if (!isset($typeBarang[$idJenis])) {
                        $typeBarang[$idJenis] = [
                            'id_jenis_barang' => $idJenis,
                            'nama'            => $namaJenis,
                            'jml_trx'         => 0,
                            'item_qty'        => 0,
                            'nilai_trx'       => 0,
                            'nilai_hpp'       => 0,
                            'laba'            => 0,
                        ];
                    }

                    $typeBarang[$idJenis]['item_qty'] += $qty;
                    $typeBarang[$idJenis]['nilai_trx'] += $totalHarga;
                    $typeBarang[$idJenis]['nilai_hpp'] += $hppJual * $qty;
                    $typeBarang[$idJenis]['laba']      += ($totalHarga - ($hppJual * $qty));

                    $idJenisCollected[$idJenis] = true;
                }

                foreach (array_keys($idJenisCollected) as $idJenis) {
                    $typeBarang[$idJenis]['jml_trx']++;
                }
            }

            ksort($typeBarang);

            $biayaRetur = $this->repository->getBiayaRetur($startDate, $endDate, $idTokoKey);
            $kasbon = $this->repository->getKasbon($startDate, $endDate, $idTokoKey);

            $labaKotor = max($nilaiTrx - $nilaiHpp - $biayaRetur - $kasbon, 0);

            $toko = $kasirList->first()?->toko;
            $areaToko = $toko
                ? "{$toko->nama_toko} ({$toko->wilayah})"
                : 'Toko Tidak Diketahui';

            $summaryPerToko[] = [
                'id_toko'    => $idTokoKey,
                'area_toko'  => $areaToko,
                'jml_trx'    => $jmlTrx,
                'nilai_trx'  => $nilaiTrx,
                'nilai_hpp'  => $nilaiHpp,
                'biaya_retur' => $biayaRetur,
                'kasbon'     => $kasbon,
                'sub_laba'   => $labaKotor,
            ];

            $totalTrx    += $jmlTrx;
            $totalNilaiTrx += $nilaiTrx;
            $totalHpp    += $nilaiHpp;
            $totalRetur  += $biayaRetur;
            $totalKasbon += $kasbon;
            $totalSubLaba += $labaKotor;

            if ($idToko == 1 || $idToko === null) {
                foreach ($typeBarang as $idJenis => $data) {
                    if (!isset($globalTypeBarang[$idJenis])) $globalTypeBarang[$idJenis] = $data;
                    else {
                        $globalTypeBarang[$idJenis]['jml_trx']   += $data['jml_trx'];
                        $globalTypeBarang[$idJenis]['item_qty']  += $data['item_qty'];
                        $globalTypeBarang[$idJenis]['nilai_trx'] += $data['nilai_trx'];
                        $globalTypeBarang[$idJenis]['nilai_hpp'] += $data['nilai_hpp'];
                        $globalTypeBarang[$idJenis]['laba']      += $data['laba'];
                    }
                }

                $globalDetailLaporan['transaksi_member']['jumlah'] += $memberTrxCount;
                $globalDetailLaporan['transaksi_member']['nilai']  += $memberTrxValue;
                $globalDetailLaporan['transaksi_member']['hpp']    += $memberHpp;
                $globalDetailLaporan['transaksi_member']['laba']   += max($memberTrxValue - $memberHpp, 0);

                $globalDetailLaporan['transaksi_non_member']['jumlah'] += $nonMemberTrxCount;
                $globalDetailLaporan['transaksi_non_member']['nilai']  += $nonMemberTrxValue;
                $globalDetailLaporan['transaksi_non_member']['hpp']    += $nonMemberHpp;
                $globalDetailLaporan['transaksi_non_member']['laba']   += max($nonMemberTrxValue - $nonMemberHpp, 0);
            }
        }

        /**
         * === FASE 2: PROSES DATA PENJUALAN NON FISIK ===
         */
        foreach ($penjualanNonFisikData as $pnf) {
            $idTokoKey = $pnf->createdBy?->id_toko ?? 0;

            $nilaiTrx = $pnf->total_harga_jual;
            $nilaiHpp = $pnf->total_hpp;
            $laba     = $nilaiTrx - $nilaiHpp;

            $toko = $pnf->createdBy?->toko;
            $areaToko = $toko
                ? "{$toko->nama_toko} ({$toko->wilayah})"
                : 'Toko Tidak Diketahui';

            // cek apakah toko ini sudah ada di summary
            $existingKey = null;
            foreach ($summaryPerToko as $key => $row) {
                if ($row['id_toko'] == $idTokoKey) {
                    $existingKey = $key;
                    break;
                }
            }

            if ($existingKey !== null) {
                // update data toko yg sudah ada
                $summaryPerToko[$existingKey]['jml_trx']   += 1;
                $summaryPerToko[$existingKey]['nilai_trx'] += $nilaiTrx;
                $summaryPerToko[$existingKey]['nilai_hpp'] += $nilaiHpp;
                $summaryPerToko[$existingKey]['sub_laba']  += $laba;
            } else {
                // kalau belum ada, buat entry baru
                $summaryPerToko[] = [
                    'id_toko'    => $idTokoKey,
                    'area_toko'  => $areaToko,
                    'jml_trx'    => 1,
                    'nilai_trx'  => $nilaiTrx,
                    'nilai_hpp'  => $nilaiHpp,
                    'biaya_retur' => 0,
                    'kasbon'     => 0,
                    'sub_laba'   => $laba,
                ];
            }

            $totalTrx++;
            $totalNilaiTrx += $nilaiTrx;
            $totalHpp      += $nilaiHpp;
            $totalSubLaba  += $laba;

            if ($idToko == 1 || $idToko === null) {
                $idJenis = "DOMPET-{$pnf->dompet_kategori_id}";
                $namaJenis = $pnf->dompetKategori?->nama ? "Dompet Digital ({$pnf->dompetKategori->nama})" : 'Dompet Digital';
                $qty = $pnf->detail_sum_qty;

                if (!isset($globalTypeBarang[$idJenis])) {
                    $globalTypeBarang[$idJenis] = [
                        'id_jenis_barang' => $idJenis,
                        'nama'            => $namaJenis,
                        'jml_trx'         => 0,
                        'item_qty'        => 0,
                        'nilai_trx'       => 0,
                        'nilai_hpp'       => 0,
                        'laba'            => 0,
                    ];
                }

                $globalTypeBarang[$idJenis]['jml_trx']   += 1;
                $globalTypeBarang[$idJenis]['item_qty']  += $qty;
                $globalTypeBarang[$idJenis]['nilai_trx'] += $nilaiTrx;
                $globalTypeBarang[$idJenis]['nilai_hpp'] += $nilaiHpp;
                $globalTypeBarang[$idJenis]['laba']      += $laba;
            }
        }

        /**
         * === TOTAL ===
         */
        $total = [
            'jml_trx'    => $totalTrx,
            'nilai_trx'  => $totalNilaiTrx,
            'nilai_hpp'  => $totalHpp,
            'biaya_retur' => $totalRetur,
            'kasbon'     => $totalKasbon,
            'sub_laba'   => $totalSubLaba,
        ];

        if ($idToko && $idToko != 1) {
            return [
                'summary_per_toko' => $summaryPerToko,
                'total'            => $total
            ];
        }

        return [
            'periode_transaksi_penjualan' => [
                'tanggal_awal'  => $startDate,
                'tanggal_akhir' => $endDate,
            ],
            'laporan_penjualan_periode' => "{$startDate} s/d {$endDate}",
            'summary_per_toko'          => $summaryPerToko,
            'total'                     => $total,
            'type_barang'               => array_values($globalTypeBarang),
            'detail_laporan'            => $globalDetailLaporan,
        ];
    }
}
