<?php

namespace App\Services;

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
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $toko = Toko::find($idToko);

        $query = TransaksiKasirDetail::select(
            'transaksi_kasir.toko_id',
            'toko.nama as nama_toko',
            'jenis_barang.id as id_jenis_barang',
            'jenis_barang.nama_jenis_barang',
            DB::raw('COUNT(DISTINCT transaksi_kasir.id) as jml_trx'),
            DB::raw('SUM(transaksi_kasir_detail.qty) as item_qty'),
            DB::raw('SUM(transaksi_kasir_detail.subtotal) as nilai_trx'),
            DB::raw('SUM(transaksi_kasir_detail.qty * stock_barang.hpp_baru) as nilai_hpp'),
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

        if ($toko && $toko->parent_id !== null) {

            $query->where('transaksi_kasir.toko_id', $idToko);
            $query->groupBy(
                'transaksi_kasir.toko_id',
                'toko.nama',
                'jenis_barang.id',
                'jenis_barang.nama_jenis_barang'
            );
        } else {

            if ($idToko) {
                $query->where('transaksi_kasir.toko_id', $idToko);
            }

            $query->groupBy(
                'transaksi_kasir.toko_id',
                'toko.nama',
                'jenis_barang.id',
                'jenis_barang.nama_jenis_barang'
            );
        }

        $data = $query->get();

        $listToko = [];

        $member = ['jumlah' => 0, 'nilai' => 0, 'hpp' => 0, 'laba' => 0];
        $nonMember = ['jumlah' => 0, 'nilai' => 0, 'hpp' => 0, 'laba' => 0];

        $total = [
            'jml_trx' => 0,
            'nilai_trx' => 0,
            'nilai_hpp' => 0,
            'biaya_retur' => 0,
            'sub_laba' => 0
        ];

        foreach ($data as $row) {

            $hpp = $row->nilai_hpp;
            $laba = $row->nilai_trx - $hpp;

            if (!isset($listToko[$row->toko_id])) {

                $listToko[$row->toko_id] = [
                    'toko_id' => $row->toko_id,
                    'nama_toko' => $row->nama_toko,
                    'type_barang' => []
                ];
            }

            $listToko[$row->toko_id]['type_barang'][] = [
                'id_jenis_barang' => $row->id_jenis_barang,
                'nama' => $row->nama_jenis_barang,
                'jml_trx' => $row->jml_trx,
                'item_qty' => $row->item_qty,
                'nilai_trx' => $row->nilai_trx,
                'nilai_hpp' => $hpp,
                'laba' => $laba
            ];

            $member['jumlah'] += $row->member_trx;
            $member['nilai'] += $row->member_nilai;
            $member['hpp'] += $row->member_hpp;

            $nonMember['jumlah'] += $row->non_member_trx;
            $nonMember['nilai'] += $row->non_member_nilai;
            $nonMember['hpp'] += $row->non_member_hpp;

            $total['jml_trx'] += $row->jml_trx;
            $total['nilai_trx'] += $row->nilai_trx;
            $total['nilai_hpp'] += $hpp;
        }

        $member['laba'] = $member['nilai'] - $member['hpp'];
        $nonMember['laba'] = $nonMember['nilai'] - $nonMember['hpp'];

        /**
         * PENJUALAN NON FISIK
         */
        $pnf = $this->repository->getPenjualanNonFisik($startDate, $endDate, $idToko);

        $pnfGrouped = [];

        foreach ($pnf as $row) {

            $tokoId = $row->createdBy->toko_id ?? 0;
            $namaToko = $row->createdBy->toko->nama ?? 'Unknown';

            if (!isset($pnfGrouped[$tokoId])) {

                $pnfGrouped[$tokoId] = [
                    'toko_id' => $tokoId,
                    'nama_toko' => $namaToko,
                    'jml_trx' => 0,
                    'item_qty' => 0,
                    'nilai_trx' => 0,
                    'nilai_hpp' => 0,
                    'laba' => 0
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

            if (!isset($listToko[$pnfData['toko_id']])) {

                $listToko[$pnfData['toko_id']] = [
                    'toko_id' => $pnfData['toko_id'],
                    'nama_toko' => $pnfData['nama_toko'],
                    'type_barang' => []
                ];
            }

            $listToko[$pnfData['toko_id']]['type_barang'][] = [
                'id_jenis_barang' => 0,
                'nama' => 'Dompet Digital',
                'jml_trx' => $pnfData['jml_trx'],
                'item_qty' => $pnfData['item_qty'],
                'nilai_trx' => $pnfData['nilai_trx'],
                'nilai_hpp' => $pnfData['nilai_hpp'],
                'laba' => $laba
            ];

            /**
             * TAMBAH KE NON MEMBER
             */
            $nonMember['jumlah'] += $pnfData['jml_trx'];
            $nonMember['nilai'] += $pnfData['nilai_trx'];
            $nonMember['hpp'] += $pnfData['nilai_hpp'];
            $nonMember['laba'] += $pnfData['laba'];

            /**
             * TOTAL GLOBAL
             */
            $total['jml_trx'] += $pnfData['jml_trx'];
            $total['nilai_trx'] += $pnfData['nilai_trx'];
            $total['nilai_hpp'] += $pnfData['nilai_hpp'];
        }

        /**
         * BIAYA RETUR
         */
        $biayaRetur = $this->repository->getBiayaRetur($startDate, $endDate, $idToko);

        $total['biaya_retur'] = $biayaRetur;

        $total['sub_laba'] =
            $total['nilai_trx']
            - $total['nilai_hpp']
            - $biayaRetur;

        $laporanPenjualanPeriode = $startDate && $endDate
            ? Carbon::parse($startDate)->format('d-m-Y') . ' s/d ' . Carbon::parse($endDate)->format('d-m-Y')
            : 's/d';

        return [
            'laporan_penjualan_periode' => $laporanPenjualanPeriode,

            'periode' => [
                'tanggal_awal' => $startDate,
                'tanggal_akhir' => $endDate
            ],

            'list_toko' => array_values($listToko),

            'detail_laporan' => [
                'transaksi_member' => $member,
                'transaksi_non_member' => $nonMember
            ],

            'total' => $total
        ];
    }
}
