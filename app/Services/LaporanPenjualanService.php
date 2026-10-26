<?php

namespace App\Services;

use App\Models\KasTransaksi;
use App\Models\PembelianBarangDetailAdjustment;
use App\Models\Pengeluaran;
use App\Models\ReturMember;
use App\Models\TransaksiKasirHarian;
use App\Repositories\LaporanPenjualanRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LaporanPenjualanService
{
    protected $repository;

    public function __construct(LaporanPenjualanRepository $repository)
    {
        $this->repository = $repository;
    }

    public function generateReport($startDate, $endDate, $idToko = null)
    {
        $start = Carbon::parse($startDate)->startOfDay()->toDateTimeString();
        $end = Carbon::parse($endDate)->endOfDay()->toDateTimeString();

        // 1. Query Utama dari KasTransaksi & TransaksiKasirHarian (Mengambil Nilai Omzet, HPP, JML TRX, & JML QTY)
        $queryOmzet = KasTransaksi::select(
            'kas.toko_id',
            'toko.nama as nama_toko',
            'kas.jenis_barang_id as id_jenis_barang',
            DB::raw("COALESCE(jenis_barang.nama_jenis_barang, 'Dompet Digital') as nama_jenis_barang"),
            DB::raw('SUM(transaksi_kasir_harian.total_transaksi) as jml_trx'),
            DB::raw('SUM(transaksi_kasir_harian.total_qty) as item_qty'),
            DB::raw('SUM(kas_transaksi.total_nominal) as omzet_raw')
        )
            ->join('kas', 'kas.id', '=', 'kas_transaksi.kas_id')
            ->join('toko', 'toko.id', '=', 'kas.toko_id')
            ->join('transaksi_kasir_harian', 'transaksi_kasir_harian.id', '=', 'kas_transaksi.sumber_id')
            ->leftJoin('jenis_barang', 'jenis_barang.id', '=', 'kas.jenis_barang_id')
            ->whereBetween('kas_transaksi.tanggal', [$start, $end])
            ->where('kas_transaksi.tipe', 'in')
            ->where('kas_transaksi.sumber_type', TransaksiKasirHarian::class);

        if ($idToko && $idToko !== 'all' && $idToko != 0) {
            $queryOmzet->where('kas.toko_id', $idToko);
        }

        $dataOmzet = $queryOmzet->groupBy(
            'kas.toko_id',
            'toko.nama',
            'kas.jenis_barang_id',
            'jenis_barang.nama_jenis_barang'
        )->get();

        // 2. Query Pengurang Omzet (Retur Member, Pembatalan Transaksi Bulan Ini, Pengeluaran Tipe 15)
        $queryPengurangOmzet = KasTransaksi::select(
            'kas.toko_id',
            'kas.jenis_barang_id',
            DB::raw('SUM(kas_transaksi.total_nominal) as total_pengurang')
        )
            ->join('kas', 'kas.id', '=', 'kas_transaksi.kas_id')
            ->whereBetween('kas_transaksi.tanggal', [$start, $end])
            ->where('kas_transaksi.tipe', 'out')
            ->where(function ($q) {
                $q->where('kas_transaksi.sumber_type', ReturMember::class)
                    ->orWhere('kas_transaksi.kategori', 'Pembatalan Transaksi')
                    ->orWhereHasMorph('sumber', [Pengeluaran::class], function ($subQ) {
                        $subQ->where('pengeluaran_tipe_id', 15)->whereNull('deleted_at');
                    });
            });

        if ($idToko && $idToko !== 'all' && $idToko != 0) {
            $queryPengurangOmzet->where('kas.toko_id', $idToko);
        }

        $pengurangOmzetMap = $queryPengurangOmzet
            ->groupBy('kas.toko_id', 'kas.jenis_barang_id')
            ->get()
            ->keyBy(fn ($i) => $i->toko_id.'_'.($i->jenis_barang_id ?? 0));

        // 3. Query HPP Raw dari TransaksiKasirHarian
        $queryHpp = KasTransaksi::select(
            'kas.toko_id',
            'kas.jenis_barang_id',
            DB::raw('SUM(transaksi_kasir_harian.total_harga_beli) as hpp_raw')
        )
            ->join('kas', 'kas.id', '=', 'kas_transaksi.kas_id')
            ->join('transaksi_kasir_harian', 'kas_transaksi.sumber_id', '=', 'transaksi_kasir_harian.id')
            ->whereBetween('kas_transaksi.tanggal', [$start, $end])
            ->where('kas_transaksi.tipe', 'in')
            ->where('kas_transaksi.sumber_type', TransaksiKasirHarian::class);

        if ($idToko && $idToko !== 'all' && $idToko != 0) {
            $queryHpp->where('kas.toko_id', $idToko);
        }

        $hppMap = $queryHpp
            ->groupBy('kas.toko_id', 'kas.jenis_barang_id')
            ->get()
            ->keyBy(fn ($i) => $i->toko_id.'_'.($i->jenis_barang_id ?? 0));

        // 4. Query Pengurang HPP (HPP Retur Member & HPP Pembatalan Transaksi)
        $queryPengurangHpp = KasTransaksi::select(
            'kas.toko_id',
            'kas.jenis_barang_id',
            DB::raw('SUM(CASE
            WHEN kas_transaksi.sumber_type = "App\\\\Models\\\\ReturMember" THEN retur_member_detail.total_hpp
            WHEN kas_transaksi.kategori = "HPP Pembatalan Transaksi" THEN kas_transaksi.total_nominal
            ELSE 0 END) as total_pengurang_hpp')
        )
            ->join('kas', 'kas.id', '=', 'kas_transaksi.kas_id')
            ->leftJoin('retur_member', 'retur_member.id', '=', 'kas_transaksi.sumber_id')
            ->leftJoin('retur_member_detail', 'retur_member_detail.retur_id', '=', 'retur_member.id')
            ->whereBetween('kas_transaksi.tanggal', [$start, $end])
            ->where(function ($q) {
                $q->where('kas_transaksi.sumber_type', ReturMember::class)
                    ->orWhere('kas_transaksi.kategori', 'HPP Pembatalan Transaksi');
            });

        if ($idToko && $idToko !== 'all' && $idToko != 0) {
            $queryPengurangHpp->where('kas.toko_id', $idToko);
        }

        $pengurangHppMap = $queryPengurangHpp
            ->groupBy('kas.toko_id', 'kas.jenis_barang_id')
            ->get()
            ->keyBy(fn ($i) => $i->toko_id.'_'.($i->jenis_barang_id ?? 0));

        // 5. Query HPP Koreksi Adjustment
        $hppKoreksiQuery = PembelianBarangDetailAdjustment::query();
        if ($idToko && $idToko !== 'all' && $idToko != 0) {
            $hppKoreksiQuery->where('toko_id', $idToko);
        }
        $hppKoreksiQuery->whereBetween('created_at', [$start, $end]);
        $hppKoreksi = $hppKoreksiQuery->sum('nominal_laba_rugi');

        $listToko = [];
        $member = ['jumlah' => 0, 'nilai' => 0, 'hpp' => 0, 'laba' => 0];
        $nonMember = ['jumlah' => 0, 'nilai' => 0, 'hpp' => 0, 'laba' => 0];

        $total = [
            'jml_trx' => 0,
            'nilai_trx' => 0,
            'nilai_hpp' => 0,
            'biaya_retur' => 0,
            'sub_laba' => 0,
        ];

        foreach ($dataOmzet as $row) {
            $jenisId = $row->id_jenis_barang ?? 0;
            $key = $row->toko_id.'_'.$jenisId;

            $pengurangOmzet = $pengurangOmzetMap[$key]->total_pengurang ?? 0;
            $hppRaw = $hppMap[$key]->hpp_raw ?? 0;
            $pengurangHpp = $pengurangHppMap[$key]->total_pengurang_hpp ?? 0;

            $nilaiTrx = $row->omzet_raw - $pengurangOmzet;
            $hpp = $hppRaw - $pengurangHpp;
            $laba = $nilaiTrx - $hpp;

            if (! isset($listToko[$row->toko_id])) {
                $listToko[$row->toko_id] = [
                    'toko_id' => $row->toko_id,
                    'nama_toko' => $row->nama_toko,
                    'type_barang' => [],
                ];
            }

            $listToko[$row->toko_id]['type_barang'][] = [
                'id_jenis_barang' => $jenisId,
                'nama' => $row->nama_jenis_barang,
                'jml_trx' => (int) $row->jml_trx,
                'item_qty' => (float) $row->item_qty, // Mengambil total_qty dari transaksi_kasir_harian
                'nilai_trx' => (float) $nilaiTrx,
                'nilai_hpp' => (float) $hpp,
                'laba' => (float) $laba,
            ];

            $nonMember['jumlah'] += $row->jml_trx;
            $nonMember['nilai'] += $nilaiTrx;
            $nonMember['hpp'] += $hpp;

            $total['jml_trx'] += $row->jml_trx;
            $total['nilai_trx'] += $nilaiTrx;
            $total['nilai_hpp'] += $hpp;
        }

        // Tambahkan HPP Koreksi ke Total HPP (Persis Laba Rugi 2.1)
        $total['nilai_hpp'] += $hppKoreksi;

        $nonMember['laba'] = $nonMember['nilai'] - $nonMember['hpp'];

        // 6. Penjualan Non Fisik (Dompet Digital)
        $pnf = $this->repository->getPenjualanNonFisik($startDate, $endDate, $idToko);
        $pnfGrouped = [];

        foreach ($pnf as $row) {
            $tokoId = $row->createdBy->toko_id ?? 0;
            $namaToko = $row->createdBy->toko->nama ?? 'Unknown';

            if (! isset($pnfGrouped[$tokoId])) {
                $pnfGrouped[$tokoId] = [
                    'toko_id' => $tokoId,
                    'nama_toko' => $namaToko,
                    'jml_trx' => 0,
                    'item_qty' => 0,
                    'nilai_trx' => 0,
                    'nilai_hpp' => 0,
                    'laba' => 0,
                ];
            }

            $pnfGrouped[$tokoId]['jml_trx'] += 1;
            $pnfGrouped[$tokoId]['item_qty'] += $row->detail_sum_qty ?? 0;
            $pnfGrouped[$tokoId]['nilai_trx'] += $row->total_harga_jual ?? 0;
            $pnfGrouped[$tokoId]['nilai_hpp'] += $row->total_hpp ?? 0;
            $pnfGrouped[$tokoId]['laba'] += ($row->total_harga_jual - $row->total_hpp) ?? 0;
        }

        foreach ($pnfGrouped as $pnfData) {
            $laba = $pnfData['nilai_trx'] - $pnfData['nilai_hpp'];

            if (! isset($listToko[$pnfData['toko_id']])) {
                $listToko[$pnfData['toko_id']] = [
                    'toko_id' => $pnfData['toko_id'],
                    'nama_toko' => $pnfData['nama_toko'],
                    'type_barang' => [],
                ];
            }

            $listToko[$pnfData['toko_id']]['type_barang'][] = [
                'id_jenis_barang' => 0,
                'nama' => 'Dompet Digital',
                'jml_trx' => $pnfData['jml_trx'],
                'item_qty' => $pnfData['item_qty'],
                'nilai_trx' => $pnfData['nilai_trx'],
                'nilai_hpp' => $pnfData['nilai_hpp'],
                'laba' => $laba,
            ];

            $nonMember['jumlah'] += $pnfData['jml_trx'];
            $nonMember['nilai'] += $pnfData['nilai_trx'];
            $nonMember['hpp'] += $pnfData['nilai_hpp'];
            $nonMember['laba'] += $pnfData['laba'];

            $total['jml_trx'] += $pnfData['jml_trx'];
            $total['nilai_trx'] += $pnfData['nilai_trx'];
            $total['nilai_hpp'] += $pnfData['nilai_hpp'];
        }

        $total['biaya_retur'] = 0;
        $total['sub_laba'] = $total['nilai_trx'] - $total['nilai_hpp'];

        $laporanPenjualanPeriode = $startDate && $endDate
            ? Carbon::parse($startDate)->format('d-m-Y').' s/d '.Carbon::parse($endDate)->format('d-m-Y')
            : 's/d';

        return [
            'laporan_penjualan_periode' => $laporanPenjualanPeriode,
            'periode' => [
                'tanggal_awal' => $startDate,
                'tanggal_akhir' => $endDate,
            ],
            'list_toko' => array_values($listToko),
            'detail_laporan' => [
                'transaksi_member' => $member,
                'transaksi_non_member' => $nonMember,
            ],
            'total' => $total,
        ];
    }
}
