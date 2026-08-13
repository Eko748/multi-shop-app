<?php

namespace App\Services;

use App\Helpers\RupiahGenerate;
use App\Models\DompetSaldo;
use App\Models\Kas;
use App\Models\KasMutasi;
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
use App\Models\Toko;
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

        // 1. Query Laba Rugi Dasar
        $query = LabaRugi::where('tahun', $year)
            ->where('bulan', '<=', $month);

        if ($tokoId !== 'all') {
            $query->where('toko_id', $tokoId);
        }

        $data = $query
            ->pluck('laba_bersih', 'bulan')
            ->toArray();

        // 2. Cek apakah toko saat ini adalah Parent atau Child
        $isParent = false;
        $parentKasIds = [];

        if ($tokoId !== 'all') {
            $toko = Toko::find($tokoId);

            // Toko dianggap PARENT jika parent_id-nya null/kosong
            if ($toko && empty($toko->parent_id)) {
                $isParent = true;
                $parentKasIds = Kas::where('toko_id', $tokoId)->pluck('id')->toArray();
            }
        }

        // 3. Loop per bulan (Januari s/d Bulan Filter)
        for ($i = 1; $i <= $month; $i++) {
            $labaBersihBawaan = $data[$i] ?? 0;
            $nettoMutasi = 0;

            // PENYESUAIAN MUTASI HANYA BERLAKU UNTUK PARENT TOKO
            if ($isParent && ! empty($parentKasIds)) {
                // Ambil mutasi riil spesifik di bulan $i dan tahun $year
                $mutasiList = KasMutasi::with(['kasAsal', 'kasTujuan'])
                    ->whereYear('tanggal', $year)
                    ->whereMonth('tanggal', $i)
                    ->where(function ($q) use ($parentKasIds) {
                        $q->whereIn('kas_asal_id', $parentKasIds)
                            ->orWhereIn('kas_tujuan_id', $parentKasIds);
                    })
                    ->get();

                foreach ($mutasiList as $m) {
                    $tokoAsalId = $m->kasAsal?->toko_id;
                    $tokoTujuanId = $m->kasTujuan?->toko_id;

                    // Hanya hitung jika transaksi antar toko (Parent <-> Child)
                    if ($tokoAsalId != $tokoTujuanId) {
                        $isMasuk = in_array($m->kas_tujuan_id, $parentKasIds);
                        $isKeluar = in_array($m->kas_asal_id, $parentKasIds);

                        if ($isMasuk) {
                            $nettoMutasi += $m->nominal; // Tambah laba Parent saat terima dari Child
                        } elseif ($isKeluar) {
                            $nettoMutasi -= $m->nominal; // Kurangi laba Parent jika kirim ke Child
                        }
                    }
                }
            }

            // Hasil akhir: Child hanya $labaBersihBawaan, Parent $labaBersihBawaan + $nettoMutasi
            $results[$i] = (int) $labaBersihBawaan + (int) $nettoMutasi;
        }

        return $results;
    }

    public function hitungDetailLabaRugi($month, $year, $tokoId = 'all', $isNeraca = false)
    {
        // 1. Tentukan rentang waktu
        $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateTimeString();
        $endDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateTimeString();
        $endOfDateOnly = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');

        // Cek apakah Toko saat ini adalah Child atau Parent, serta ambil Singkatan Toko
        $isChild = false;
        $singkatanToko = '';

        if ($tokoId !== 'all' && $tokoId !== null && $tokoId != 0) {
            $tokoObj = Toko::find($tokoId);
            if ($tokoObj) {
                $isChild = ! empty($tokoObj->parent_id);
                $singkatanToko = $tokoObj->singkatan ?? '';
            }
        }

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

        // Helper Filter Tanggal
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
        // HPP
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

        // ABAIKAN ID 11, ID 12 (Bagi Hasil Mitra), DAN ID 13 (Bagi Hasil Pusat)
        $jenisList = PengeluaranTipe::whereNotIn('id', [11, 12, 13])->get();

        foreach ($jenisList as $index => $jenis) {
            $nilai = isset($pengeluaran[$jenis->id]) ? (int) $pengeluaran[$jenis->id]->total : 0;
            $bebanOperasional[] = [
                'label' => '3.'.($index + 1).' '.$jenis->tipe,
                'value' => $nilai,
            ];
            $totalBeban += $nilai;
        }

        // --- STOK HILANG & STOK MATI ---
        $stockHilangQuery = StockBarangBermasalah::query()
            ->join('stock_barang_batch as batch', 'batch.id', '=', 'stock_barang_bermasalah.stock_barang_batch_id')
            ->where('stock_barang_bermasalah.status', 'hilang');
        $applyTokoDirect($stockHilangQuery, 'batch.toko_id');
        $applyDateFilter($stockHilangQuery, 'stock_barang_bermasalah.created_at');
        $stockHilang = $stockHilangQuery->selectRaw('SUM(stock_barang_bermasalah.qty * batch.harga_beli) as total')->value('total') ?? 0;

        $stockMatiQuery = StockBarangBermasalah::query()
            ->join('stock_barang_batch as batch', 'batch.id', '=', 'stock_barang_bermasalah.stock_barang_batch_id')
            ->where('stock_barang_bermasalah.status', 'mati');
        $applyTokoDirect($stockMatiQuery, 'batch.toko_id');
        $applyDateFilter($stockMatiQuery, 'stock_barang_bermasalah.created_at');
        $stockMati = $stockMatiQuery->selectRaw('SUM(stock_barang_bermasalah.qty * batch.harga_beli) as total')->value('total') ?? 0;

        // Tambahkan Selisih Top-up
        $nextNumber = count($jenisList) + 1;
        $bebanOperasional[] = [
            'label' => '3.'.$nextNumber.' Selisih Top-up Saldo Digital',
            'value' => (int) $hppSelisihTopup,
        ];
        $totalBeban += $hppSelisihTopup;

        // Tambahkan Stok Hilang
        $nextNumber++;
        $bebanOperasional[] = [
            'label' => '3.'.$nextNumber.' Stok Hilang',
            'value' => (int) $stockHilang,
        ];
        $totalBeban += $stockHilang;

        // Tambahkan Stok Mati
        $nextNumber++;
        $bebanOperasional[] = [
            'label' => '3.'.$nextNumber.' Stok Mati/Rusak',
            'value' => (int) $stockMati,
        ];
        $totalBeban += $stockMati;

        $bebanOperasional[] = [
            'label' => 'Total Beban Operasional',
            'value' => (int) $totalBeban,
        ];

        // ============================
        // IV. Bagi Hasil/Deviden
        // ============================

        // 4.1 Bagi Hasil Pusat (Dari Pengeluaran Tipe ID 13)
        $bagiHasilTokoUtama = isset($pengeluaran[13]) ? (int) $pengeluaran[13]->total : 0;

        // 4.2 Bagi Hasil Owner / Mitra (Dari Pengeluaran Tipe ID 12)
        $bagiHasilOwner = isset($pengeluaran[12]) ? (int) $pengeluaran[12]->total : 0;

        // PENYESUAIAN STOK HILANG:
        // Menambah Bagi Hasil Pusat dan mengurangi Bagi Hasil Mitra
        if ($stockHilang > 0) {
            $bagiHasilTokoUtama += (int) $stockHilang;
            $bagiHasilOwner -= (int) $stockHilang;
        }

        // Total Dividen Bagi Hasil (Tetap sama secara akumulasi)
        $totalDividenBagiHasil = $bagiHasilTokoUtama + $bagiHasilOwner;

        // ============================
        // KEPUTUSAN AKHIR LABA RUGI DITAHAN
        // ============================
        $labaOperasional = $totalPendapatan - $total_hpp - $totalBeban;

        if ($isChild) {
            $total_labarugi = $labaOperasional - $totalDividenBagiHasil;
        } else {
            $total_labarugi = $labaOperasional + $totalDividenBagiHasil;
        }

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
            (int) $bagiHasilTokoUtama, // Menghilangkan abs()
            (int) $bagiHasilOwner,     // Menghilangkan abs() agar nilai minus tetap dipertahankan
            (int) $totalDividenBagiHasil, // Menghilangkan abs()
            (int) $total_labarugi,
            (int) $pendapatanNonTransaksi,
            $singkatanToko,
            $isChild
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
        $bagiHasilTokoUtama,
        $bagiHasilOwner,
        $totalDividenBagiHasil,
        $total_labarugi,
        $pendapatanNonTransaksi,
        $singkatanToko = '',
        $isChild = false
    ) {
        $laporan = [
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
        ];

        // KONDISI UNTUK TOKO CHILD (CABANG / MITRA)
        if ($isChild) {
            $labelMitra = '4.2 Bagi Hasil Mitra';
            if (! empty($singkatanToko)) {
                $labelMitra .= " {$singkatanToko}";
            }

            // Tampilkan Poin IV. Bagi Hasil/Deviden
            $laporan[] = [
                'IV. Bagi Hasil/Deviden',
                [
                    ['4.1 Bagi Hasil Pusat', RupiahGenerate::build($bagiHasilTokoUtama)],
                    [$labelMitra, RupiahGenerate::build($bagiHasilOwner)],
                    ['Total Bagi Hasil/Dividen', RupiahGenerate::build($totalDividenBagiHasil)],
                ],
            ];

            // Laba Rugi ada di Poin V.
            $laporan[] = [
                'V. Laba Rugi',
                [
                    ['Laba Rugi Ditahan', RupiahGenerate::build($total_labarugi)],
                ],
            ];
        } else {
            // KONDISI UNTUK TOKO PARENT (PUSAT / ALL TOKO)
            // Poin IV. Bagi Hasil/Deviden DILOMPATI/HIDE
            // Laba Rugi langsung menjadi Poin IV.
            $laporan[] = [
                'IV. Laba Rugi',
                [
                    ['Laba Rugi Ditahan', RupiahGenerate::build($total_labarugi)],
                ],
            ];
        }

        return $laporan;
    }

    protected function getTotalLabaRugi($total_labarugi)
    {
        return number_format((int) $total_labarugi, 0, ',', '.');
    }
}
