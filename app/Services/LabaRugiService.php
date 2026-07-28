<?php

namespace App\Services;

use App\Helpers\RupiahGenerate;
use App\Models\DompetSaldo;
use App\Models\KasTransaksi;
use App\Models\LabaRugi;
use App\Models\LabaRugiTahunan;
use App\Models\Pemasukan;
use App\Models\PembelianBarangDetailAdjustment;
use App\Models\Pengeluaran;
use App\Models\PengeluaranTipe;
use App\Models\ReturMember;
use App\Models\ReturSupplierDetail;
use App\Models\StockBarangBermasalah;
use App\Models\TransaksiKasirHarian;

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

        return (int) $query->sum('laba_bersih');
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
        // 1. Tentukan rentang waktu berdasarkan kebutuhan (Neraca vs Laba Rugi Bulanan)
        $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateTimeString();
        $endDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateTimeString();
        $endOfDateOnly = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');

        // Scope Global Filter Toko untuk KasTransaksi
        $filterToko = function ($query) use ($tokoId) {
            $query->whereHas('kas', function ($q) use ($tokoId) {
                if ($tokoId !== 'all' && $tokoId !== null && $tokoId != 0) {
                    $q->where('toko_id', $tokoId);
                }
            });
        };

        // Helper Filter untuk query non-KasTransaksi
        $applyTokoDirect = function ($query, $column = 'toko_id') use ($tokoId) {
            if ($tokoId !== 'all' && $tokoId !== null && $tokoId != 0) {
                $query->where($column, $tokoId);
            }
        };

        // Helper Filter Tanggal (Jika Neraca = Akumulatif dari awal, Jika LabaRugi = Hanya bulan itu)
        $applyDateFilter = function ($query, $column) use ($isNeraca, $startDate, $endDate) {
            if ($isNeraca) {
                $query->where($column, '<=', $endDate);
            } else {
                $query->whereBetween($column, [$startDate, $endDate]);
            }
        };

        $applyDateFilterOnly = function ($query, $column) use ($isNeraca, $endOfDateOnly, $month, $year) {
            if ($isNeraca) {
                $query->where($column, '<=', $endOfDateOnly);
            } else {
                $query->whereYear($column, $year)->whereMonth($column, $month);
            }
        };

        // ============================
        // PENDAPATAN
        // ============================

        $penjualanUmumQuery = KasTransaksi::where('tipe', 'in')
            ->where('sumber_type', TransaksiKasirHarian::class)
            ->where($filterToko);
        $applyDateFilter($penjualanUmumQuery, 'tanggal');
        $penjualanUmum = $penjualanUmumQuery->sum('total_nominal');

        $lainnyaQuery = KasTransaksi::where('tipe', 'in')
            ->where('sumber_type', Pemasukan::class)
            ->where($filterToko)
            ->whereHasMorph('sumber', [Pemasukan::class], function ($q) {
                $q->whereNotIn('pemasukan_tipe_id', [1, 2]);
            });
        $applyDateFilter($lainnyaQuery, 'tanggal');
        $lainnya = $lainnyaQuery->sum('total_nominal');

        $nilaiReturMemberQuery = KasTransaksi::where('tipe', 'out')
            ->where('sumber_type', ReturMember::class)
            ->where($filterToko);
        $applyDateFilter($nilaiReturMemberQuery, 'tanggal');
        $nilaiReturMember = $nilaiReturMemberQuery->sum('total_nominal');

        $nilaiReturSuplierQuery = KasTransaksi::where('tipe', 'in')
            ->where('sumber_type', ReturSupplierDetail::class)
            ->where($filterToko);
        $applyDateFilter($nilaiReturSuplierQuery, 'tanggal');
        $nilaiReturSuplier = $nilaiReturSuplierQuery->sum('total_nominal');

        $pendapatanNonTransaksiQuery = DompetSaldo::where($filterToko);
        $applyDateFilter($pendapatanNonTransaksiQuery, 'created_at');
        $pendapatanNonTransaksi = $pendapatanNonTransaksiQuery->get()
            ->filter(fn ($row) => $row->harga_beli < $row->saldo)
            ->sum(fn ($row) => $row->saldo - $row->harga_beli);

        $pendapatanLainnya = $lainnya + $pendapatanNonTransaksi;
        $penjualanUmum -= $nilaiReturMember;
        $totalPendapatan = $penjualanUmum + $pendapatanLainnya + $nilaiReturSuplier;

        // ============================
        // HPP (Eager Loading dioptimasi via Join/Sum langsung)
        // ============================

        $hppTrxQuery = KasTransaksi::where('kas_transaksi.tipe', 'in')
            ->where('kas_transaksi.sumber_type', TransaksiKasirHarian::class)
            ->where($filterToko)
            ->join('transaksi_kasir_harian', 'kas_transaksi.sumber_id', '=', 'transaksi_kasir_harian.id');
        $applyDateFilter($hppTrxQuery, 'kas_transaksi.tanggal');
        $hppTrx = $hppTrxQuery->sum('transaksi_kasir_harian.total_harga_beli');

        $hppKoreksiQuery = PembelianBarangDetailAdjustment::query();
        $applyTokoDirect($hppKoreksiQuery, 'toko_id');
        $applyDateFilter($hppKoreksiQuery, 'created_at');
        $hppKoreksi = $hppKoreksiQuery->sum('nominal_laba_rugi');

        $hppreturQuery = KasTransaksi::where('kas_transaksi.tipe', 'out')
            ->where('kas_transaksi.sumber_type', ReturMember::class)
            ->where($filterToko)
            ->join('retur_member', 'retur_member.id', '=', 'kas_transaksi.sumber_id')
            ->join('retur_member_detail', 'retur_member_detail.retur_id', '=', 'retur_member.id')
            ->where('retur_member_detail.qty_refund', '>', 0);
        $applyDateFilter($hppreturQuery, 'kas_transaksi.tanggal');
        $hppretur = $hppreturQuery->sum('retur_member_detail.total_hpp');

        $hppSelisihTopupQuery = KasTransaksi::where('tipe', 'out')
            ->where('sumber_type', DompetSaldo::class)
            ->where('keterangan', 'Selisih Top-up')
            ->where($filterToko);
        $applyDateFilter($hppSelisihTopupQuery, 'tanggal');
        $hppSelisihTopup = $hppSelisihTopupQuery->sum('total_nominal');

        $hppReturSuplierQuery = ReturSupplierDetail::query()
            ->join('retur_supplier', 'retur_supplier.id', '=', 'retur_supplier_detail.retur_supplier_id')
            ->where('retur_supplier_detail.qty_refund', '>', 0);
        $applyTokoDirect($hppReturSuplierQuery, 'retur_supplier.toko_id');
        $applyDateFilterOnly($hppReturSuplierQuery, 'retur_supplier.verify_date');
        $hppReturSuplier = $hppReturSuplierQuery->selectRaw('SUM(retur_supplier_detail.qty_refund * retur_supplier_detail.hpp) as total')->value('total') ?? 0;

        $hppPenjualan = $hppTrx - $hppretur + $hppKoreksi;
        $total_hpp = $hppPenjualan + $hppReturSuplier;

        // ============================
        // BEBAN OPERASIONAL
        // ============================

        $pengeluaranQuery = KasTransaksi::where('tipe', 'out')
            ->where('sumber_type', Pengeluaran::class)
            ->where($filterToko)
            ->join('pengeluaran', 'kas_transaksi.sumber_id', '=', 'pengeluaran.id');
        $applyDateFilter($pengeluaranQuery, 'kas_transaksi.tanggal');

        $pengeluaran = $pengeluaranQuery->selectRaw('pengeluaran.pengeluaran_tipe_id, SUM(kas_transaksi.total_nominal) as total')
            ->groupBy('pengeluaran.pengeluaran_tipe_id')
            ->get()
            ->keyBy('pengeluaran_tipe_id');

        $bebanOperasional = [];
        $totalBeban = 0;
        $jenisList = PengeluaranTipe::where('id', '!=', 11)->get();

        foreach ($jenisList as $index => $jenis) {
            $nilai = isset($pengeluaran[$jenis->id]) ? (int) $pengeluaran[$jenis->id]->total : 0;
            $bebanOperasional[] = [
                'label' => '3.'.($index + 1).' '.$jenis->tipe,
                'value' => $nilai,
            ];
            $totalBeban += $nilai;
        }

        // ============================
        // STOK HILANG
        // ============================

        $stockBermasalahQuery = StockBarangBermasalah::query()
            ->join('stock_barang_batch as batch', 'batch.id', '=', 'stock_barang_bermasalah.stock_barang_batch_id');
        $applyTokoDirect($stockBermasalahQuery, 'batch.toko_id');
        $applyDateFilter($stockBermasalahQuery, 'stock_barang_bermasalah.created_at');
        $stockBermasalah = $stockBermasalahQuery->selectRaw('SUM(stock_barang_bermasalah.qty * batch.harga_beli) as total')->value('total') ?? 0;

        $nextNumber = count($jenisList) + 1;
        $bebanOperasional[] = [
            'label' => '3.'.$nextNumber.' Selisih Top-up Saldo Digital',
            'value' => $hppSelisihTopup,
        ];
        $totalBeban += $hppSelisihTopup;

        $nextNumber++;
        $bebanOperasional[] = [
            'label' => '3.'.$nextNumber.' Stok Barang Bermasalah',
            'value' => $stockBermasalah,
        ];
        $totalBeban += $stockBermasalah;

        $bebanOperasional[] = [
            'label' => 'Total Beban Operasional',
            'value' => $totalBeban,
        ];

        // ============================
        // KEPUTUSAN AKHIR LABA RUGI
        // ============================
        $total_labarugi = $totalPendapatan - $total_hpp - $totalBeban;

        if ($isNeraca) {
            return (int) $total_labarugi;
        }

        return $this->getDetailLaporan(
            (int) $penjualanUmum,
            (int) $pendapatanLainnya,
            (int) $hppReturSuplier,
            (int) $totalPendapatan,
            (int) $hppPenjualan,
            (int) $nilaiReturSuplier,
            (int) $total_hpp,
            $bebanOperasional,
            (int) $total_labarugi,
            (int) $pendapatanNonTransaksi
        );
    }

    protected function getDetailLaporan(
        $penjualanUmum,
        $pendapatanLainnya,
        $hppReturSuplier,
        $totalPendapatan,
        $hppPenjualan,
        $nilaiReturSuplier,
        $total_hpp,
        $bebanOperasional,
        $total_labarugi,
        $pendapatanNonTransaksi
    ) {
        return [
            [
                'I. Pendapatan',
                [
                    ['1.1 Pendapatan Umum', RupiahGenerate::build($penjualanUmum)],
                    ['1.2 Pendapatan Retur', RupiahGenerate::build($nilaiReturSuplier)],
                    ['1.3 Pendapatan Lainnya', RupiahGenerate::build($pendapatanLainnya)],
                    ['Total Pendapatan', RupiahGenerate::build($totalPendapatan)],
                ],
            ],
            [
                'II. HPP',
                [
                    ['2.1 HPP Pendapatan Umum', RupiahGenerate::build($hppPenjualan)],
                    ['2.2 HPP Pendapatan Retur', RupiahGenerate::build($hppReturSuplier)],
                    ['Total HPP', RupiahGenerate::build($total_hpp)],
                ],
            ],
            [
                'III. Biaya Pengeluaran',
                array_map(function ($item) {
                    return [$item['label'], RupiahGenerate::build($item['value'])];
                }, $bebanOperasional),
            ],
            [
                'IV. Laba Rugi',
                [
                    ['Laba Rugi Ditahan', RupiahGenerate::build($total_labarugi)],
                ],
            ],
        ];
    }

    protected function getTotalLabaRugi($total_labarugi)
    {
        return number_format((int) $total_labarugi, 0, ',', '.');
    }
}
