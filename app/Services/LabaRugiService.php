<?php

namespace App\Services;

use App\Models\DetailKasir;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\JenisPengeluaran;
use App\Models\PenjualanNonFisikDetail;
use App\Models\ReturMemberDetail;
use App\Models\ReturSupplierDetail;
use App\Models\StockBarangBermasalah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LabaRugiService
{
    public function hitungLabaRugi($month, $year)
    {
        return $this->hitungDetailLabaRugi($month, $year, true);
    }

    public function hitungLabaRugiRange($month, $year)
    {
        $results = [];

        // ============ PENJUALAN UMUM ============
        $kasir = DetailKasir::join('barang as b', 'b.id', '=', 'detail_kasir.id_barang')
            ->join('stock_barang as sb', 'sb.id_barang', '=', 'b.id')
            ->whereYear('detail_kasir.created_at', $year)
            ->whereMonth('detail_kasir.created_at', '<=', $month)
            ->whereNull('detail_kasir.deleted_at')
            ->whereNull('b.deleted_at')
            ->whereNull('sb.deleted_at')
            ->selectRaw('MONTH(detail_kasir.created_at) as bulan, SUM(detail_kasir.total_harga) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $penjualanNF = [];
        if (Schema::hasTable('td_penjualan_nonfisik_detail')) {
            $penjualanNF = PenjualanNonFisikDetail::whereYear('created_at', $year)
                ->whereMonth('created_at', '<=', $month)
                ->selectRaw('MONTH(created_at) as bulan, SUM(harga_jual * qty) as total')
                ->groupBy('bulan')
                ->pluck('total', 'bulan');
        }

        // ============ PENDAPATAN LAINNYA ============
        $pemasukan = Pemasukan::whereNotIn('id_jenis_pemasukan', [1, 2])
            ->whereYear('tanggal', $year)
            ->whereMonth('tanggal', '<=', $month)
            ->selectRaw('MONTH(tanggal) as bulan, SUM(nilai) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan')
            ->map(fn($v) => (float) $v);

        $keuntunganRefundSuplier = ReturSupplierDetail::where('qty_refund', '>', 0)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', '<=', $month)
            ->where('keterangan', 'untung')
            ->selectRaw('MONTH(created_at) as bulan, SUM(selisih) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan')
            ->map(fn($v) => (float) $v);

        $pendapatanLainnya = collect(range(1, $month))->mapWithKeys(function ($i) use ($pemasukan, $keuntunganRefundSuplier) {
            return [$i => ($pemasukan[$i] ?? 0) + ($keuntunganRefundSuplier[$i] ?? 0)];
        });

        // ============ RETUR MEMBER (Refund + Barang) ============
        $returMemberRefund = ReturMemberDetail::whereYear('created_at', $year)
            ->whereMonth('created_at', '<=', $month)
            ->where('qty_refund', '>', 0)
            ->selectRaw('MONTH(created_at) as bulan, SUM(total_refund) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        // $returMemberBarang = ReturMemberDetail::whereYear('created_at', $year)
        //     ->whereMonth('created_at', '<=', $month)
        //     ->where('qty_barang', '>', 0)
        //     ->selectRaw('MONTH(created_at) as bulan, SUM(total_hpp_barang) as total')
        //     ->groupBy('bulan')
        //     ->pluck('total', 'bulan');

        // Total retur = nilai negatif (karena pengurang pendapatan)
        $nilaiReturMember = $returMemberRefund->map(function ($value, $key) {
            return -abs($value + ($returMemberBarang[$key] ?? 0));
        });

        // ============ HPP PENJUALAN ============
        $hppKasir = DB::table('detail_kasir as dk')
            ->join('kasir as k', 'dk.id_kasir', '=', 'k.id')
            ->join('detail_pembelian_barang as dpb', 'dk.id_detail_pembelian', '=', 'dpb.id')
            ->join('pembelian_barang as pb', 'dpb.id_pembelian_barang', '=', 'pb.id')
            ->join('barang as b', 'dpb.id_barang', '=', 'b.id')
            ->join('stock_barang as sb', 'sb.id_barang', '=', 'b.id')
            ->whereYear('dk.created_at', $year)
            ->whereMonth('dk.created_at', '<=', $month)
            ->whereNull('dk.deleted_at')
            ->whereNull('k.deleted_at')
            ->whereNull('dpb.deleted_at')
            ->whereNull('pb.deleted_at')
            ->whereNull('b.deleted_at')
            ->whereNull('sb.deleted_at')
            ->selectRaw('MONTH(dk.created_at) as bulan, SUM(dk.qty * dpb.harga_barang) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $hppPenjualanNF = PenjualanNonFisikDetail::whereYear('created_at', $year)
            ->whereMonth('created_at', '<=', $month)
            ->selectRaw('MONTH(created_at) as bulan, SUM(hpp * qty) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        // ============ HPP RETUR ============
        $hppRetur = ReturMemberDetail::whereYear('created_at', $year)
            ->whereMonth('created_at', '<=', $month)
            ->selectRaw('MONTH(created_at) as bulan, SUM(total_hpp) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan')
            ->map(fn($v) => -abs($v)); // nilai negatif (pengurang HPP total)

        // ============ BEBAN OPERASIONAL ============
        $pengeluaran = Pengeluaran::whereYear('tanggal', $year)
            ->whereMonth('tanggal', '<=', $month)
            ->selectRaw('MONTH(tanggal) as bulan, SUM(nilai) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $hppReturBarang = ReturMemberDetail::whereYear('created_at', $year)
            ->whereMonth('created_at', '<=', $month)
            ->where('qty_barang', '>', 0)
            ->selectRaw('MONTH(created_at) as bulan, SUM(total_hpp_barang) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $stockBermasalah = StockBarangBermasalah::where('status', 'hilang')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', '<=', $month)
            ->selectRaw('MONTH(created_at) as bulan, SUM(total_hpp) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan')
            ->map(fn($v) => (float) $v); // pastikan semua angka float

        $kerugianRefundSuplier = ReturSupplierDetail::where('qty_refund', '>', 0)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', '<=', $month)
            ->where('keterangan', 'rugi')
            ->selectRaw('MONTH(created_at) as bulan, SUM(selisih) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan')
            ->map(fn($v) => (float) $v);

        // ======= HITUNG PER BULAN =======
        for ($i = 1; $i <= $month; $i++) {
            // 1. Penjualan
            $penjualan = ($kasir[$i] ?? 0) + ($penjualanNF[$i] ?? 0);

            // 2. Pendapatan total (sama seperti hitungDetail)
            $pendapatan = $penjualan + ($pendapatanLainnya[$i] ?? 0) + ($nilaiReturMember[$i] ?? 0);

            // 3. HPP total
            $hpp = ($hppKasir[$i] ?? 0) + ($hppPenjualanNF[$i] ?? 0) + ($hppRetur[$i] ?? 0) + ($hppReturBarang[$i] ?? 0);

            // 4. Beban total
            $beban = ($pengeluaran[$i] ?? 0) + ($stockBermasalah[$i] ?? 0) + ($kerugianRefundSuplier[$i] ?? 0);

            // 5. Laba/Rugi
            $results[$i] = $pendapatan - $hpp - $beban;
        }

        return $results;
    }

    public function hitungDetailLabaRugi($month, $year, $isNeraca = false)
    {
        // Penjualan Umum
        $kasir = DetailKasir::join('barang as b', 'b.id', '=', 'detail_kasir.id_barang')
            ->join('stock_barang as sb', 'sb.id_barang', '=', 'b.id')
            ->whereMonth('detail_kasir.created_at', $month)
            ->whereYear('detail_kasir.created_at', $year)
            ->whereNull('detail_kasir.deleted_at')
            ->whereNull('b.deleted_at')
            ->whereNull('sb.deleted_at')
            ->sum('detail_kasir.total_harga');

        if (Schema::hasTable('td_penjualan_nonfisik_detail')) {
            $penjualanNF = PenjualanNonFisikDetail::whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->sum(DB::raw('harga_jual * qty'));
        } else {
            $penjualanNF = 0;
        }

        // Pendapatan Lainnya
        $pemasukan = Pemasukan::whereNotIn('id_jenis_pemasukan', [1, 2])
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->sum('nilai');

        $keuntunganRefundSuplier = ReturSupplierDetail::where('qty_refund', '>', 0)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->where('keterangan', 'untung')->sum('selisih');

        $kerugianRefundSuplier = ReturSupplierDetail::where('qty_refund', '>', 0)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->where('keterangan', 'rugi')->sum('selisih');

        $pendapatanLainnya = $pemasukan + $keuntunganRefundSuplier - $kerugianRefundSuplier;

        // Asset Retur
        $returMemberRefund = ReturMemberDetail::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->where('qty_refund', '>', 0)
            ->sum('total_refund');

        // $returMemberBarang = ReturMemberDetail::whereMonth('created_at', $month)
        //     ->whereYear('created_at', $year)
        //     ->where('qty_barang', '>', 0)
        //     ->sum('total_hpp_barang');

        $penjualanUmum = $kasir + $penjualanNF;

        $nilaiReturMember = -abs($returMemberRefund);

        $totalPendapatan = $penjualanUmum + $pendapatanLainnya + $nilaiReturMember;

        // HPP Penjualan
        $hppKasir = DB::table('detail_kasir as dk')
            ->join('kasir as k', 'dk.id_kasir', '=', 'k.id')
            ->join('detail_pembelian_barang as dpb', 'dk.id_detail_pembelian', '=', 'dpb.id')
            ->join('pembelian_barang as pb', 'dpb.id_pembelian_barang', '=', 'pb.id')
            ->join('barang as b', 'dpb.id_barang', '=', 'b.id')
            ->join('stock_barang as sb', 'sb.id_barang', '=', 'b.id') // filter barang valid
            ->whereMonth('dk.created_at', $month)
            ->whereYear('dk.created_at', $year)
            ->whereNull('dk.deleted_at')
            ->whereNull('k.deleted_at')
            ->whereNull('dpb.deleted_at')
            ->whereNull('pb.deleted_at')
            ->whereNull('b.deleted_at')
            ->whereNull('sb.deleted_at')
            ->sum(DB::raw('dk.qty * dpb.harga_barang')) ?? 0;

        $hppPenjualanNF = PenjualanNonFisikDetail::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->sum(DB::raw('hpp * qty'));

        $hppPenjualan = $hppKasir + $hppPenjualanNF;

        // HPP Retur
        $hppretur = ReturMemberDetail::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->sum('total_hpp');

        $hppretur = -abs($hppretur);

        $hppBarangGanti = ReturMemberDetail::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->where('qty_barang', '>', 0)
            ->sum('total_hpp_barang');

        $hppBarangGanti = $hppBarangGanti;

        $total_hpp = $hppPenjualan + $hppretur + $hppBarangGanti;

        // Beban Operasional
        $jenisPengeluaran = JenisPengeluaran::where('id', '!=', 11)->get();
        $totalBeban = 0;
        $bebanOperasional = [];

        foreach ($jenisPengeluaran as $index => $jenis) {
            $totalNilai = Pengeluaran::where('id_jenis_pengeluaran', $jenis->id)
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', $year)
                ->sum('nilai');

            $totalBeban += $totalNilai;
            $bebanOperasional[] = [
                'label' => '3.' . ($index + 1) . ' ' . $jenis->nama_jenis,
                'value' => $totalNilai
            ];
        }

        // Stock bermasalah
        $stockBermasalah = StockBarangBermasalah::where('status', 'hilang')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->sum('total_hpp');

        // $kerugianRefundSuplier = ReturSupplierDetail::where('qty_refund', '>', 0)
        //     ->whereMonth('created_at', $month)
        //     ->whereYear('created_at', $year)
        //     ->where('keterangan', 'rugi')->sum('selisih');

        // $bebanOperasional[] = ['label' => '3.11 Kerugian Retur ke Suplier', 'value' => $kerugianRefundSuplier];
        $bebanOperasional[] = ['label' => '3.11 Stok Barang Hilang', 'value' => $stockBermasalah];

        $totalBeban += $stockBermasalah;

        // $bebanOperasional[] = ['label' => '3.11 Biaya Retur Ganti Barang', 'value' => $biayaRetur];
        $bebanOperasional[] = ['label' => 'Total Beban Operasional', 'value' => $totalBeban];

        $total_labarugi = $totalPendapatan + -abs($total_hpp) - $totalBeban;

        if ($isNeraca) {
            return (float) $total_labarugi;
        }

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
            $total_labarugi
        );
    }

    protected function getDetailLaporan($penjualanUmum, $pendapatanLainnya, $assetRetur, $totalPendapatan, $hppPenjualan, $hppretur, $hppBarangGanti, $total_hpp, $bebanOperasional, $total_labarugi)
    {
        return [
            [
                'I. Pendapatan',
                [
                    ['1.1 Penjualan Umum', number_format($penjualanUmum, 0, ',', '.')],
                    ['1.2 Pendapatan Lainnya', number_format($pendapatanLainnya, 0, ',', '.')],
                    ['1.3 Pengembalian Retur', number_format($assetRetur, 0, ',', '.')],
                    ['Total Pendapatan', number_format($totalPendapatan, 0, ',', '.')]
                ]
            ],
            [
                'II. HPP',
                [
                    ['2.1 HPP Penjualan', number_format((float) $hppPenjualan, 0, ',', '.')],
                    ['2.2 HPP Retur', number_format((float) $hppretur, 0, ',', '.')],
                    ['2.3 HPP Barang Ganti', number_format((float) $hppBarangGanti, 0, ',', '.')],
                    ['Total HPP', number_format((float) $total_hpp, 0, ',', '.')]
                ]
            ],
            [
                'III. Biaya Pengeluaran',
                array_map(function ($item) {
                    return [$item['label'], number_format($item['value'], 0, ',', '.')];
                }, $bebanOperasional)
            ],
            [
                'IV. Laba Rugi',
                [
                    ['Laba Rugi Ditahan', number_format((float) $total_labarugi, 0, ',', '.')]
                ]
            ],
        ];
    }

    protected function getTotalLabaRugi($total_labarugi)
    {
        return number_format((float) $total_labarugi, 0, ',', '.');
    }
}
