<?php

namespace App\Http\Controllers;

use App\Models\DetailKasir;
use App\Models\PenjualanNonFisik;
use App\Models\Kasir;
use App\Models\PenjualanNonFisikDetail;
use App\Models\Member;
use App\Models\ReturMemberDetail;
use App\Models\ReturSupplierDetail;
use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $title = 'Dashboard';
        return view('master.index', compact('title'));
    }

    public function laporan_kasir(Request $request)
    {
        $idToko = $request->input('nama_toko', 'all');
        $period = $request->input('period', 'monthly');
        $month = $period === 'daily' ? (int)$request->input('month', now()->month) : null;
        $year = (int)$request->input('year', now()->year);

        try {
            // === Nama toko
            $namaToko = 'All';
            if ($idToko !== 'all') {
                $toko = Toko::find($idToko);
                $namaToko = $toko ? $toko->nama_toko : 'Unknown';
            }

            // === Rentang tanggal
            if ($period === 'daily' && $month) {
                $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
                $endDate   = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
            } else {
                $startDate = Carbon::createFromDate($year, 1, 1)->startOfDay();
                $endDate   = Carbon::createFromDate($year, 12, 31)->endOfDay();
            }

            // === Data Kasir
            $kasirData = Kasir::with('toko:id,nama_toko')
                ->when($idToko !== 'all', fn($q) => $q->where('id_toko', $idToko))
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('id', 'id_toko', 'created_at', 'total_nilai', 'total_diskon')
                ->get();

            // === Data Penjualan Non Fisik
            $pnfData = PenjualanNonFisik::with('createdBy')
                ->when($idToko !== 'all' && $idToko != 1, function ($q) use ($idToko) {
                    $q->whereHas('createdBy', fn($sub) => $sub->where('id_toko', $idToko));
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('id', 'created_at', 'total_harga_jual')
                ->get();

            // === Retur Member per Bulan
            $refundPerBulan = ReturMemberDetail::where('qty_refund', '>', 0)
                ->whereYear('created_at', $year)
                ->selectRaw('MONTH(created_at) as bulan, SUM(total_refund) as total')
                ->groupBy('bulan')
                ->pluck('total', 'bulan')
                ->map(fn($v) => (float)$v);

            // === Refund Supplier per Bulan (untung & rugi)
            $keuntunganRefundPerBulan = ReturSupplierDetail::where('qty_refund', '>', 0)
                ->whereYear('created_at', $year)
                ->where('keterangan', 'untung')
                ->selectRaw('MONTH(created_at) as bulan, SUM(selisih) as total')
                ->groupBy('bulan')
                ->pluck('total', 'bulan')
                ->map(fn($v) => (float)$v);

            $kerugianRefundPerBulan = ReturSupplierDetail::where('qty_refund', '>', 0)
                ->whereYear('created_at', $year)
                ->where('keterangan', 'rugi')
                ->selectRaw('MONTH(created_at) as bulan, SUM(selisih) as total')
                ->groupBy('bulan')
                ->pluck('total', 'bulan')
                ->map(fn($v) => (float)$v);

            // === Kasbon per Bulan
            $kasbonPerBulan = DB::table('kasbon')
                ->join('kasir', 'kasbon.id_kasir', '=', 'kasir.id')
                ->where('kasbon.utang_sisa', '>', 0)
                ->when($idToko !== 'all', fn($q) => $q->where('kasir.id_toko', $idToko))
                ->selectRaw('MONTH(kasir.tgl_transaksi) as bulan, SUM(kasbon.utang_sisa) as total')
                ->groupBy('bulan')
                ->pluck('total', 'bulan')
                ->map(fn($v) => (float)$v);

            // === Inisialisasi laporan
            $laporan = [
                'nama_toko' => $namaToko,
                $period => [],
                'totals' => 0,
            ];

            // ================================================================
            // ======================= DAILY REPORT ==========================
            // ================================================================
            if ($period === 'daily') {
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                $dailyTotals = array_fill(1, $daysInMonth, 0);

                foreach ($kasirData as $data) {
                    $day = (int) Carbon::parse($data->created_at)->format('j');
                    $dailyTotals[$day] += ($data->total_nilai - $data->total_diskon);
                }

                foreach ($pnfData as $data) {
                    $day = (int) Carbon::parse($data->created_at)->format('j');
                    $dailyTotals[$day] += (float)$data->total_harga_jual;
                }

                // Hitung retur & kasbon hanya untuk bulan yg sedang dipilih
                $refund = $refundPerBulan[$month] ?? 0;
                $keuntungan = $keuntunganRefundPerBulan[$month] ?? 0;
                $kerugian = $kerugianRefundPerBulan[$month] ?? 0;
                $kasbon = $kasbonPerBulan[$month] ?? 0;

                foreach (range(1, $daysInMonth) as $day) {
                    $dailyTotals[$day] -= (($refund - $keuntungan + $kerugian) / $daysInMonth);
                    $dailyTotals[$day] -= ($kasbon / $daysInMonth);
                }

                $laporan['daily'] = [
                    $year => [
                        $month => array_values($dailyTotals),
                    ],
                ];
                $laporan['totals'] = array_sum($dailyTotals);
            }

            // ================================================================
            // ======================= MONTHLY REPORT ========================
            // ================================================================
            elseif ($period === 'monthly') {
                $monthlyTotals = array_fill(1, 12, 0);

                foreach ($kasirData as $data) {
                    $bulan = (int) Carbon::parse($data->created_at)->format('n');
                    $monthlyTotals[$bulan] += ($data->total_nilai - $data->total_diskon);
                }

                foreach ($pnfData as $data) {
                    $bulan = (int) Carbon::parse($data->created_at)->format('n');
                    $monthlyTotals[$bulan] += (float)$data->total_harga_jual;
                }

                // Kurangkan retur & kasbon per bulan
                foreach (range(1, 12) as $bulan) {
                    $refund = $refundPerBulan[$bulan] ?? 0;
                    $keuntungan = $keuntunganRefundPerBulan[$bulan] ?? 0;
                    $kerugian = $kerugianRefundPerBulan[$bulan] ?? 0;
                    $kasbon = $kasbonPerBulan[$bulan] ?? 0;
                    $monthlyTotals[$bulan] -= ($refund - $keuntungan + $kerugian + $kasbon);
                }

                $laporan['monthly'] = [
                    $year => array_values($monthlyTotals),
                ];
                $laporan['totals'] = array_sum($monthlyTotals);
            }

            // ================================================================
            // ======================= YEARLY REPORT =========================
            // ================================================================
            elseif ($period === 'yearly') {
                $yearlyTotals = [];

                foreach ($kasirData as $data) {
                    $dataYear = (int) Carbon::parse($data->created_at)->format('Y');
                    $yearlyTotals[$dataYear] = ($yearlyTotals[$dataYear] ?? 0) + ($data->total_nilai - $data->total_diskon);
                }

                foreach ($pnfData as $data) {
                    $dataYear = (int) Carbon::parse($data->created_at)->format('Y');
                    $yearlyTotals[$dataYear] = ($yearlyTotals[$dataYear] ?? 0) + (float)$data->total_harga_jual;
                }

                // Hitung total retur & kasbon per tahun
                $refundTotal = $refundPerBulan->sum();
                $keuntunganTotal = $keuntunganRefundPerBulan->sum();
                $kerugianTotal = $kerugianRefundPerBulan->sum();
                $kasbonTotal = $kasbonPerBulan->sum();

                foreach ($yearlyTotals as $th => $total) {
                    $yearlyTotals[$th] -= ($refundTotal - $keuntunganTotal + $kerugianTotal + $kasbonTotal);
                }

                $laporan['yearly'] = $yearlyTotals;
                $laporan['totals'] = array_sum($yearlyTotals);
            }

            // ================================================================
            // ======================= RETURN RESPONSE =======================
            // ================================================================
            return response()->json([
                'error' => false,
                'message' => 'Successfully',
                'status_code' => 200,
                'data' => [$laporan],
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => 'Error',
                'status_code' => 500,
                'data' => $th->getMessage(),
            ]);
        }
    }

    public function getBarangJual(Request $request)
    {
        try {
            $selectedTokoIds = $request->input('id_toko');
            $startDate = $request->input('start_date');
            $endDate   = $request->input('end_date');

            // ===== SUBQUERY RETUR MENGGUNAKAN MODEL =====
            $subqueryRetur = ReturMemberDetail::select(
                'detail_kasir.id_barang',
                DB::raw('SUM(retur_member_detail.qty_refund) as total_qty_refund'),
                DB::raw('SUM(retur_member_detail.total_refund) as total_nominal_refund')
            )
                ->join('retur_member', 'retur_member_detail.retur_id', '=', 'retur_member.id')
                ->join('detail_kasir', 'retur_member_detail.detail_kasir_id', '=', 'detail_kasir.id')
                ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                    $q->whereBetween(DB::raw('DATE(retur_member.tanggal)'), [$startDate, $endDate]);
                })
                ->when(!empty($selectedTokoIds) && $selectedTokoIds !== 'all', function ($q) use ($selectedTokoIds) {
                    $q->whereIn('retur_member.toko_id', (array) $selectedTokoIds);
                })
                ->groupBy('detail_kasir.id_barang');

            // ===== QUERY UTAMA PENJUALAN =====
            $query = DetailKasir::select(
                'detail_kasir.id_barang',
                'barang.nama_barang',
                DB::raw('SUM(detail_kasir.qty) as total_terjual'),
                DB::raw('SUM((detail_kasir.qty * detail_kasir.harga) - COALESCE(detail_kasir.diskon, 0)) as total_nilai'),
                DB::raw('COALESCE(retur_sub.total_qty_refund, 0) as total_retur'),
                DB::raw('SUM(detail_kasir.qty) - COALESCE(retur_sub.total_qty_refund, 0) as net_terjual'),
                DB::raw('
                SUM((detail_kasir.qty * detail_kasir.harga) - COALESCE(detail_kasir.diskon, 0))
                - COALESCE(retur_sub.total_nominal_refund, 0)
                as net_nilai
            ')
            )
                ->join('barang', 'detail_kasir.id_barang', '=', 'barang.id')
                ->join('kasir', 'detail_kasir.id_kasir', '=', 'kasir.id')
                ->leftJoinSub($subqueryRetur, 'retur_sub', function ($join) {
                    $join->on('detail_kasir.id_barang', '=', 'retur_sub.id_barang');
                })
                ->where('kasir.total_item', '>', 0);

            // ===== FILTER TOKO =====
            if (!empty($selectedTokoIds) && $selectedTokoIds !== 'all') {
                $query->whereIn('kasir.id_toko', (array) $selectedTokoIds)
                    ->groupBy('kasir.id_toko', 'detail_kasir.id_barang', 'barang.nama_barang', 'retur_sub.total_qty_refund', 'retur_sub.total_nominal_refund');
            } else {
                $query->groupBy('detail_kasir.id_barang', 'barang.nama_barang', 'retur_sub.total_qty_refund', 'retur_sub.total_nominal_refund');
            }

            // ===== AMBIL DATA =====
            $dataBarang = $query->orderByDesc('net_terjual')->limit(10)->get();

            // ===== MAPPING HASIL =====
            $data = $dataBarang->map(function ($item) {
                return [
                    'nama_barang' => $item->nama_barang,
                    'jumlah' => (int) $item->net_terjual,
                    'total_retur' => (int) $item->total_retur,
                    'total_nilai' => (float) $item->net_nilai
                ];
            });

            return response()->json([
                "error" => false,
                "message" => $data->isEmpty() ? "No data found" : "Data retrieved successfully",
                "status_code" => 200,
                "data" => $data
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "error" => true,
                "message" => "Failed to retrieve data",
                "status_code" => 500,
                "trace" => $th->getMessage()
            ]);
        }
    }

    public function getMember(Request $request)
    {
        $selectedTokoIds = $request->input('id_toko');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        // ====== QUERY DASAR ======
        $query = Member::select(
            'member.id',
            'member.nama_member',
            'kasir.id_toko',
            'toko.nama_toko',
            // jumlah total barang (qty) dikurangi retur
            DB::raw('
            SUM(detail_kasir.qty - COALESCE(retur_sum.total_qty_refund, 0))
            as total_barang_dibeli
        '),
            // total pembayaran sebelum retur
            DB::raw('SUM(detail_kasir.qty * detail_kasir.harga) as total_pembayaran'),
            // total pembayaran setelah retur
            DB::raw('
            SUM(
                (detail_kasir.qty - COALESCE(retur_sum.total_qty_refund, 0)) * detail_kasir.harga
            ) as total_pembayaran_setelah_retur
        ')
        )
            ->join('kasir', 'member.id', '=', 'kasir.id_member')
            ->join('detail_kasir', 'kasir.id', '=', 'detail_kasir.id_kasir')
            ->join('toko', 'kasir.id_toko', '=', 'toko.id')
            ->where('kasir.total_item', '>', 0)
            // join subquery retur berbasis model
            ->leftJoinSub(
                ReturMemberDetail::select(
                    'retur_member_detail.detail_kasir_id',
                    DB::raw('SUM(retur_member_detail.qty_refund) as total_qty_refund'),
                    DB::raw('SUM(retur_member_detail.total_refund) as total_refund')
                )
                    ->join('retur_member', 'retur_member_detail.retur_id', '=', 'retur_member.id')
                    ->when($startDate && $endDate, function ($sub) use ($startDate, $endDate) {
                        $sub->whereBetween(DB::raw('DATE(retur_member.tanggal)'), [$startDate, $endDate]);
                    })
                    ->groupBy('retur_member_detail.detail_kasir_id'),
                'retur_sum',
                'retur_sum.detail_kasir_id',
                '=',
                'detail_kasir.id'
            );

        // ====== FILTER TOKO ======
        if (!empty($selectedTokoIds) && $selectedTokoIds !== 'all') {
            $query->where('kasir.id_toko', $selectedTokoIds);
        }

        // ====== GROUPING ======
        $query->groupBy('kasir.id_toko', 'toko.nama_toko', 'member.id', 'member.nama_member');

        // ====== HASIL ======
        $dataMember = $query->orderByDesc('total_pembayaran_setelah_retur')
            ->limit(10)
            ->get();

        // ====== MAPPING ======
        $data = $dataMember->map(function ($item) {
            return [
                'nama_member' => $item->nama_member,
                'id_toko' => $item->id_toko,
                'nama_toko' => $item->nama_toko,
                'total_barang_dibeli' => (int) $item->total_barang_dibeli,
                'total_pembayaran' => (float) $item->total_pembayaran,
                'total_pembayaran_setelah_retur' => (float) $item->total_pembayaran_setelah_retur,
            ];
        });

        return response()->json([
            "error" => false,
            "message" => $data->isEmpty() ? "No data found" : "Data retrieved successfully",
            "status_code" => 200,
            "data" => $data
        ]);
    }


    // public function getOmset(Request $request)
    // {
    //     // Ambil tanggal dari request, default ke hari ini jika tidak ada input
    //     $startDate = $request->input('startDate', now()->toDateString());
    //     $endDate = $request->input('endDate', now()->toDateString());
    //     $month = $request->has('month') ? $request->month : Carbon::now()->month;
    //     $year = $request->has('year') ? $request->year : Carbon::now()->year;

    //     // Ambil id_toko dari request, default ke 1
    //     $idTokoLogin = $request->input('id_toko', 1);

    //     try {
    //         // Hitung total omset dari tabel kasir, tergantung id_toko
    //         $query = Toko::leftJoin('kasir', function ($join) use ($startDate, $endDate) {
    //             $join->on('toko.id', '=', 'kasir.id_toko')
    //                 ->whereBetween('kasir.tgl_transaksi', [$startDate, $endDate]);
    //         })
    //             ->when($idTokoLogin != 1, function ($query) use ($idTokoLogin) {
    //                 return $query->where('toko.id', $idTokoLogin);
    //             })
    //             ->when($idTokoLogin != 1, function ($query) {
    //                 return $query->where('toko.id', '!=', 1);
    //             })
    //             ->selectRaw('SUM(kasir.total_nilai - kasir.total_diskon) as total_nilai');

    //         $omsetData = $query->first();
    //         $totalOmset = $omsetData->total_nilai ?? 0;
    //         $biayaRetur = DB::table('detail_retur')
    //             ->leftJoin('data_retur', 'detail_retur.id_retur', '=', 'data_retur.id')
    //             ->where('detail_retur.metode', 'Cash')
    //             ->whereBetween('data_retur.tgl_retur', [$startDate, $endDate])
    //             ->when($idTokoLogin != 1, function ($query) use ($idTokoLogin) {
    //                 return $query->where('data_retur.id_toko', $idTokoLogin);
    //             })
    //             ->select(DB::raw('SUM(detail_retur.harga) as total_biaya_retur'))
    //             ->value('total_biaya_retur') ?? 0;

    //         $biayaReturs = DB::table('detail_retur')
    //             ->leftJoin('data_retur', 'detail_retur.id_retur', '=', 'data_retur.id')
    //             ->where('detail_retur.metode', 'Cash')
    //             ->whereBetween('data_retur.tgl_retur', [$startDate, $endDate])
    //             ->when($idTokoLogin != 1, function ($query) use ($idTokoLogin) {
    //                 return $query->where('data_retur.id_toko', $idTokoLogin);
    //             })
    //             ->select(DB::raw('SUM(detail_retur.harga - detail_retur.hpp_jual) as total_biaya_retur'))
    //             ->value('total_biaya_retur') ?? 0;

    //             // Get total kasbon for the specified toko
    //             $totalKasbon = DB::table('kasbon')
    //             ->join('kasir', 'kasbon.id_kasir', '=', 'kasir.id')
    //             ->where('kasbon.utang_sisa', '>', 0)
    //             ->when($idTokoLogin != 1, function ($query) use ($idTokoLogin) {
    //                 return $query->where('kasir.id_toko', $idTokoLogin);
    //             })
    //             ->select(DB::raw('SUM(kasbon.utang_sisa) as total_kasbon'))
    //             ->value('total_kasbon') ?? 0;

    //         $fixomset = $totalOmset - $biayaRetur - $totalKasbon;

    //         // Hitung laba kotor: total_harga - (hpp_jual * qty)
    //         $labakotorquery = DetailKasir::join('kasir', 'kasir.id', '=', 'detail_kasir.id_kasir')
    //             ->whereBetween('kasir.tgl_transaksi', [$startDate, $endDate])
    //             ->when($idTokoLogin != 1, function ($query) use ($idTokoLogin) {
    //                 return $query->where('kasir.id_toko', $idTokoLogin);
    //             })
    //             ->where('kasir.id_toko', '!=', 1)
    //             ->selectRaw('SUM(detail_kasir.total_harga) as total_penjualan, SUM(detail_kasir.hpp_jual * detail_kasir.qty) as total_hpp')
    //             ->first();

    //         $laba_kotor = ($labakotorquery->total_penjualan ?? 0) - ($labakotorquery->total_hpp ?? 0);

    //         $today = now()->toDateString();

    //         $totalTransaksiHariIni = Kasir::whereBetween('tgl_transaksi', [$startDate, $endDate])
    //             ->where('id_toko', '!=', 1)
    //             ->when($idTokoLogin != 1, function ($query) use ($idTokoLogin) {
    //                 return $query->where('id_toko', $idTokoLogin);
    //             })
    //             ->count();

    //         return response()->json([
    //             "error" => false,
    //             "message" => $totalOmset > 0 ? "Data retrieved successfully" : "No data found",
    //             "status_code" => 200,
    //             "data" => [
    //                 'total' => $fixomset,
    //                 'kasbon' => $totalKasbon,
    //                 'biaya_retur' => $biayaRetur,
    //                 'laba_kotor' => $laba_kotor - $biayaReturs,
    //                 'jumlah_trx' => $totalTransaksiHariIni,
    //             ],
    //         ]);
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             "error" => true,
    //             "message" => "Error retrieving data",
    //             "status_code" => 500,
    //             "data" => $th->getMessage(),
    //         ]);
    //     }
    // }

    public function getOmset(Request $request)
    {
        $startDate = $request->input('startDate', now()->format('Y-m-d')) . ' 00:00:00';
        $endDate   = $request->input('endDate', now()->format('Y-m-d')) . ' 23:59:59';

        $idTokoLogin = $request->input('id_toko');

        try {
            // Omset dari Kasir
            $query = Toko::leftJoin('kasir', function ($join) use ($startDate, $endDate) {
                $join->on('toko.id', '=', 'kasir.id_toko')
                    ->where('kasir.total_item', '>', 0)
                    ->whereBetween('kasir.tgl_transaksi', [$startDate, $endDate]);
            })
                ->when($idTokoLogin, function ($query) use ($idTokoLogin) {
                    return $query->where('toko.id', $idTokoLogin);
                })
                ->selectRaw('SUM(COALESCE(kasir.total_nilai, 0) - COALESCE(kasir.total_diskon, 0)) as total_nilai');

            $omsetData = $query->first();
            $totalOmsetKasir = (float) ($omsetData->total_nilai ?? 0);

            // Omset dari Penjualan Non Fisik (PNF)
            $pnfQuery = PenjualanNonFisik::whereBetween('created_at', [$startDate, $endDate])
                ->when($idTokoLogin && $idTokoLogin != 1, function ($q) use ($idTokoLogin) {
                    $q->whereHas('createdBy', function ($sub) use ($idTokoLogin) {
                        $sub->where('id_toko', $idTokoLogin);
                    });
                })
                ->selectRaw('SUM(total_harga_jual) as total_pnf');

            $pnfData = $pnfQuery->first();
            $totalOmsetPNF = (float) ($pnfData->total_pnf ?? 0);

            // Total Omset = Kasir + PNF
            $totalOmset = $totalOmsetKasir + $totalOmsetPNF;

            // Hitung retur
            $refundReturMember = ReturMemberDetail::where('qty_refund', '>', 0)
                ->whereHas('retur', function ($query) use ($startDate, $endDate, $idTokoLogin) {
                    $query->whereBetween('tanggal', [$startDate, $endDate])
                        ->where('toko_id', $idTokoLogin);
                })
                ->sum('total_refund');

            $keuntunganRefundSuplier = ReturSupplierDetail::where('qty_refund', '>', 0)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('keterangan', 'untung')->sum('selisih');

            $kerugianRefundSuplier = ReturSupplierDetail::where('qty_refund', '>', 0)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('keterangan', 'rugi')->sum('selisih');

            $returTotal = $refundReturMember - $keuntunganRefundSuplier + $kerugianRefundSuplier;

            // Hitung kasbon
            $totalKasbon = DB::table('kasbon')
                ->join('kasir', 'kasbon.id_kasir', '=', 'kasir.id')
                ->where('kasbon.utang_sisa', '>', 0)
                ->whereBetween('kasir.tgl_transaksi', [$startDate, $endDate])
                ->when($idTokoLogin, function ($query) use ($idTokoLogin) {
                    return $query->where('kasir.id_toko', $idTokoLogin);
                })
                ->select(DB::raw('SUM(kasbon.utang_sisa) as total_kasbon'))
                ->value('total_kasbon') ?? 0;

            // Fix Omset (dikurangi retur + kasbon)
            $fixOmset = max($totalOmset - $returTotal - $totalKasbon, 0);

            return response()->json([
                "error"       => false,
                "message"     => $totalOmset > 0 ? "Data retrieved successfully" : "No data found",
                "status_code" => 200,
                "data" => [
                    'total'       => $fixOmset,
                    'kasbon'      => (float) $totalKasbon,
                    'biaya_retur' => (float) $returTotal,
                    'omset_kasir' => $totalOmsetKasir,
                    'omset_pnf'   => $totalOmsetPNF,
                ],
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "error"       => true,
                "message"     => "Error retrieving omset",
                "status_code" => 500,
                "data"        => $th->getMessage(),
            ]);
        }
    }

    public function getLabaKotor(Request $request)
    {
        $startDate = $request->input('startDate', now()->format('Y-m-d')) . ' 00:00:00';
        $endDate = $request->input('endDate', now()->format('Y-m-d')) . ' 23:59:59';
        $idTokoLogin = $request->input('id_toko');

        try {
            // Biaya retur
            // $biayaReturs = DB::table('detail_retur')
            //     ->leftJoin('data_retur', 'detail_retur.id_retur', '=', 'data_retur.id')
            //     ->where('detail_retur.metode', 'Cash')
            //     ->whereBetween('data_retur.tgl_retur', [$startDate, $endDate])
            //     ->when($idTokoLogin, function ($query) use ($idTokoLogin) {
            //         return $query->where('data_retur.id_toko', $idTokoLogin);
            //     })
            //     ->select(DB::raw('SUM(detail_retur.harga - detail_retur.hpp_jual) as total_biaya_retur'))
            //     ->value('total_biaya_retur') ?? 0;

            // $returTotal = ReturMemberDetail::where('qty_refund', '>', 0)
            //     ->whereHas('retur', function ($query) use ($startDate, $endDate, $idTokoLogin) {
            //         $query->whereBetween('tanggal', [$startDate, $endDate])
            //             ->where('toko_id', $idTokoLogin);
            //     })
            //     ->selectRaw('SUM(total_refund - total_hpp) as laba')
            //     ->value('laba') ?? 0;

            $refundReturMember = ReturMemberDetail::where('qty_refund', '>', 0)
                ->whereHas('retur', function ($query) use ($startDate, $endDate, $idTokoLogin) {
                    $query->whereBetween('tanggal', [$startDate, $endDate])
                        ->where('toko_id', $idTokoLogin);
                })
                ->selectRaw('SUM(total_refund - total_hpp) as laba')
                ->value('laba') ?? 0;

            $keuntunganRefundSuplier = ReturSupplierDetail::where('qty_refund', '>', 0)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('keterangan', 'untung')->sum('selisih');

            $kerugianRefundSuplier = ReturSupplierDetail::where('qty_refund', '>', 0)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('keterangan', 'rugi')->sum('selisih');

            $returTotal = $refundReturMember - $keuntunganRefundSuplier + $kerugianRefundSuplier;

            // Laba kotor dari kasir
            $labakotorKasir = DetailKasir::join('kasir', 'kasir.id', '=', 'detail_kasir.id_kasir')
                ->where('kasir.total_item', '>', 0)
                ->whereBetween('kasir.tgl_transaksi', [$startDate, $endDate])
                ->when($idTokoLogin, function ($query) use ($idTokoLogin) {
                    return $query->where('kasir.id_toko', $idTokoLogin);
                })
                ->selectRaw('SUM(detail_kasir.total_harga) as total_penjualan, SUM(detail_kasir.hpp_jual * detail_kasir.qty) as total_hpp')
                ->first();

            $totalPenjualanKasir = (float) ($labakotorKasir->total_penjualan ?? 0);
            $totalHppKasir = (float) ($labakotorKasir->total_hpp ?? 0);

            // Laba kotor dari penjualan non fisik
            $labakotorPNF = PenjualanNonFisikDetail::whereHas('penjualanNonfisik', function ($q) use ($startDate, $endDate, $idTokoLogin) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                    ->when($idTokoLogin && $idTokoLogin != 1, function ($q2) use ($idTokoLogin) {
                        $q2->whereHas('createdBy', function ($q3) use ($idTokoLogin) {
                            $q3->where('id_toko', $idTokoLogin);
                        });
                    });
            })
                ->selectRaw('
                        SUM(harga_jual * qty) as total_penjualan,
                        SUM(hpp * qty) as total_hpp
                    ')
                ->first();

            $totalPenjualanPNF = (float) ($labakotorPNF->total_penjualan ?? 0);
            $totalHppPNF = (float) ($labakotorPNF->total_hpp ?? 0);


            // Gabungan
            $totalPenjualan = $totalPenjualanKasir + $totalPenjualanPNF;
            $totalHpp = $totalHppKasir + $totalHppPNF;

            $labaKotor = max($totalPenjualan - $totalHpp - $returTotal, 0);

            return response()->json([
                "error" => false,
                "message" => "Laba kotor berhasil dihitung",
                "status_code" => 200,
                "data" => [
                    'laba_kotor' => $labaKotor,
                ],
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "error" => true,
                "message" => "Error retrieving laba kotor",
                "status_code" => 500,
                "data" => $th->getMessage(),
            ]);
        }
    }

    public function getJumlahTransaksi(Request $request)
    {
        $startDate = $request->input('startDate', now()->format('Y-m-d')) . ' 00:00:00';
        $endDate = $request->input('endDate', now()->format('Y-m-d')) . ' 23:59:59';
        $idTokoLogin = $request->input('id_toko');

        try {
            // Jumlah transaksi kasir
            $jumlahTransaksiKasir = Kasir::where('total_item', '>', 0)
                ->whereBetween('tgl_transaksi', [$startDate, $endDate])
                ->when($idTokoLogin, function ($query) use ($idTokoLogin) {
                    return $query->where('id_toko', $idTokoLogin);
                })
                ->count();

            // Jumlah transaksi penjualan non fisik
            $jumlahTransaksiPNF = PenjualanNonFisik::whereBetween('created_at', [$startDate, $endDate])
                ->when($idTokoLogin && $idTokoLogin != 1, function ($query) use ($idTokoLogin) {
                    return $query->whereHas('createdBy', function ($q) use ($idTokoLogin) {
                        $q->where('id_toko', $idTokoLogin);
                    });
                })
                ->count();

            $jumlahTransaksi = $jumlahTransaksiKasir + $jumlahTransaksiPNF;

            return response()->json([
                "error" => false,
                "message" => "Jumlah transaksi berhasil dihitung",
                "status_code" => 200,
                "data" => [
                    'jumlah_transaksi' => $jumlahTransaksi ?? 0,
                ],
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "error" => true,
                "message" => "Error retrieving jumlah transaksi",
                "status_code" => 500,
                "data" => $th->getMessage(),
            ]);
        }
    }

    public function getKomparasiToko(Request $request)
    {
        $startDate = $request->input('startDate', now()->startOfDay()->toDateString());
        $endDate = $request->input('endDate', now()->endOfDay()->toDateString());

        try {
            $query = Toko::leftJoin('kasir', function ($join) use ($startDate, $endDate) {
                $join->on('toko.id', '=', 'kasir.id_toko')
                    ->where('kasir.total_item', '>', 0)
                    ->whereBetween('kasir.tgl_transaksi', [$startDate, $endDate]);
            })
                ->where('toko.id', '!=', 1)
                ->selectRaw('toko.id, toko.singkatan, COUNT(kasir.id) as jumlah_transaksi, SUM(kasir.total_nilai - kasir.total_diskon) as total_transaksi')
                ->groupBy('toko.id', 'toko.singkatan');

            $tokoData = $query->get();

            $result = [
                'singkatan' => [],
                'total' => 0,
            ];

            foreach ($tokoData as $data) {
                // Hitung assetRetur hanya untuk toko ini berdasarkan id_toko di data_retur
                $assetRetur = DB::table('detail_retur')
                    ->join('data_retur', 'detail_retur.id_retur', '=', 'data_retur.id')
                    ->leftJoin('stock_barang', 'detail_retur.id_barang', '=', 'stock_barang.id_barang')
                    ->where('data_retur.id_toko', $data->id)
                    ->whereBetween('data_retur.tgl_retur', [$startDate, $endDate])
                    ->select(DB::raw('SUM(CASE WHEN detail_retur.metode = "Cash" THEN detail_retur.harga ELSE stock_barang.hpp_baru END) as total_retur'))
                    ->value('total_retur') ?? 0;

                $assetRetur = -1 * $assetRetur;

                // Get total kasbon for this toko
                $totalKasbon = DB::table('kasbon')
                    ->join('kasir', 'kasbon.id_kasir', '=', 'kasir.id')
                    ->where('kasbon.utang_sisa', '>', 0)
                    ->where('kasir.id_toko', $data->id)
                    ->select(DB::raw('SUM(kasbon.utang_sisa) as total_kasbon'))
                    ->value('total_kasbon') ?? 0;

                $result['singkatan'][] = [
                    $data->singkatan => [
                        'jumlah_transaksi' => (int) $data->jumlah_transaksi,
                        'total_transaksi' => (float) (($data->total_transaksi ?? 0) + $assetRetur - $totalKasbon),
                    ],
                ];

                $result['total'] += ($data->total_transaksi + $assetRetur - $totalKasbon ?? 0);
            }

            return response()->json([
                'error' => false,
                'message' => 'Successfully retrieved comparison data',
                'status_code' => 200,
                'data' => $result,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => 'Error retrieving data',
                'status_code' => 500,
                'data' => $th->getMessage(),
            ]);
        }
    }
}
