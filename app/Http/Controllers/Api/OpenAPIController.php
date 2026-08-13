<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KasTransaksi;
use App\Models\PenjualanNonFisik;
use App\Models\ReturMemberDetail;
use App\Models\ReturSupplierDetail;
use App\Models\Toko;
use App\Traits\ApiResponse; // 1. Import Trait
use Carbon\Carbon;
use Illuminate\Http\Request;

class OpenAPIController extends Controller
{
    use ApiResponse; // 2. Gunakan Trait di sini

    public function getLaporanKasir(Request $request)
    {
        // 1. Parameter menggunakan format snake_case
        $startDateInput = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDateInput = $request->input('end_date', now()->endOfMonth()->toDateString());
        $period = $request->input('period', 'monthly'); // 'daily', 'monthly', 'yearly'
        $idToko = $request->input('toko_id', 'all');

        $startDate = Carbon::parse($startDateInput)->startOfDay();
        $endDate = Carbon::parse($endDateInput)->endOfDay();

        try {
            // 2. Ambil Toko (Semua atau Filter Spesifik + Child)
            $queryToko = Toko::select('id', 'singkatan', 'nama', 'parent_id');
            if ($idToko !== 'all') {
                $queryToko->where(function ($q) use ($idToko) {
                    $q->where('id', $idToko)->orWhere('parent_id', $idToko);
                });
            }
            $tokos = $queryToko->get();
            $allTokoIds = $tokos->pluck('id')->toArray();

            // 3. Query Utama Kas
            $kasData = KasTransaksi::with('kas')
                ->whereHas('kas', fn ($sub) => $sub->whereIn('toko_id', $allTokoIds))
                ->where('tipe', 'in')
                ->where('kategori', 'Pendapatan Umum')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get()
                ->groupBy(fn ($item) => $item->kas->toko_id ?? null);

            // 4. Query Pengurang (Per Toko)
            $pengurangPerToko = $this->getKomponenPengurang($startDate, $endDate, 'toko');

            $year = $startDate->year;
            $month = $startDate->month;

            $storesList = [];
            $grandTotal = 0;

            // 5. Looping Per Toko
            foreach ($tokos as $toko) {
                $tokoId = $toko->id;
                $txToko = $kasData->get($tokoId, collect());

                $refund = $pengurangPerToko['refund'][$tokoId] ?? 0;
                $keuntungan = $pengurangPerToko['untung'][$tokoId] ?? 0;
                $kerugian = $pengurangPerToko['rugi'][$tokoId] ?? 0;
                $netPengurang = $refund - $keuntungan + $kerugian;

                $timeSeriesData = [];
                $totalToko = 0;

                if ($period === 'daily') {
                    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                    $dailyTotals = array_fill(1, $daysInMonth, 0);

                    foreach ($txToko as $data) {
                        $day = (int) Carbon::parse($data->created_at)->format('j');
                        $dailyTotals[$day] += $data->total_nominal;
                    }

                    foreach (range(1, $daysInMonth) as $day) {
                        $dailyTotals[$day] -= ($netPengurang / $daysInMonth);
                    }

                    $timeSeriesData = array_map(fn ($v) => round($v, 2), array_values($dailyTotals));
                    $totalToko = array_sum($timeSeriesData);

                } elseif ($period === 'monthly') {
                    $monthlyTotals = array_fill(1, 12, 0);

                    foreach ($txToko as $data) {
                        $b = (int) Carbon::parse($data->created_at)->format('n');
                        $monthlyTotals[$b] += $data->total_nominal;
                    }

                    $monthlyTotals[$month] -= $netPengurang;

                    $timeSeriesData = array_map(fn ($v) => round($v, 2), array_values($monthlyTotals));
                    $totalToko = array_sum($timeSeriesData);

                } elseif ($period === 'yearly') {
                    $nominalKas = $txToko->sum('total_nominal');
                    $totalToko = round($nominalKas - $netPengurang, 2);
                    $timeSeriesData = [$year => $totalToko];
                }

                $storesList[] = [
                    'toko_id' => $toko->id,
                    'singkatan' => $toko->singkatan,
                    'nama_toko' => $toko->nama,
                    $period => $timeSeriesData,
                    'total_omset' => round($totalToko, 2),
                ];

                $grandTotal += $totalToko;
            }

            $responseData = [
                'periode' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'period_type' => $period,
                ],
                'stores' => $storesList,
                'grand_total' => round($grandTotal, 2),
            ];

            return $this->success($responseData, 200, 'Data laporan kasir berhasil diambil!');

        } catch (\Throwable $e) {
            return $this->error(500, 'Gagal mengambil data laporan kasir', $e->getMessage());
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
