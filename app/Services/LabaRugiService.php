<?php

namespace App\Services;

use App\Helpers\RupiahGenerate;
use App\Models\{Pemasukan, Pengeluaran, PengeluaranTipe, KasTransaksi, TransaksiKasirHarian};
use App\Models\PenjualanNonFisikDetail;
use App\Models\ReturMember;
use App\Models\{LabaRugi, LabaRugiTahunan};
use App\Models\StockBarangBermasalah;
use App\Models\TransaksiKasir;

class LabaRugiService
{
    public function hitungLabaRugi($month, $year, $tokoId)
    {
        return $this->hitungDetailLabaRugi($month, $year, $tokoId, true);
    }

    public function hitungLabaRugiTahunSebelumnya($year, $tokoId)
    {
        $query = LabaRugiTahunan::where('tahun', '<', $year);

        if ($tokoId !== 'all') {
            $query->where('toko_id', $tokoId);
        }

        return (float) $query->sum('laba_bersih');
    }

    public function hitungLabaRugiRange($month, $year, $tokoId)
    {
        $results = [];

        $query = LabaRugi::where('tahun', $year)
            ->where('bulan', '<=', $month);

        if ($tokoId !== 'all') {
            $query->where('toko_id', $tokoId);
        }

        $data = $query
            ->pluck('laba_bersih', 'bulan')
            ->toArray();

        for ($i = 1; $i <= $month; $i++) {
            $results[$i] = $data[$i] ?? 0;
        }

        return $results;
    }

    public function hitungDetailLabaRugi($month, $year, $tokoId = 'all', $isNeraca = false)
    {
        // Helper closure untuk filter toko
        $filterToko = function ($query) use ($tokoId) {
            $query->whereHas('kas', function ($q) use ($tokoId) {
                if ($tokoId !== 'all') {
                    $q->where('toko_id', $tokoId);
                }
            });
        };

        // ============================
        // PENDAPATAN
        // ============================

        $penjualanUmum = KasTransaksi::where('tipe', 'in')
            ->where('sumber_type', TransaksiKasirHarian::class)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where($filterToko)
            ->sum('total_nominal');

        $penjualanNF = KasTransaksi::where('tipe', 'in')
            ->where('sumber_type', PenjualanNonFisikDetail::class)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where($filterToko)
            ->sum('total_nominal');

        $pendapatanLainnya = KasTransaksi::where('tipe', 'in')
            ->where('sumber_type', Pemasukan::class)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where($filterToko)
            ->whereHasMorph('sumber', [Pemasukan::class], function ($q) {
                $q->whereNotIn('pemasukan_tipe_id', [1, 2]);
            })
            ->sum('total_nominal');

        $nilaiReturMember = KasTransaksi::where('tipe', 'in')
            ->where('sumber_type', ReturMember::class)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where($filterToko)
            ->sum('total_nominal');

        $pendapatanNonTransaksi = 0;

        $penjualanUmum += $penjualanNF;
        $totalPendapatan = $penjualanUmum + $pendapatanLainnya + $nilaiReturMember + $pendapatanNonTransaksi;

        // ============================
        // HPP (sementara 0)
        // ============================
        $hppPenjualan = KasTransaksi::where('tipe', 'in')
            ->where('sumber_type', TransaksiKasirHarian::class)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where($filterToko)
            ->with('sumber')
            ->get()
            ->sum(fn($kt) => (float) $kt->sumber->total_hpp ?? 0);

        $hppretur = 0;
        $hppBarangGanti = 0;

        $total_hpp = $hppPenjualan + $hppretur + $hppBarangGanti;

        // ============================
        // BEBAN OPERASIONAL
        // ============================

        $pengeluaran = KasTransaksi::where('tipe', 'out')
            ->where('sumber_type', Pengeluaran::class)
            ->whereMonth('kas_transaksi.tanggal', $month)
            ->whereYear('kas_transaksi.tanggal', $year)
            ->where($filterToko)
            ->join('pengeluaran', 'kas_transaksi.sumber_id', '=', 'pengeluaran.id')
            ->selectRaw('pengeluaran.pengeluaran_tipe_id, SUM(kas_transaksi.total_nominal) as total')
            ->groupBy('pengeluaran.pengeluaran_tipe_id')
            ->get()
            ->keyBy('pengeluaran_tipe_id');

        $bebanOperasional = [];
        $totalBeban = 0;

        $jenisList = PengeluaranTipe::where('id', '!=', 11)->get();

        foreach ($jenisList as $index => $jenis) {

            $nilai = isset($pengeluaran[$jenis->id])
                ? (float) $pengeluaran[$jenis->id]->total
                : 0;

            $label = '3.' . ($index + 1) . ' ' . $jenis->tipe;

            $bebanOperasional[] = [
                'label' => $label,
                'value' => $nilai
            ];

            $totalBeban += $nilai;
        }

        // ============================
        // STOK HILANG / BARANG BERMASALAH
        // ============================

        $stockBermasalah = KasTransaksi::where('tipe', 'out')
            ->where('sumber_type', StockBarangBermasalah::class)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where($filterToko)
            ->sum('total_nominal');

        $nextNumber = count($jenisList) + 1;

        $bebanOperasional[] = [
            'label' => '3.' . $nextNumber . ' Stok Barang Hilang',
            'value' => $stockBermasalah
        ];

        $totalBeban += $stockBermasalah;

        $bebanOperasional[] = [
            'label' => 'Total Beban Operasional',
            'value' => $totalBeban
        ];

        // ============================
        // LABA RUGI
        // ============================
        $total_labarugi = $totalPendapatan - $total_hpp - $totalBeban;

        if ($isNeraca) {
            return (float) $total_labarugi;
        }

        $totalPendapatan = (float) $totalPendapatan;
        $total_hpp = (float) $total_hpp;
        $totalBeban = (float) $totalBeban;
        $total_labarugi = (float) $total_labarugi;

        return $this->getDetailLaporan(
            $penjualanUmum,
            $pendapatanLainnya,
            $nilaiReturMember,
            $totalPendapatan,
            $hppPenjualan,
            $hppretur,
            $hppBarangGanti,
            $total_hpp,
            $bebanOperasional,
            $total_labarugi,
            $pendapatanNonTransaksi
        );
    }

