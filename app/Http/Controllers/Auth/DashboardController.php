<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\TextGenerate;
use App\Http\Controllers\Controller;
use App\Models\DetailKasir;
use App\Models\PenjualanNonFisik;
use App\Models\Kasir;
use App\Models\PenjualanNonFisikDetail;
use App\Models\Member;
use App\Models\ReturMemberDetail;
use App\Models\ReturSupplierDetail;
use App\Models\Toko;
use App\Models\TransaksiKasir;
use App\Models\TransaksiKasirDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class DashboardController extends Controller
{
    use ApiResponse;
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
                $namaToko = $toko ? $toko->nama : 'Unknown';
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
            $kasirData = TransaksiKasir::with('toko:id,nama')
                ->when($idToko !== 'all', fn($q) => $q->where('toko_id', $idToko))
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->select('id', 'toko_id', 'tanggal', 'total_nominal', 'total_diskon')
                ->get();

            // === Data Penjualan Non Fisik
            $pnfData = PenjualanNonFisik::with('createdBy')
                ->when($idToko !== 'all' && $idToko != 1, function ($q) use ($idToko) {
                    $q->whereHas('createdBy', fn($sub) => $sub->where('toko_id', $idToko));
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

            $laporan = [
                'nama_toko' => $namaToko,
                $period => [],
                'totals' => 0,
            ];

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
            } elseif ($period === 'monthly') {
                $monthlyTotals = array_fill(1, 12, 0);

                foreach ($kasirData as $data) {
                    $bulan = (int) Carbon::parse($data->created_at)->format('n');
                    $monthlyTotals[$bulan] += ($data->total_nominal - $data->total_diskon);
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
            } elseif ($period === 'yearly') {
                $yearlyTotals = [];

                foreach ($kasirData as $data) {
                    $dataYear = (int) Carbon::parse($data->created_at)->format('Y');
                    $yearlyTotals[$dataYear] = ($yearlyTotals[$dataYear] ?? 0) + ($data->total_nominal - $data->total_diskon);
                }

                foreach ($pnfData as $data) {
                    $dataYear = (int) Carbon::parse($data->created_at)->format('Y');
                    $yearlyTotals[$dataYear] = ($yearlyTotals[$dataYear] ?? 0) + (float)$data->total_harga_jual;
                }

                $refundTotal = $refundPerBulan->sum();
                $keuntunganTotal = $keuntunganRefundPerBulan->sum();
                $kerugianTotal = $kerugianRefundPerBulan->sum();
                // $kasbonTotal = $kasbonPerBulan->sum();

                foreach ($yearlyTotals as $th => $total) {
                    $yearlyTotals[$th] -= ($refundTotal - $keuntunganTotal + $kerugianTotal);
                }

                $laporan['yearly'] = $yearlyTotals;
                $laporan['totals'] = array_sum($yearlyTotals);
            }

            return $this->success($laporan, 200, 'Data berhasil diambil!');
        } catch (\Throwable $e) {
            return $this->error(500, $e->getMessage());
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
                'retur_member_detail.barang_id', // WAJIB ADA
                DB::raw('SUM(retur_member_detail.qty_refund) as total_qty_refund'),
                DB::raw('SUM(retur_member_detail.total_refund) as total_nominal_refund')
            )
                ->join('retur_member', 'retur_member_detail.retur_id', '=', 'retur_member.id')
                ->join('transaksi_kasir_detail', 'retur_member_detail.transaksi_kasir_detail_id', '=', 'transaksi_kasir_detail.id')
                ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                    $q->whereBetween(DB::raw('DATE(retur_member.tanggal)'), [$startDate, $endDate]);
                })
                ->when(!empty($selectedTokoIds) && $selectedTokoIds !== 'all', function ($q) use ($selectedTokoIds) {
                    $q->whereIn('retur_member.toko_id', (array) $selectedTokoIds);
                })
                ->groupBy('retur_member_detail.barang_id');

            // ===== QUERY UTAMA PENJUALAN =====
            $query = TransaksiKasirDetail::select(
                'barang.nama',
                DB::raw('SUM(transaksi_kasir_detail.qty) as total_terjual'),
                DB::raw('SUM((transaksi_kasir_detail.qty * transaksi_kasir_detail.nominal) - COALESCE(transaksi_kasir_detail.diskon, 0)) as total_nilai'),
                DB::raw('COALESCE(retur_sub.total_qty_refund, 0) as total_retur'),
                DB::raw('SUM(transaksi_kasir_detail.qty) - COALESCE(retur_sub.total_qty_refund, 0) as net_terjual'),
                DB::raw('
                SUM((transaksi_kasir_detail.qty * transaksi_kasir_detail.nominal) - COALESCE(transaksi_kasir_detail.diskon, 0))
                - COALESCE(retur_sub.total_nominal_refund, 0)
                as net_nilai
            ')
            )
                ->join('stock_barang_batch', 'transaksi_kasir_detail.stock_barang_batch_id', '=', 'stock_barang_batch.id')
                ->join('stock_barang', 'stock_barang_batch.stock_barang_id', '=', 'stock_barang.id')
                ->join('barang', 'stock_barang.barang_id', '=', 'barang.id')
                ->join('transaksi_kasir', 'transaksi_kasir_detail.transaksi_kasir_id', '=', 'transaksi_kasir.id')
                ->leftJoinSub($subqueryRetur, 'retur_sub', function ($join) {
                    $join->on('stock_barang.barang_id', '=', 'retur_sub.barang_id');
                })
                ->where('transaksi_kasir.total_qty', '>', 0)
                ->whereNull('transaksi_kasir.deleted_at');

            // ===== FILTER TOKO =====
            if (!empty($selectedTokoIds) && $selectedTokoIds !== 'all') {
                $query->whereIn('transaksi_kasir.toko_id', (array) $selectedTokoIds)
                    ->groupBy('transaksi_kasir.toko_id', 'stock_barang.barang_id', 'barang.nama', 'retur_sub.total_qty_refund', 'retur_sub.total_nominal_refund');
            } else {
                $query->groupBy('stock_barang.barang_id', 'barang.nama', 'retur_sub.total_qty_refund', 'retur_sub.total_nominal_refund');
            }

            // ===== AMBIL DATA =====
            $dataBarang = $query->orderByDesc('net_terjual')->limit(10)->get();

            // ===== MAPPING HASIL =====
            $data = $dataBarang->map(function ($item) {
                return [
                    'nama_barang' => TextGenerate::smartTail($item->nama),
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
            'member.nama as nama_member',
            'transaksi_kasir.toko_id',
            'toko.nama',
            // jumlah total barang (qty) dikurangi retur
            DB::raw('
            SUM(transaksi_kasir_detail.qty - COALESCE(retur_sum.total_qty_refund, 0))
            as total_barang_dibeli
        '),
            // total pembayaran sebelum retur
            DB::raw('SUM(transaksi_kasir_detail.subtotal) as total_pembayaran'),
            // total pembayaran setelah retur
            DB::raw('
            SUM(
                (transaksi_kasir_detail.qty - COALESCE(retur_sum.total_qty_refund, 0)) * transaksi_kasir_detail.nominal
            ) as total_pembayaran_setelah_retur
        ')
        )
            ->join('transaksi_kasir', 'member.id', '=', 'transaksi_kasir.member_id')
            ->join('transaksi_kasir_detail', 'transaksi_kasir.id', '=', 'transaksi_kasir_detail.transaksi_kasir_id')
            ->join('toko', 'transaksi_kasir.toko_id', '=', 'toko.id')
            ->where('transaksi_kasir.total_qty', '>', 0)
            // join subquery retur berbasis model
            ->leftJoinSub(
                ReturMemberDetail::select(
                    'retur_member_detail.transaksi_kasir_detail_id',
                    DB::raw('SUM(retur_member_detail.qty_refund) as total_qty_refund'),
                    DB::raw('SUM(retur_member_detail.total_refund) as total_refund')
                )
                    ->join('retur_member', 'retur_member_detail.retur_id', '=', 'retur_member.id')
                    ->when($startDate && $endDate, function ($sub) use ($startDate, $endDate) {
                        $sub->whereBetween(DB::raw('DATE(retur_member.tanggal)'), [$startDate, $endDate]);
                    })
                    ->groupBy('retur_member_detail.transaksi_kasir_detail_id'),
                'retur_sum',
                'retur_sum.transaksi_kasir_detail_id',
                '=',
                'transaksi_kasir_detail.id'
            );

        // ====== FILTER TOKO ======
        if (!empty($selectedTokoIds) && $selectedTokoIds !== 'all') {
            $query->where('transaksi_kasir.toko_id', $selectedTokoIds);
        }

        // ====== GROUPING ======
        $query->groupBy('transaksi_kasir.toko_id', 'toko.nama', 'member.id', 'nama_member');

        // ====== HASIL ======
        $dataMember = $query->orderByDesc('total_pembayaran_setelah_retur')
            ->limit(10)
            ->get();

        // ====== MAPPING ======
        $data = $dataMember->map(function ($item) {
            return [
                'nama_member' => $item->nama_member,
                'id_toko' => $item->toko_id,
                'nama_toko' => $item->toko->nama,
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

    public function getOmset(Request $request)
    {
        $startDate = $request->input('startDate', now()->format('Y-m-d')) . ' 00:00:00';
        $endDate   = $request->input('endDate', now()->format('Y-m-d')) . ' 23:59:59';

        $idTokoLogin = $request->input('id_toko');

        try {
            // Omset dari Kasir
            $query = Toko::leftJoin('transaksi_kasir', function ($join) use ($startDate, $endDate) {
                $join->on('toko.id', '=', 'transaksi_kasir.toko_id')
                    ->where('transaksi_kasir.total_qty', '>', 0)
                    ->whereNull('deleted_at')
                    ->whereBetween('transaksi_kasir.tanggal', [$startDate, $endDate]);
            })
                ->when($idTokoLogin, function ($query) use ($idTokoLogin) {
                    return $query->where('toko.id', $idTokoLogin);
                })
                ->selectRaw('SUM(COALESCE(transaksi_kasir.total_nominal, 0) - COALESCE(transaksi_kasir.total_diskon, 0)) as total_nominal');

            $omsetData = $query->first();
            $totalOmsetKasir = (float) ($omsetData->total_nominal ?? 0);

            $pnfQuery = PenjualanNonFisik::whereBetween('created_at', [$startDate, $endDate])
                ->when($idTokoLogin && $idTokoLogin != 1, function ($q) use ($idTokoLogin) {
                    $q->whereHas('createdBy', function ($sub) use ($idTokoLogin) {
                        $sub->where('toko_id', $idTokoLogin);
                    });
                })
                ->selectRaw('SUM(total_harga_jual) as total_pnf');

            $pnfData = $pnfQuery->first();
            $totalOmsetPNF = (float) ($pnfData->total_pnf ?? 0);

            $totalOmset = $totalOmsetKasir + $totalOmsetPNF;

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

            $totalKasbon = 0;

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
            $labakotorKasir = TransaksiKasirDetail::join('transaksi_kasir', 'transaksi_kasir.id', '=', 'transaksi_kasir_detail.transaksi_kasir_id')
                ->join('stock_barang_batch', 'stock_barang_batch.id', '=', 'transaksi_kasir_detail.stock_barang_batch_id')
                ->where('transaksi_kasir.total_qty', '>', 0)
                ->whereBetween('transaksi_kasir.tanggal', [$startDate, $endDate])
                ->when($idTokoLogin, function ($query) use ($idTokoLogin) {
                    return $query->where('transaksi_kasir.toko_id', $idTokoLogin);
                })
                ->selectRaw('SUM(transaksi_kasir_detail.subtotal) as total_penjualan, SUM(stock_barang_batch.harga_beli * transaksi_kasir_detail.qty) as total_hpp')
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
            $jumlahTransaksiKasir = TransaksiKasir::where('total_qty', '>', 0)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->when($idTokoLogin, function ($query) use ($idTokoLogin) {
                    return $query->where('toko_id', $idTokoLogin);
                })
                ->count();

            // Jumlah transaksi penjualan non fisik
            $jumlahTransaksiPNF = PenjualanNonFisik::whereBetween('created_at', [$startDate, $endDate])
                ->when($idTokoLogin && $idTokoLogin != 1, function ($query) use ($idTokoLogin) {
                    return $query->whereHas('createdBy', function ($q) use ($idTokoLogin) {
                        $q->where('toko_id', $idTokoLogin);
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
            $query = Toko::leftJoin('transaksi_kasir', function ($join) use ($startDate, $endDate) {
                $join->on('toko.id', '=', 'transaksi_kasir.toko_id')
                    ->where('transaksi_kasir.total_qty', '>', 0)
                    ->whereNull('deleted_at')
                    ->whereBetween('transaksi_kasir.tanggal', [
                        $startDate . ' 00:00:00',
                        $endDate . ' 23:59:59'
                    ]);
            })
                ->selectRaw('
    toko.id,
    toko.singkatan,
    COUNT(transaksi_kasir.id) as jumlah_transaksi,
    COALESCE(SUM(transaksi_kasir.total_nominal),0) - COALESCE(SUM(transaksi_kasir.total_diskon),0) as total_transaksi
')
                ->groupBy('toko.id', 'toko.singkatan');

            $tokoData = $query->get();

            $result = [
                'singkatan' => [],
                'total' => 0,
            ];

            foreach ($tokoData as $data) {
                $total = $data->total_transaksi ?? 0;

                $result['singkatan'][] = [
                    $data->singkatan => [
                        'jumlah_transaksi' => (int) $data->jumlah_transaksi,
                        'total_transaksi' => round($total, 2), // aman
                    ],
                ];

                $result['total'] += $total;
            }

            // optional: rapihin total akhir
            $result['total'] = round($result['total'], 2);

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
