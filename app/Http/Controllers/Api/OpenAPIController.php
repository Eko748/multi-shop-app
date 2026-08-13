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
use App\Traits\ApiResponse; // 1. Import Trait
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpenAPIController extends Controller
{
    use ApiResponse; // 2. Gunakan Trait di sini

    public function getTopMemberBarang(Request $request)
    {
        $idToko = $request->input('toko_id', 'all');
        $startDateInput = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDateInput = $request->input('end_date', now()->endOfMonth()->toDateString());
        $period = $request->input('period', 'monthly'); // 'daily', 'monthly', 'yearly', 'custom'

        $startDate = Carbon::parse($startDateInput)->startOfDay();
        $endDate = Carbon::parse($endDateInput)->endOfDay();

        try {
            // 1. Ambil Toko Spesifik & Child-nya
            $tokoIds = [];
            if ($idToko !== 'all' && ! empty($idToko)) {
                $tokoIds = Toko::where('id', $idToko)->orWhere('parent_id', $idToko)->pluck('id')->toArray();
            }

            // ==========================================
            // 2. QUERY TOP 10 BARANG TERJUAL
            // ==========================================
            $subqueryReturBarang = ReturMemberDetail::select(
                'retur_member_detail.barang_id',
                DB::raw('SUM(retur_member_detail.qty_refund) as total_qty_refund'),
                DB::raw('SUM(retur_member_detail.total_refund) as total_nominal_refund')
            )
                ->join('retur_member', 'retur_member_detail.retur_id', '=', 'retur_member.id')
                ->whereBetween(DB::raw('DATE(retur_member.tanggal)'), [$startDate->toDateString(), $endDate->toDateString()])
                ->when(! empty($tokoIds), fn ($q) => $q->whereIn('retur_member.toko_id', $tokoIds))
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
                ->when(! empty($tokoIds), fn ($q) => $q->whereIn('transaksi_kasir.toko_id', $tokoIds))
                ->groupBy('stock_barang.barang_id', 'barang.nama', 'retur_sub.total_qty_refund', 'retur_sub.total_nominal_refund')
                ->orderByDesc('net_terjual')
                ->limit(10)
                ->get()
                ->map(fn ($item) => [
                    'nama_barang' => class_exists('TextGenerate') ? TextGenerate::smartTail($item->nama) : $item->nama,
                    'jumlah' => (int) $item->net_terjual,
                    'total_retur' => (int) $item->total_retur,
                    'total_nilai' => round((float) $item->net_nilai, 2),
                ]);

            // ==========================================
            // 3. QUERY TOP 10 MEMBER
            // ==========================================
            $subqueryReturMember = ReturMemberDetail::select(
                'retur_member_detail.transaksi_kasir_detail_id',
                DB::raw('SUM(retur_member_detail.qty_refund) as total_qty_refund')
            )
                ->join('retur_member', 'retur_member_detail.retur_id', '=', 'retur_member.id')
                ->whereBetween(DB::raw('DATE(retur_member.tanggal)'), [$startDate->toDateString(), $endDate->toDateString()])
                ->when(! empty($tokoIds), fn ($sub) => $sub->whereIn('retur_member.toko_id', $tokoIds))
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
                ->when(! empty($tokoIds), fn ($q) => $q->whereIn('transaksi_kasir.toko_id', $tokoIds))
                ->groupBy('transaksi_kasir.toko_id', 'toko.nama', 'member.id', 'nama_member')
                ->orderByDesc('total_pembayaran_setelah_retur')
                ->limit(10)
                ->get()
                ->map(fn ($item) => [
                    'nama_member' => $item->nama_member,
                    'toko_id' => $item->toko_id, // ✅ Diubah menjadi toko_id
                    'nama_toko' => $item->nama_toko,
                    'total_barang_dibeli' => (int) $item->total_barang_dibeli,
                    'total_pembayaran' => round((float) $item->total_pembayaran, 2),
                    'total_pembayaran_setelah_retur' => round((float) $item->total_pembayaran_setelah_retur, 2),
                ]);

            // ==========================================
            // 4. SUSUN RESPONSE API
            // ==========================================
            $responseData = [
                'periode' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'period_type' => $period,
                ],
                'toko_id' => $idToko,
                'top_barang' => $topBarang,
                'top_member' => $topMember,
            ];

            return $this->success($responseData, 200, 'Data detail top barang dan member berhasil diambil!');

        } catch (\Throwable $th) {
            return $this->error(500, 'Gagal mengambil detail toko', $th->getMessage());
        }
    }

    public function getLaporanToko(Request $request)
    {
        $startDateInput = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDateInput = $request->input('end_date', now()->endOfMonth()->toDateString());
        $period = $request->input('period', 'monthly'); // 'daily', 'monthly', 'yearly'
        $idToko = $request->input('toko_id', 'all');

        $startDate = Carbon::parse($startDateInput)->startOfDay();
        $endDate = Carbon::parse($endDateInput)->endOfDay();

        try {
            // 1. Filter Toko (Sertakan parent_id)
            $queryToko = Toko::select('id', 'singkatan', 'nama', 'parent_id');
            if ($idToko !== 'all') {
                $queryToko->where(function ($q) use ($idToko) {
                    $q->where('id', $idToko)->orWhere('parent_id', $idToko);
                });
            }
            $tokos = $queryToko->get();
            $allTokoIds = $tokos->pluck('id')->toArray();

            // Identifikasi ID mana saja yang menjadi parent dari toko lain dalam database
            $parentIdsList = Toko::whereIn('id', $allTokoIds)
                ->whereHas('children') // atau logika whereNull('parent_id') jika berbasis top-level
                ->pluck('id')
                ->toArray();

            // 2. Query Omset Kasir (KasTransaksi)
            $kasData = KasTransaksi::with('kas')
                ->whereHas('kas', fn ($sub) => $sub->whereIn('toko_id', $allTokoIds))
                ->where('tipe', 'in')
                ->where('kategori', 'Pendapatan Umum')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get()
                ->groupBy(fn ($item) => $item->kas->toko_id ?? null);

            // 3. Query Omset Penjualan Non-Fisik (PNF) Per Toko
            $pnfData = PenjualanNonFisik::whereBetween('created_at', [$startDate, $endDate])
                ->whereHas('createdBy', fn ($sub) => $sub->whereIn('toko_id', $allTokoIds))
                ->selectRaw('created_by, total_harga_jual, created_at')
                ->with('createdBy:id,toko_id')
                ->get()
                ->groupBy(fn ($item) => $item->createdBy->toko_id ?? null);

            // 4. Query Pengurang Retur
            $pengurangPerToko = $this->getKomponenPengurang($startDate, $endDate, 'toko');

            $year = $startDate->year;
            $month = $startDate->month;

            $storesList = [];

            // Summary Variabel Keseluruhan
            $summary = [
                'grand_total' => 0,
                'total_omset_kasir' => 0,
                'total_omset_pnf' => 0,
                'total_biaya_retur' => 0,
                'total_kasbon' => 0,
                'total_transaksi' => 0,
            ];

            // 5. Kalkulasi Per Toko
            foreach ($tokos as $toko) {
                $tokoId = $toko->id;

                // Penanda Parent & Parent ID
                $isParent = is_null($toko->parent_id) || in_array($tokoId, $parentIdsList);

                // Transaksi Kasir
                $txKasirToko = $kasData->get($tokoId, collect());
                $omsetKasirToko = (float) $txKasirToko->sum('total_nominal');
                $jumlahTxKasir = $txKasirToko->count();

                // Transaksi PNF
                $txPnfToko = $pnfData->get($tokoId, collect());
                $omsetPnftoko = (float) $txPnfToko->sum('total_harga_jual');

                // Retur & Kasbon
                $refund = $pengurangPerToko['refund'][$tokoId] ?? 0;
                $keuntungan = $pengurangPerToko['untung'][$tokoId] ?? 0;
                $kerugian = $pengurangPerToko['rugi'][$tokoId] ?? 0;
                $biayaRetur = $refund - $keuntungan + $kerugian;
                $kasbon = 0; // Nilai default

                // Omset Bersih Toko
                $totalOmsetGross = $omsetKasirToko + $omsetPnftoko;
                $totalOmsetBersih = max($totalOmsetGross - $biayaRetur - $kasbon, 0);

                // Grafik Deret Waktu (Time Series)
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

                $storesList[] = [
                    'toko_id' => $toko->id,
                    'singkatan' => $toko->singkatan,
                    'nama_toko' => $toko->nama,
                    'is_parent' => $isParent,       // ✅ Flag Boolean (true jika Induk)
                    'parent_id' => $toko->parent_id, // ✅ ID Toko Induk (null jika top-level)
                    'jumlah_transaksi' => $jumlahTxKasir,
                    'ringkasan_omset' => [
                        'total_omset_bersih' => round($totalOmsetBersih, 2),
                        'omset_kasir' => round($omsetKasirToko, 2),
                        'omset_pnf' => round($omsetPnftoko, 2),
                        'biaya_retur' => round($biayaRetur, 2),
                        'kasbon' => round($kasbon, 2),
                    ],
                    $period => $timeSeriesData,
                    'total_omset' => round($totalOmsetBersih, 2),
                ];

                // Akumulasi ke Summary
                $summary['grand_total'] += $totalOmsetBersih;
                $summary['total_omset_kasir'] += $omsetKasirToko;
                $summary['total_omset_pnf'] += $omsetPnftoko;
                $summary['total_biaya_retur'] += $biayaRetur;
                $summary['total_kasbon'] += $kasbon;
                $summary['total_transaksi'] += $jumlahTxKasir;
            }

            // Rounding Summary
            foreach ($summary as $key => $val) {
                $summary[$key] = round($val, 2);
            }

            $responseData = [
                'periode' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'period_type' => $period,
                ],
                'summary' => $summary,
                'stores' => $storesList,
            ];

            return $this->success($responseData, 200, 'Data laporan toko berhasil diambil!');

        } catch (\Throwable $e) {
            return $this->error(500, 'Gagal mengambil data laporan toko', $e->getMessage());
        }
    }

    public function getKomparasiToko(Request $request)
    {
        // Sesuaikan juga ke start_date & end_date
        $startDate = Carbon::parse($request->input('start_date', now()->toDateString()))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()->toDateString()))->endOfDay();

        try {
            $tokos = Toko::select('id', 'singkatan', 'nama')->get();
            $kasData = KasTransaksi::with('kas')
                ->where('tipe', 'in')
                ->where('kategori', 'Pendapatan Umum')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get()
                ->groupBy(fn ($item) => $item->kas->toko_id ?? null);

            $pengurang = $this->getKomponenPengurang($startDate, $endDate, 'toko');

            $storesComparison = [];
            $totalSemuaToko = 0;

            foreach ($tokos as $toko) {
                $tokoId = $toko->id;
                $txToko = $kasData->get($tokoId, collect());
                $nominalKas = $txToko->sum('total_nominal');

                $refund = $pengurang['refund'][$tokoId] ?? 0;
                $keuntungan = $pengurang['untung'][$tokoId] ?? 0;
                $kerugian = $pengurang['rugi'][$tokoId] ?? 0;

                $totalBersih = $nominalKas - ($refund - $keuntungan + $kerugian);

                $storesComparison[] = [
                    'toko_id' => $toko->id,
                    'singkatan' => $toko->singkatan,
                    'nama_toko' => $toko->nama,
                    'jumlah_transaksi' => $txToko->count(),
                    'total_transaksi' => round($totalBersih, 2),
                ];

                $totalSemuaToko += $totalBersih;
            }

            $responseData = [
                'periode' => ['start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()],
                'stores' => $storesComparison,
                'grand_total' => round($totalSemuaToko, 2),
            ];

            return $this->success($responseData, 200, 'Data komparasi toko berhasil diambil!');

        } catch (\Throwable $th) {
            return $this->error(500, 'Gagal mengambil data komparasi toko', $th->getMessage());
        }
    }

    public function getOmset(Request $request)
    {
        $startDate = Carbon::parse($request->input('startDate', now()->toDateString()))->startOfDay();
        $endDate = Carbon::parse($request->input('endDate', now()->toDateString()))->endOfDay();
        $idToko = $request->input('toko_id', 'all');

        try {
            // 1. Penanganan Filter Toko (Induk + Child)
            $tokoIds = [];
            if ($idToko !== 'all' && ! empty($idToko)) {
                $tokoIds = Toko::where('id', $idToko)->orWhere('parent_id', $idToko)->pluck('id')->toArray();
            }

            // 2. Omset Kasir (Transaksi Kasir)
            $queryKasir = Toko::leftJoin('transaksi_kasir', function ($join) use ($startDate, $endDate) {
                $join->on('toko.id', '=', 'transaksi_kasir.toko_id')
                    ->where('transaksi_kasir.total_qty', '>', 0)
                    ->whereNull('transaksi_kasir.deleted_at')
                    ->whereBetween('transaksi_kasir.tanggal', [$startDate, $endDate]);
            })
                ->when(! empty($tokoIds), fn ($q) => $q->whereIn('toko.id', $tokoIds))
                ->selectRaw('SUM(COALESCE(transaksi_kasir.total_nominal, 0) - COALESCE(transaksi_kasir.total_diskon, 0)) as total_nominal');

            $totalOmsetKasir = (float) ($queryKasir->first()->total_nominal ?? 0);

            // 3. Omset Penjualan Non-Fisik (PNF)
            $queryPNF = PenjualanNonFisik::whereBetween('created_at', [$startDate, $endDate])
                ->when(! empty($tokoIds), function ($q) use ($tokoIds) {
                    $q->whereHas('createdBy', fn ($sub) => $sub->whereIn('toko_id', $tokoIds));
                })
                ->selectRaw('SUM(total_harga_jual) as total_pnf');

            $totalOmsetPNF = (float) ($queryPNF->first()->total_pnf ?? 0);

            $totalOmset = $totalOmsetKasir + $totalOmsetPNF;

            // 4. Retur Member
            $refundReturMember = ReturMemberDetail::where('qty_refund', '>', 0)
                ->whereHas('retur', function ($query) use ($startDate, $endDate, $tokoIds) {
                    $query->whereBetween('tanggal', [$startDate, $endDate])
                        ->when(! empty($tokoIds), fn ($q) => $q->whereIn('toko_id', $tokoIds));
                })
                ->sum('total_refund');

            // 5. Retur Supplier (Untung & Rugi)
            $keuntunganRefundSupplier = ReturSupplierDetail::where('qty_refund', '>', 0)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('keterangan', 'untung')
                ->whereHas('returSupplier', function ($q) use ($tokoIds) {
                    $q->when(! empty($tokoIds), fn ($sub) => $sub->whereIn('toko_id', $tokoIds));
                })
                ->sum('selisih');

            $kerugianRefundSupplier = ReturSupplierDetail::where('qty_refund', '>', 0)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('keterangan', 'rugi')
                ->whereHas('returSupplier', function ($q) use ($tokoIds) {
                    $q->when(! empty($tokoIds), fn ($sub) => $sub->whereIn('toko_id', $tokoIds));
                })
                ->sum('selisih');

            $returTotal = $refundReturMember - $keuntunganRefundSupplier + $kerugianRefundSupplier;
            $totalKasbon = 0; // Siapkan nilai default

            $fixOmset = max($totalOmset - $returTotal - $totalKasbon, 0);

            $responseData = [
                'total' => round($fixOmset, 2),
                'kasbon' => (float) $totalKasbon,
                'biaya_retur' => round((float) $returTotal, 2),
                'omset_kasir' => round($totalOmsetKasir, 2),
                'omset_pnf' => round($totalOmsetPNF, 2),
            ];

            return $this->success($responseData, 200, $totalOmset > 0 ? 'Data omset berhasil diambil' : 'Data omset kosong');

        } catch (\Throwable $th) {
            return $this->error(500, 'Gagal mengambil data omset', $th->getMessage());
        }
    }

    /**
     * Helper privat untuk query pengurang retur (Mencegah duplikasi kode)
     */
    private function getKomponenPengurang($startDate, $endDate, $groupBy = 'bulan')
    {
        $groupColumn = $groupBy === 'bulan' ? 'MONTH(retur_member_detail.created_at)' : 'retur_member.toko_id';
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