    protected function getDetailLaporan(
        $penjualanUmum,
        $pendapatanLainnya,
        $assetRetur,
        $totalPendapatan,
        $hppPenjualan,
        $hppretur,
        $hppBarangGanti,
        $total_hpp,
        $bebanOperasional,
        $total_labarugi,
        $pendapatanNonTransaksi
    ) {
        return [
            [
                'I. Pendapatan',
                [
                    ['1.1 Penjualan Umum', RupiahGenerate::build($penjualanUmum)],
                    ['1.2 Pendapatan Lainnya', RupiahGenerate::build($pendapatanLainnya)],
                    ['1.3 Pengembalian Retur', RupiahGenerate::build($assetRetur)],
                    ['1.4 Pendapatan Non Transaksi', RupiahGenerate::build($pendapatanNonTransaksi)],
                    ['Total Pendapatan', RupiahGenerate::build($totalPendapatan)]
                ]
            ],
            [
                'II. HPP',
                [
                    ['2.1 HPP Penjualan', RupiahGenerate::build($hppPenjualan)],
                    ['2.2 HPP Retur', RupiahGenerate::build($hppretur)],
                    ['2.3 HPP Barang Ganti', RupiahGenerate::build($hppBarangGanti)],
                    ['Total HPP', RupiahGenerate::build($total_hpp)]
                ]
            ],
            [
                'III. Biaya Pengeluaran',
                array_map(function ($item) {
                    return [$item['label'], RupiahGenerate::build($item['value'])];
                }, $bebanOperasional)
            ],
            [
                'IV. Laba Rugi',
                [
                    ['Laba Rugi Ditahan', RupiahGenerate::build($total_labarugi)]
                ]
            ],
        ];
    }

    protected function getTotalLabaRugi($total_labarugi)
    {
        return number_format((float) $total_labarugi, 0, ',', '.');
    }
}
