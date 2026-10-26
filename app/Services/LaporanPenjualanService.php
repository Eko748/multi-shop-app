<?php

namespace App\Services;

use App\Models\KasTransaksi;
use App\Models\PembelianBarangDetailAdjustment;
use App\Models\Pengeluaran;
use App\Models\ReturMember;
use App\Models\Toko;
use App\Models\TransaksiKasirDetail;
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

        // Filter Toko Helper
        $applyTokoFilter = function ($query, $column = 'transaksi_kasir.toko_id') use ($idToko) {
            if ($idToko && $idToko !== 'all' && $idToko != 0) {
                $query->where($column, $idToko);
            }
        };

        // 1. Query Gurgurtaa Fisik Granular (TransaksiKasirDetail)
        $query = TransaksiKasirDetail::select(
            'transaksi_kasir.toko_id',
            'toko.nama as nama_toko',
            'barang.jenis_barang_id as id_jenis_barang',
            'jenis_barang.nama_jenis_barang',
            DB::raw('COUNT(DISTINCT transaksi_kasir.id) as jml_trx'),
            DB::raw('SUM(transaksi_kasir_detail.qty) as item_qty'),
            DB::raw('SUM(transaksi_kasir_detail.subtotal) as nilai_trx_raw'),
            DB::raw('SUM(transaksi_kasir_detail.qty * stock_barang_batch.harga_beli) as nilai_hpp_raw'),
            DB::raw('SUM(CASE WHEN transaksi_kasir.member_id IS NOT NULL THEN transaksi_kasir_detail.subtotal ELSE 0 END) as member_nilai'),
            DB::raw('SUM(CASE WHEN transaksi_kasir.member_id IS NULL THEN transaksi_kasir_detail.subtotal ELSE 0 END) as non_member_nilai'),
            DB::raw('SUM(CASE WHEN transaksi_kasir.member_id IS NOT NULL THEN transaksi_kasir_detail.qty * stock_barang_batch.harga_beli ELSE 0 END) as member_hpp'),
            DB::raw('SUM(CASE WHEN transaksi_kasir.member_id IS NULL THEN transaksi_kasir_detail.qty * stock_barang_batch.harga_beli ELSE 0 END) as non_member_hpp'),
            DB::raw('COUNT(DISTINCT CASE WHEN transaksi_kasir.member_id IS NOT NULL THEN transaksi_kasir.id END) as member_trx'),
            DB::raw('COUNT(DISTINCT CASE WHEN transaksi_kasir.member_id IS NULL THEN transaksi_kasir.id END) as non_member_trx')
        )
            ->join('transaksi_kasir', 'transaksi_kasir.id', '=', 'transaksi_kasir_detail.transaksi_kasir_id')
            ->join('toko', 'toko.id', '=', 'transaksi_kasir.toko_id')
            ->join('stock_barang_batch', 'stock_barang_batch.id', '=', 'transaksi_kasir_detail.stock_barang_batch_id')
            ->join('stock_barang', 'stock_barang.id', '=', 'stock_barang_batch.stock_barang_id')
            ->join('barang', 'barang.id', '=', 'stock_barang.barang_id')
            ->join('jenis_barang', 'jenis_barang.id', '=', 'barang.jenis_barang_id')
            ->whereBetween('transaksi_kasir.tanggal', [$start, $end]);

        $applyTokoFilter($query, 'transaksi_kasir.toko_id');

        $query->groupBy(
            'transaksi_kasir.toko_id',
            'toko.nama',
            'barang.jenis_barang_id',
            'jenis_barang.nama_jenis_barang'
        );

        $data = $query->get();

        // 2. Query Retur Member Omzet per Toko & Jenis Barang
        $returMemberQuery = KasTransaksi::select(
            'kas.toko_id',
            'kas.jenis_barang_id',
            DB::raw('SUM(kas_transaksi.total_nominal) as total')
        )
            ->join('kas', 'kas.id', '=', 'kas_transaksi.kas_id')
            ->whereBetween('kas_transaksi.tanggal', [$start, $end])
            ->where('kas_transaksi.tipe', 'out')
            ->where('kas_transaksi.sumber_type', ReturMember::class);
        $applyTokoFilter($returMemberQuery, 'kas.toko_id');
        $returMemberMap = $returMemberQuery
            ->groupBy('kas.toko_id', 'kas.jenis_barang_id')
            ->get()
            ->keyBy(fn ($i) => $i->toko_id.'_'.($i->jenis_barang_id ?? 0));

        // 3. Query Retur Member HPP per Toko & Jenis Barang (Persis Laba Rugi)
        $hppReturQuery = KasTransaksi::select(
            'kas.toko_id',
            'kas.jenis_barang_id',
            DB::raw('SUM(retur_member_detail.total_hpp) as total_hpp_retur')
        )
            ->join('kas', 'kas.id', '=', 'kas_transaksi.kas_id')
            ->join('retur_member', 'retur_member.id', '=', 'kas_transaksi.sumber_id')
            ->join('retur_member_detail', 'retur_member_detail.retur_id', '=', 'retur_member.id')
            ->whereBetween('kas_transaksi.tanggal', [$start, $end])
            ->where('kas_transaksi.tipe', 'out')
            ->where('kas_transaksi.sumber_type', ReturMember::class)
            ->where('retur_member_detail.qty_refund', '>', 0);
        $applyTokoFilter($hppReturQuery, 'kas.toko_id');
        $hppReturMap = $hppReturQuery
            ->groupBy('kas.toko_id', 'kas.jenis_barang_id')
            ->get()
            ->keyBy(fn ($i) => $i->toko_id.'_'.($i->jenis_barang_id ?? 0));

        // 4. Query Pembatalan Transaksi Bulan Ini (Void Omzet)
        $pembatalanQuery = KasTransaksi::select(
            'kas.toko_id',
            'kas.jenis_barang_id',
            DB::raw('SUM(kas_transaksi.total_nominal) as total_void')
        )
            ->join('kas', 'kas.id', '=', 'kas_transaksi.kas_id')
            ->whereBetween('kas_transaksi.tanggal', [$start, $end])
            ->where('kas_transaksi.tipe', 'out')
            ->where('kas_transaksi.kategori', 'Pembatalan Transaksi');
        $applyTokoFilter($pembatalanQuery, 'kas.toko_id');
        $pembatalanMap = $pembatalanQuery
            ->groupBy('kas.toko_id', 'kas.jenis_barang_id')
            ->get()
            ->keyBy(fn ($i) => $i->toko_id.'_'.($i->jenis_barang_id ?? 0));

        // 5. Query HPP Pembatalan Transaksi Bulan Ini (Void HPP)
        $hppPembatalanQuery = KasTransaksi::select(
            'kas.toko_id',
            'kas.jenis_barang_id',
            DB::raw('SUM(kas_transaksi.total_nominal) as total_hpp_void')
        )
            ->join('kas', 'kas.id', '=', 'kas_transaksi.kas_id')
            ->whereBetween('kas_transaksi.tanggal', [$start, $end])
            ->where('kas_transaksi.kategori', 'HPP Pembatalan Transaksi');
        $applyTokoFilter($hppPembatalanQuery, 'kas.toko_id');
        $hppPembatalanMap = $hppPembatalanQuery
            ->groupBy('kas.toko_id', 'kas.jenis_barang_id')
            ->get()
            ->keyBy(fn ($i) => $i->toko_id.'_'.($i->jenis_barang_id ?? 0));

        // 6. Query Pengeluaran Tipe ID 15 (Memotong Omzet)
        $pengeluaran15Query = KasTransaksi::select(
            'kas.toko_id',
            'kas.jenis_barang_id',
            DB::raw('SUM(kas_transaksi.total_nominal) as total')
        )
            ->join('kas', 'kas.id', '=', 'kas_transaksi.kas_id')
            ->whereBetween('kas_transaksi.tanggal', [$start, $end])
            ->where('kas_transaksi.tipe', 'out')
            ->whereHasMorph('sumber', [Pengeluaran::class], function ($q) {
                $q->where('pengeluaran_tipe_id', 15)->whereNull('deleted_at');
            });
        $applyTokoFilter($pengeluaran15Query, 'kas.toko_id');
        $pengeluaran15Map = $pengeluaran15Query
            ->groupBy('kas.toko_id', 'kas.jenis_barang_id')
            ->get()
            ->keyBy(fn ($i) => $i->toko_id.'_'.($i->jenis_barang_id ?? 0));

        // 7. Query HPP Koreksi Adjustment per Toko
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

        foreach ($data as $row) {
            $jenisId = $row->id_jenis_barang ?? 0;
            $key = $row->toko_id.'_'.$jenisId;

            $voidOmzet = $pembatalanMap[$key]->total_void ?? 0;
            $voidHpp = $hppPembatalanMap[$key]->total_hpp_void ?? 0;

            // NILAI TRX & HPP murni dari transaksi kasir fisik dikurangi pembatalan (void) bulan berjalan
            $nilaiTrx = $row->nilai_trx_raw - $voidOmzet;
            $hpp = $row->nilai_hpp_raw - $voidHpp;
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
                'item_qty' => (float) $row->item_qty,
                'nilai_trx' => (float) $nilaiTrx,
                'nilai_hpp' => (float) $hpp,
                'laba' => (float) $laba,
            ];

            $member['jumlah'] += $row->member_trx;
            $member['nilai'] += $row->member_nilai;
            $member['hpp'] += $row->member_hpp;

            $nonMember['jumlah'] += $row->non_member_trx;
            $nonMember['nilai'] += ($row->non_member_nilai - $voidOmzet);
            $nonMember['hpp'] += ($row->non_member_hpp - $voidHpp);

            $total['jml_trx'] += $row->jml_trx;
            $total['nilai_trx'] += $nilaiTrx;
            $total['nilai_hpp'] += $hpp;
        }

        // Tambahkan HPP Koreksi ke total HPP Penjualan (sesuai Laba Rugi 2.1)
        $total['nilai_hpp'] += $hppKoreksi;

        $member['laba'] = $member['nilai'] - $member['hpp'];
        $nonMember['laba'] = $nonMember['nilai'] - $nonMember['hpp'];

        // 8. Penjualan Non Fisik (Dompet Digital)
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

        // Biaya retur di-set 0 karena sudah dipotong secara net di tiap jenis barang
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
