<?php

namespace App\Services;

use App\Models\DompetSaldo;
use App\Models\KasTransaksi;
use App\Models\Pengeluaran;
use App\Models\Toko;
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

        // 1. Query KasTransaksi untuk Penjualan Fisik & Non-Fisik per Toko dan Jenis Barang (dari kas.jenis_barang_id)
        $query = KasTransaksi::select(
            'kas.toko_id',
            'toko.nama as nama_toko',
            'kas.jenis_barang_id as id_jenis_barang',
            DB::raw("COALESCE(jenis_barang.nama_jenis_barang, 'Dompet Digital') as nama_jenis_barang"),
            DB::raw("COUNT(DISTINCT CASE WHEN kas_transaksi.sumber_type = 'App\\\\Models\\\\TransaksiKasirHarian' THEN kas_transaksi.sumber_id END) as jml_trx"),
            DB::raw("SUM(CASE WHEN kas_transaksi.tipe = 'in' THEN kas_transaksi.total_nominal ELSE 0 END) as nilai_trx_raw")
        )
            ->join('kas', 'kas.id', '=', 'kas_transaksi.kas_id')
            ->join('toko', 'toko.id', '=', 'kas.toko_id')
            ->leftJoin('jenis_barang', 'jenis_barang.id', '=', 'kas.jenis_barang_id')
            ->whereBetween('kas_transaksi.tanggal', [$start, $end])
            ->where('kas_transaksi.tipe', 'in')
            ->whereIn('kas_transaksi.sumber_type', [
                TransaksiKasirHarian::class,
                DompetSaldo::class,
            ]);

        if ($idToko && $idToko !== 'all' && $idToko != 0) {
            $query->where('kas.toko_id', $idToko);
        }

        $query->groupBy(
            'kas.toko_id',
            'toko.nama',
            'kas.jenis_barang_id',
            'jenis_barang.nama_jenis_barang'
        );

        $data = $query->get();

        // 2. Query Pengeluaran Tipe 15 (Memotong Penjualan Umum langsung)
        $pengeluaranTipe15Query = KasTransaksi::select(
            'kas.toko_id',
            'kas.jenis_barang_id',
            DB::raw('SUM(kas_transaksi.total_nominal) as total_potongan')
        )
            ->join('kas', 'kas.id', '=', 'kas_transaksi.kas_id')
            ->whereBetween('kas_transaksi.tanggal', [$start, $end])
            ->where('kas_transaksi.tipe', 'out')
            ->whereHasMorph('sumber', [Pengeluaran::class], function ($q) {
                $q->where('pengeluaran_tipe_id', 15)->whereNull('deleted_at');
            });

        if ($idToko && $idToko !== 'all' && $idToko != 0) {
            $pengeluaranTipe15Query->where('kas.toko_id', $idToko);
        }

        $pengeluaran15Grouped = $pengeluaranTipe15Query
            ->groupBy('kas.toko_id', 'kas.jenis_barang_id')
            ->get()
            ->keyBy(fn ($item) => $item->toko_id.'_'.($item->jenis_barang_id ?? 0));

        // 3. Query HPP Penjualan dari relasi TransaksiKasirHarian
        $hppQuery = KasTransaksi::select(
            'kas.toko_id',
            'kas.jenis_barang_id',
            DB::raw('SUM(transaksi_kasir_harian.total_harga_beli) as total_hpp')
        )
            ->join('kas', 'kas.id', '=', 'kas_transaksi.kas_id')
            ->join('transaksi_kasir_harian', 'kas_transaksi.sumber_id', '=', 'transaksi_kasir_harian.id')
            ->whereBetween('kas_transaksi.tanggal', [$start, $end])
            ->where('kas_transaksi.tipe', 'in')
            ->where('kas_transaksi.sumber_type', TransaksiKasirHarian::class);

        if ($idToko && $idToko !== 'all' && $idToko != 0) {
            $hppQuery->where('kas.toko_id', $idToko);
        }

        $hppGrouped = $hppQuery
            ->groupBy('kas.toko_id', 'kas.jenis_barang_id')
            ->get()
            ->keyBy(fn ($item) => $item->toko_id.'_'.($item->jenis_barang_id ?? 0));

        // 4. Inisialisasi Data Output & Summary
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

        foreach ($data as $row) {
            $jenisId = $row->id_jenis_barang ?? 0;
            $key = $row->toko_id.'_'.$jenisId;

            // Potong nilai_trx dengan Pengeluaran Tipe ID 15 jika ada
            $potongan15 = $pengeluaran15Grouped[$key]->total_potongan ?? 0;
            $nilaiTrx = max(0, $row->nilai_trx_raw - $potongan15);

            // Ambill HPP dari rekap TransaksiKasirHarian
            $hpp = $hppGrouped[$key]->total_hpp ?? 0;
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
                'item_qty' => 0,
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

        $member['laba'] = $member['nilai'] - $member['hpp'];
        $nonMember['laba'] = $nonMember['nilai'] - $nonMember['hpp'];

        // 5. BIAYA RETUR
        $biayaRetur = $this->repository->getBiayaRetur($startDate, $endDate, $idToko);
        $total['biaya_retur'] = $biayaRetur;

        $total['sub_laba'] = $total['nilai_trx'] - $total['nilai_hpp'] - $biayaRetur;

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
