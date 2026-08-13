<?php

namespace App\Http\Controllers\Api;

use App\Helpers\TextGenerate;
use App\Http\Controllers\Controller;
use App\Models\KasTransaksi;
use App\Models\Member;
use App\Models\PenjualanNonFisik;
use App\Models\ReturMemberDetail;
use App\Models\ReturSupplierDetail;
use App\Models\Toko;
use App\Models\TransaksiKasirDetail;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpenAPIController extends Controller
{
    use ApiResponse;

    /**
     * 1. GET LAPORAN TOKO (Laporan Omset, Summary, & Time-Series per Toko)
     * Menggabungkan logic omset kasir, PNF, retur member/supplier dari DashboardController
     */
    public function getLaporanToko(Request $request)
    {
        $startDateInput = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDateInput   = $request->input('end_date', now()->endOfMonth()->toDateString());
        $period         = $request->input('period', 'monthly'); // 'daily', 'monthly', 'yearly'
        $idToko         = $request->input('toko_id', 'all');

        $startDate = Carbon::parse($startDateInput)->startOfDay();
        $endDate   = Carbon::parse($endDateInput)->endOfDay();

        try {
            // --- A. FILTER TOKO (INDUK + CHILD) ---
            $queryToko = Toko::select('id', 'singkatan', 'nama', 'parent_id');
            if ($idToko !== 'all') {
                $queryToko->where(function ($q) use ($idToko) {
                    $q->where('id', $idToko)->orWhere('parent_id', $idToko);
                });
            }
            $tokos = $queryToko->get();
            $allTokoIds = $tokos->pluck('id')->toArray();

            // Ambil daftar ID Toko yang bertindak sebagai Parent
            $parentIdsList = Toko::whereIn('id', $allTokoIds)
                ->whereHas('children')
                ->pluck('id')
                ->toArray();

            // --- B. QUERY OMSET KASIR (KasTransaksi Pendapatan Umum) ---
            $kasData = KasTransaksi::with('kas')
                ->whereHas('kas', fn ($sub) => $sub->whereIn('toko_id', $allTokoIds))
                ->where('tipe', 'in')
                ->where('kategori', 'Pendapatan Umum')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get()
                ->groupBy(fn ($item) => $item->kas->toko_id ?? null);

            // --- C. QUERY OMSET PENJUALAN NON-FISIK (PNF) ---
            $pnfData = PenjualanNonFisik::whereBetween('created_at', [$startDate, $endDate])
                ->whereHas('createdBy', fn ($sub) => $sub->whereIn('toko_id', $allTokoIds))
                ->selectRaw('created_by, total_harga_jual, created_at')
                ->with('createdBy:id,toko_id')
                ->get()
                ->groupBy(fn ($item) => $item->createdBy->toko_id ?? null);

            // --- D. QUERY RETUR MEMBER & SUPPLIER PER TOKO ---
            $pengurangPerToko = $this->getKomponenPengurang($startDate, $endDate, 'toko');

            $year  = $startDate->year;
            $month = $startDate->month;

            $storesList = [];
            $summary = [
                'grand_total'       => 0,
                'total_omset_kasir' => 0,
                'total_omset_pnf'   => 0,
                'total_biaya_retur' => 0,
                'total_kasbon'      => 0,
                'total_transaksi'   => 0,
            ];

            // --- E. KALKULASI UTAMA PER TOKO ---
            foreach ($tokos as $toko) {
                $tokoId   = $toko->id;
                $isParent = is_null($toko->parent_id) || in_array($tokoId, $parentIdsList);

                // 1. Transaksi Kasir
                $txKasirToko    = $kasData->get($tokoId, collect());
                $omsetKasirToko = (float) $txKasirToko->sum('total_nominal');
                $jumlahTxKasir  = $txKasirToko->count();

                // 2. Transaksi PNF
                $txPnfToko    = $pnfData->get($tokoId, collect());
                $omsetPnfToko = (float) $txPnfToko->sum('total_harga_jual');

                // 3. Retur & Kasbon
                $refund     = $pengurangPerToko['refund'][$tokoId] ?? 0;
                $keuntungan = $pengurangPerToko['untung'][$tokoId] ?? 0;
                $kerugian   = $pengurangPerToko['rugi'][$tokoId] ?? 0;
                $biayaRetur = $refund - $keuntungan + $kerugian;
                $kasbon     = 0; // Kasbon default

                // 4. Total Omset Bersih (Logika dari getOmset)
                $totalOmsetGross  = $omsetKasirToko + $omsetPnfToko;
                $totalOmsetBersih = max($totalOmsetGross - $biayaRetur - $kasbon, 0);

                // 5. Time Series untuk Grafik
                $timeSeriesData = [];

                if ($period === 'daily') {
                    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                    $dailyTotals = array_fill(1, $daysInMonth, 0);

                    foreach ($txKasirToko as $data) {
                        $day = (int) Carbon::parse($data->created_at)->format('j');
                        $dailyTotals[$day] += $data->total_nominal;
                    }
                    foreach ($txPnfToko as $data) {
                        $day = (int) Carbon::parse($data->created_at)->format('j');
                        $dailyTotals[$day] += $data->total_harga_jual;
                    }

                    // Pengurangan Retur Proporsional Harian
                    foreach (range(1, $daysInMonth) as $day) {
                        $dailyTotals[$day] -= (($biayaRetur + $kasbon) / $daysInMonth);
                        $dailyTotals[$day] = max($dailyTotals[$day], 0);
                    }

                    $timeSeriesData = array_map(fn ($v) => round($v, 2), array_values($dailyTotals));

                } elseif ($period === 'monthly') {
                    $monthlyTotals = array_fill(1, 12, 0);

                    foreach ($txKasirToko as $data) {
                        $b = (int) Carbon::parse($data->created_at)->format('n');
                        $monthlyTotals[$b] += $data->total_nominal;
                    }
                    foreach ($txPnfToko as $data) {
                        $b = (int) Carbon::parse($data->created_at)->format('n');
                        $monthlyTotals[$b] += $data->total_harga_jual;
                    }

                    $monthlyTotals[$month] -= ($biayaRetur + $kasbon);
                    $monthlyTotals[$month] = max($monthlyTotals[$month], 0);

                    $timeSeriesData = array_map(fn ($v) => round($v, 2), array_values($monthlyTotals));

                } elseif ($period === 'yearly') {
                    $timeSeriesData = [$year => round($totalOmsetBersih, 2)];
                }

                // Susun Array Toko
                $storesList[] = [
                    'toko_id'          => $toko->id,
                    'singkatan'        => $toko->singkatan,
                    'nama_toko'        => $toko->nama,
                    'is_parent'        => $isParent,
                    'parent_id'        => $toko->parent_id,
                    'jumlah_transaksi' => $jumlahTxKasir,
                    'ringkasan_omset'  => [
                        'total_omset_bersih' => round($totalOmsetBersih, 2),
                        'omset_kasir'        => round($omsetKasirToko, 2),
                        'omset_pnf'          => round($omsetPnfToko, 2),
                        'biaya_retur'        => round($biayaRetur, 2),
                        'kasbon'             => round($kasbon, 2),
                    ],
                    $period            => $timeSeriesData,
                    'total_omset'      => round($totalOmsetBersih, 2),
                ];

                // Akumulasi ke Ringkasan Dashboard
                $summary['grand_total']       += $totalOmsetBersih;
                $summary['total_omset_kasir'] += $omsetKasirToko;
                $summary['total_omset_pnf']   += $omsetPnfToko;
                $summary['total_biaya_retur'] += $biayaRetur;
                $summary['total_kasbon']      += $kasbon;
                $summary['total_transaksi']   += $jumlahTxKasir;
            }

            // Pembulatan Desimal Ringkasan
            foreach ($summary as $key => $val) {
                $summary[$key] = round($val, 2);
            }

            $responseData = [
                'periode' => [
                    'start_date'  => $startDate->toDateString(),
                    'end_date'    => $endDate->toDateString(),
                    'period_type' => $period,
                ],
                'summary' => $summary,
                'stores'  => $storesList,
            ];

            return $this->success($responseData, 200, 'Data laporan toko berhasil diambil!');

        } catch (\Throwable $e) {
            return $this->error(500, 'Gagal mengambil data laporan toko', $e->getMessage());
        }
    }

    /**
     * 2. GET TOP MEMBER & BARANG (Drill-down saat Toko Diklik)
     */
    public function getTopMemberBarang(Request $request)
    {
        $idToko         = $request->input('toko_id', 'all');
        $startDateInput = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDateInput   = $request->input('end_date', now()->endOfMonth()->toDateString());
        $period         = $request->input('period', 'monthly');

        $startDate = Carbon::parse($startDateInput)->startOfDay();
        $endDate   = Carbon::parse($endDateInput)->endOfDay();

        try {
            $tokoIds = [];
            if ($idToko !== 'all' && !empty($idToko)) {
                $tokoIds = Toko::where('id', $idToko)->orWhere('parent_id', $idToko)->pluck('id')->toArray();
            }

            // --- QUERY TOP 10 BARANG TERJUAL ---
            $subqueryReturBarang = ReturMemberDetail::select(
                'retur_member_detail.barang_id',
                DB::raw('SUM(retur_member_detail.qty_refund) as total_qty_refund'),
                DB::raw('SUM(retur_member_detail.total_refund) as total_nominal_refund')
            )
                ->join('retur_member', 'retur_member_detail.retur_id', '=', 'retur_member.id')
                ->whereBetween(DB::raw('DATE(retur_member.tanggal)'), [$startDate->toDateString(), $endDate->toDateString()])
                ->when(!empty($tokoIds), fn ($q) => $q->whereIn('retur_member.toko_id', $tokoIds))
                ->groupBy('retur_member_detail.barang_id');

            $topBarang = TransaksiKasirDetail::select(
                'barang.nama',
                DB::raw('SUM(transaksi_kasir_detail.qty) - COALESCE(retur_sub.total_qty_refund, 0) as net_terjual'),
                DB::raw('COALESCE(retur_sub.total_qty_refund, 0) as total_retur'),
                DB::raw('SUM((transaksi_kasir_detail.qty * transaksi_kasir_detail.nominal) - COALESCE(transaksi_kasir_detail.diskon, 0)) - COALESCE(retur_sub.total_nominal_refund, 0) as net_nilai')
            )
                ->join('stock_barang_batch', 'transaksi_kasir_detail.stock_barang_batch_id', '=', 'stock_barang_batch.id')
                ->join('stock_barang', 'stock_barang_batch.stock_barang_id', '=', 'stock_barang.id')
                ->join('barang', 'stock_barang.barang_id', '=', 'barang.id')
                ->join('transaksi_kasir', 'transaksi_kasir_detail.transaksi_kasir_id', '=', 'transaksi_kasir.id')
                ->leftJoinSub($subqueryReturBarang, 'retur_sub', fn ($join) => $join->on('stock_barang.barang_id', '=', 'retur_sub.barang_id'))
                ->where('transaksi_kasir.total_qty', '>', 0)
                ->whereNull('transaksi_kasir.deleted_at')
                ->whereBetween(DB::raw('DATE(transaksi_kasir.tanggal)'), [$startDate->toDateString(), $endDate->toDateString()])
                ->when(!empty($tokoIds), fn ($q) => $q->whereIn('transaksi_kasir.toko_id', $tokoIds))
                ->groupBy('stock_barang.barang_id', 'barang.nama', 'retur_sub.total_qty_refund', 'retur_sub.total_nominal_refund')
                ->orderByDesc('net_terjual')
                ->limit(10)
                ->get()
                ->map(fn ($item) => [
                    'nama_barang' => class_exists('TextGenerate') ? TextGenerate::smartTail($item->nama) : $item->nama,
                    'jumlah'      => (int) $item->net_terjual,
                    'total_retur' => (int) $item->total_retur,
                    'total_nilai' => round((float) $item->net_nilai, 2),
                ]);

            // --- QUERY TOP 10 MEMBER ---
            $subqueryReturMember = ReturMemberDetail::select(
                'retur_member_detail.transaksi_kasir_detail_id',
                DB::raw('SUM(retur_member_detail.qty_refund) as total_qty_refund')
            )
                ->join('retur_member', 'retur_member_detail.retur_id', '=', 'retur_member.id')
                ->whereBetween(DB::raw('DATE(retur_member.tanggal)'), [$startDate->toDateString(), $endDate->toDateString()])
                ->when(!empty($tokoIds), fn ($sub) => $sub->whereIn('retur_member.toko_id', $tokoIds))
                ->groupBy('retur_member_detail.transaksi_kasir_detail_id');

            $topMember = Member::select(
                'member.id',
                'member.nama as nama_member',
                'transaksi_kasir.toko_id',
                'toko.nama as nama_toko',
                DB::raw('SUM(transaksi_kasir_detail.qty - COALESCE(retur_sum.total_qty_refund, 0)) as total_barang_dibeli'),
                DB::raw('SUM(transaksi_kasir_detail.subtotal) as total_pembayaran'),
                DB::raw('SUM((transaksi_kasir_detail.qty - COALESCE(retur_sum.total_qty_refund, 0)) * transaksi_kasir_detail.nominal) as total_pembayaran_setelah_retur')
            )
                ->join('transaksi_kasir', 'member.id', '=', 'transaksi_kasir.member_id')
                ->join('transaksi_kasir_detail', 'transaksi_kasir.id', '=', 'transaksi_kasir_detail.transaksi_kasir_id')
                ->join('toko', 'transaksi_kasir.toko_id', '=', 'toko.id')
                ->leftJoinSub($subqueryReturMember, 'retur_sum', 'retur_sum.transaksi_kasir_detail_id', '=', 'transaksi_kasir_detail.id')
                ->where('transaksi_kasir.total_qty', '>', 0)
                ->whereNull('transaksi_kasir.deleted_at')
                ->whereBetween(DB::raw('DATE(transaksi_kasir.tanggal)'), [$startDate->toDateString(), $endDate->toDateString()])
                ->when(!empty($tokoIds), fn ($q) => $q->whereIn('transaksi_kasir.toko_id', $tokoIds))
                ->groupBy('transaksi_kasir.toko_id', 'toko.nama', 'member.id', 'nama_member')
                ->orderByDesc('total_pembayaran_setelah_retur')
                ->limit(10)
                ->get()
                ->map(fn ($item) => [
                    'nama_member'                    => $item->nama_member,
                    'toko_id'                        => $item->toko_id,
                    'nama_toko'                      => $item->nama_toko,
                    'total_barang_dibeli'            => (int) $item->total_barang_dibeli,
                    'total_pembayaran'               => round((float) $item->total_pembayaran, 2),
                    'total_pembayaran_setelah_retur' => round((float) $item->total_pembayaran_setelah_retur, 2),
                ]);

            $responseData = [
                'periode' => [
                    'start_date'  => $startDate->toDateString(),
                    'end_date'    => $endDate->toDateString(),
                    'period_type' => $period,
                ],
                'toko_id'    => $idToko,
                'top_barang' => $topBarang,
                'top_member' => $topMember,
            ];

            return $this->success($responseData, 200, 'Data detail top barang dan member berhasil diambil!');

        } catch (\Throwable $th) {
            return $this->error(500, 'Gagal mengambil detail toko', $th->getMessage());
        }
    }

    /**
     * HELPER PRIVAT: Query Retur Member & Supplier (Mencegah Duplikasi Kode)
     */
    private function getKomponenPengurang($startDate, $endDate, $groupBy = 'toko')
    {
        $groupColumn   = $groupBy === 'bulan' ? 'MONTH(retur_member_detail.created_at)' : 'retur_member.toko_id';
        $supplierGroup = $groupBy === 'bulan' ? 'MONTH(retur_supplier_detail.created_at)' : 'retur_supplier.toko_id';

        $refund = ReturMemberDetail::join('retur_member', 'retur_member_detail.retur_id', '=', 'retur_member.id')
            ->where('retur_member_detail.qty_refund', '>', 0)
            ->whereBetween('retur_member_detail.created_at', [$startDate, $endDate])
            ->selectRaw("{$groupColumn} as key_group, SUM(retur_member_detail.total_refund) as total")
            ->groupBy('key_group')->pluck('total', 'key_group')->toArray();

        $untung = ReturSupplierDetail::join('retur_supplier', 'retur_supplier_detail.retur_supplier_id', '=', 'retur_supplier.id')
            ->where('retur_supplier_detail.qty_refund', '>', 0)->where('retur_supplier_detail.keterangan', 'untung')
            ->whereBetween('retur_supplier_detail.created_at', [$startDate, $endDate])
            ->selectRaw("{$supplierGroup} as key_group, SUM(retur_supplier_detail.selisih) as total")
            ->groupBy('key_group')->pluck('total', 'key_group')->toArray();

        $rugi = ReturSupplierDetail::join('retur_supplier', 'retur_supplier_detail.retur_supplier_id', '=', 'retur_supplier.id')
            ->where('retur_supplier_detail.qty_refund', '>', 0)->where('retur_supplier_detail.keterangan', 'rugi')
            ->whereBetween('retur_supplier_detail.created_at', [$startDate, $endDate])
            ->selectRaw("{$supplierGroup} as key_group, SUM(retur_supplier_detail.selisih) as total")
            ->groupBy('key_group')->pluck('total', 'key_group')->toArray();

        return ['refund' => $refund, 'untung' => $untung, 'rugi' => $rugi];
    }
}
