<?php

namespace App\Http\Controllers\Api;

use App\Helpers\TextGenerate;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\PenjualanNonFisikDetail;
use App\Models\ReturMemberDetail;
use App\Models\ReturSupplierDetail;
use App\Models\Toko;
use App\Models\User;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Models\StockBarangBatch;
use App\Models\TransaksiKasirDetail;
use App\Services\NeracaKeuanganService;
use App\Services\StockBulananService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

class OpenAPIController extends Controller
{
    use ApiResponse;

    protected $neracaKeuanganService;

    public function __construct(NeracaKeuanganService $neracaKeuanganService)
    {
        $this->neracaKeuanganService = $neracaKeuanganService;
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'toko_id'  => 'nullable|integer|exists:toko,id',
        ]);

        try {
            $user = User::where('username', $request->username)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->error(401, 'Username atau password salah.');
            }

            $user->update([
                'ip_login' => $request->ip(),
                'last_activity' => Carbon::now(),
            ]);

            $isSuperAdmin = ((int) $user->role_id === 1);
            $showTokoSelection = false;
            $activeTokoId = $user->toko_id;
            $daftarToko = collect();

            if ($isSuperAdmin) {
                $daftarToko = Toko::select('id', 'nama', 'singkatan', 'alamat', 'mitra', 'parent_id')->get();
                if ($daftarToko->count() > 1) {
                    if ($request->filled('toko_id')) {
                        $activeTokoId = $request->toko_id;
                    } else {
                        $showTokoSelection = true;
                    }
                }
            } elseif (!is_null($user->toko_id)) {
                $daftarToko = Toko::select('id', 'nama', 'singkatan', 'alamat', 'mitra', 'parent_id')
                    ->where('id', $user->toko_id)
                    ->get();
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->success(
                data: [
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'show_toko_selection' => $showTokoSelection,
                    'active_toko_id' => $activeTokoId,
                    'daftar_toko' => $showTokoSelection ? $daftarToko : [],
                    'user' => [
                        'id' => $user->id,
                        'nama' => $user->nama,
                        'username' => $user->username,
                        'role_id' => $user->role_id,
                        'nama_role' => $user->role ? $user->role->name : null,
                        'toko_id' => $user->toko_id,
                    ]
                ],
                code: 200,
                message: 'Login berhasil'
            );
        } catch (\Throwable $e) {
            return $this->error(500, 'Gagal melakukan proses login', $e->getMessage());
        }
    }

    public function logout(Request $request)
    {
        try {
            $token = $request->user()?->currentAccessToken();

            if ($token instanceof PersonalAccessToken) {
                $token->delete();
            }

            return $this->success(null, 200, 'Logout berhasil.');
        } catch (\Throwable $e) {
            return $this->error(500, 'Gagal melakukan logout', $e->getMessage());
        }
    }
    public function getNeraca(Request $request)
    {
        try {
            $month = $request->input('month', now()->month);
            $year  = $request->input('year', now()->year);

            $tokos = Toko::select('id', 'parent_id', 'nama', 'singkatan', 'wilayah', 'alamat', 'mitra')->get();

            $data = $tokos->map(function ($toko) use ($month, $year) {
                return [
                    'toko'   => $toko,
                    'neraca' => $this->neracaKeuanganService->generateNeraca($month, $year, $toko->id)
                ];
            });

            return $this->success($data, 200, 'Data laporan keuangan seluruh toko berhasil diambil!');
        } catch (\Throwable $e) {
            return $this->error(500, 'Gagal mengambil data laporan keuangan', $e->getMessage());
        }
    }

    public function getLaporanToko(Request $request)
    {
        $startDateInput = $request->input('start_date', now()->toDateString());
        $endDateInput   = $request->input('end_date', now()->toDateString());
        $period         = $request->input('period', 'daily');
        $idToko         = $request->input('toko_id', 'all');

        $startDate = Carbon::parse($startDateInput)->startOfDay();
        $endDate   = Carbon::parse($endDateInput)->endOfDay();

        try {
            $queryToko = Toko::select('id', 'singkatan', 'nama', 'parent_id');
            if ($idToko !== 'all') {
                $queryToko->where(function ($q) use ($idToko) {
                    $q->where('id', $idToko)->orWhere('parent_id', $idToko);
                });
            }
            $tokos = $queryToko->get();
            $allTokoIds = $tokos->pluck('id')->toArray();

            $parentIdsList = Toko::whereIn('id', $allTokoIds)
                ->whereHas('children')
                ->pluck('id')
                ->toArray();

            $kasirData = TransaksiKasirDetail::join('transaksi_kasir', 'transaksi_kasir.id', '=', 'transaksi_kasir_detail.transaksi_kasir_id')
                ->join('stock_barang_batch', 'stock_barang_batch.id', '=', 'transaksi_kasir_detail.stock_barang_batch_id')
                ->whereIn('transaksi_kasir.toko_id', $allTokoIds)
                ->where('transaksi_kasir.total_qty', '>', 0)
                ->whereNull('transaksi_kasir.deleted_at')
                ->whereBetween('transaksi_kasir.tanggal', [$startDate, $endDate])
                ->selectRaw('
                    transaksi_kasir.toko_id,
                    DATE(transaksi_kasir.tanggal) as tanggal_tx,
                    SUM(transaksi_kasir_detail.subtotal) as total_penjualan,
                    SUM(stock_barang_batch.harga_beli * transaksi_kasir_detail.qty) as total_hpp,
                    COUNT(DISTINCT transaksi_kasir.id) as total_tx
                ')
                ->groupBy('transaksi_kasir.toko_id', DB::raw('DATE(transaksi_kasir.tanggal)'))
                ->get()
                ->groupBy('toko_id');

            $pnfData = PenjualanNonFisikDetail::join('td_penjualan_nonfisik', 'td_penjualan_nonfisik.id', '=', 'td_penjualan_nonfisik_detail.penjualan_nonfisik_id')
                ->join('users', 'users.id', '=', 'td_penjualan_nonfisik.created_by')
                ->whereBetween('td_penjualan_nonfisik_detail.created_at', [$startDate, $endDate])
                ->whereIn('users.toko_id', $allTokoIds)
                ->selectRaw('
                    users.toko_id,
                    DATE(td_penjualan_nonfisik_detail.created_at) as tanggal_tx,
                    SUM(td_penjualan_nonfisik_detail.harga_jual * td_penjualan_nonfisik_detail.qty) as total_penjualan,
                    SUM(td_penjualan_nonfisik_detail.hpp * td_penjualan_nonfisik_detail.qty) as total_hpp
                ')
                ->groupBy('users.toko_id', DB::raw('DATE(td_penjualan_nonfisik_detail.created_at)'))
                ->get()
                ->groupBy('toko_id');

            $pengurangPerToko = $this->getKomponenPengurang($startDate, $endDate, 'toko');

            $labaReturMemberPerToko = ReturMemberDetail::join('retur_member', 'retur_member_detail.retur_id', '=', 'retur_member.id')
                ->where('retur_member_detail.qty_refund', '>', 0)
                ->whereBetween('retur_member.tanggal', [$startDate, $endDate])
                ->whereIn('retur_member.toko_id', $allTokoIds)
                ->selectRaw('retur_member.toko_id, SUM(retur_member_detail.total_refund - retur_member_detail.total_hpp) as laba')
                ->groupBy('retur_member.toko_id')
                ->pluck('laba', 'toko_id');

            $storesList = [];
            $summary = [
                'grand_total'       => 0,
                'total_laba_kotor'  => 0,
                'total_hpp'         => 0,
                'total_omset_kasir' => 0,
                'total_omset_pnf'   => 0,
                'total_biaya_retur' => 0,
                'total_kasbon'      => 0,
                'total_transaksi'   => 0,
            ];

            $datePeriod = CarbonPeriod::create($startDate->toDateString(), $endDate->toDateString());

            foreach ($tokos as $toko) {
                $tokoId = $toko->id;
                $isParent = is_null($toko->parent_id) || in_array($tokoId, $parentIdsList);

                $txKasirToko = $kasirData->get($tokoId, collect());
                $omsetKasirToko = (float) $txKasirToko->sum('total_penjualan');
                $hppKasirToko   = (float) $txKasirToko->sum('total_hpp');
                $jumlahTxKasir  = (int) $txKasirToko->sum('total_tx');

                $txPnfToko = $pnfData->get($tokoId, collect());
                $omsetPnfToko = (float) $txPnfToko->sum('total_penjualan');
                $hppPnfToko   = (float) $txPnfToko->sum('total_hpp');

                $refundMember       = $pengurangPerToko['refund'][$tokoId] ?? 0;
                $keuntunganSupplier = $pengurangPerToko['untung'][$tokoId] ?? 0;
                $kerugianSupplier   = $pengurangPerToko['rugi'][$tokoId] ?? 0;
                $biayaRetur         = max($refundMember - $keuntunganSupplier + $kerugianSupplier, 0);
                $kasbon             = 0;

                $labaReturMember = (float) ($labaReturMemberPerToko[$tokoId] ?? 0);
                $returLabaTotal  = $labaReturMember - $keuntunganSupplier + $kerugianSupplier;

                $totalPenjualan   = $omsetKasirToko + $omsetPnfToko;
                $totalHpp         = $hppKasirToko + $hppPnfToko;
                $totalOmsetBersih = max($totalPenjualan - $biayaRetur - $kasbon, 0);
                $labaKotor        = max($totalPenjualan - $totalHpp - $returLabaTotal, 0);

                $timeSeriesData = [];

                if ($period === 'daily') {
                    $mapPerDate = [];
                    foreach ($txKasirToko as $row) {
                        $mapPerDate[$row->tanggal_tx] = ($mapPerDate[$row->tanggal_tx] ?? 0) + $row->total_penjualan;
                    }
                    foreach ($txPnfToko as $row) {
                        $mapPerDate[$row->tanggal_tx] = ($mapPerDate[$row->tanggal_tx] ?? 0) + $row->total_penjualan;
                    }

                    $dailyTotals = [];
                    foreach ($datePeriod as $dt) {
                        $keyDate = $dt->toDateString();
                        $omsetHariIni = $mapPerDate[$keyDate] ?? 0;

                        if ($totalPenjualan > 0 && ($biayaRetur + $kasbon) > 0) {
                            $proporsi = $omsetHariIni / $totalPenjualan;
                            $omsetHariIni = max($omsetHariIni - ($proporsi * ($biayaRetur + $kasbon)), 0);
                        }

                        $dailyTotals[] = round($omsetHariIni, 2);
                    }
                    $timeSeriesData = $dailyTotals;
                } elseif ($period === 'monthly') {
                    $monthlyTotals = array_fill(0, 12, 0);
                    foreach ($txKasirToko as $row) {
                        $monthIndex = (int) Carbon::parse($row->tanggal_tx)->format('n') - 1;
                        $monthlyTotals[$monthIndex] += $row->total_penjualan;
                    }
                    foreach ($txPnfToko as $row) {
                        $monthIndex = (int) Carbon::parse($row->tanggal_tx)->format('n') - 1;
                        $monthlyTotals[$monthIndex] += $row->total_penjualan;
                    }

                    $activeMonthIdx = $startDate->month - 1;
                    $monthlyTotals[$activeMonthIdx] = max($monthlyTotals[$activeMonthIdx] - ($biayaRetur + $kasbon), 0);
                    $timeSeriesData = array_map(fn($v) => round($v, 2), $monthlyTotals);
                } elseif ($period === 'yearly') {
                    $timeSeriesData = [$startDate->year => round($totalOmsetBersih, 2)];
                }

                $storesList[] = [
                    'toko_id'          => $toko->id,
                    'singkatan'        => $toko->singkatan,
                    'nama_toko'        => $toko->nama,
                    'is_parent'        => $isParent,
                    'parent_id'        => $toko->parent_id,
                    'jumlah_transaksi' => $jumlahTxKasir,
                    'ringkasan_omset'  => [
                        'total_omset_bersih' => round($totalOmsetBersih, 2),
                        'laba_kotor'         => round($labaKotor, 2),
                        'total_hpp'          => round($totalHpp, 2),
                        'omset_kasir'        => round($omsetKasirToko, 2),
                        'omset_pnf'          => round($omsetPnfToko, 2),
                        'biaya_retur'        => round($biayaRetur, 2),
                        'kasbon'             => round($kasbon, 2),
                    ],
                    $period            => $timeSeriesData,
                    'total_omset'      => round($totalOmsetBersih, 2),
                ];

                $summary['grand_total']       += $totalOmsetBersih;
                $summary['total_laba_kotor']  += $labaKotor;
                $summary['total_hpp']         += $totalHpp;
                $summary['total_omset_kasir'] += $omsetKasirToko;
                $summary['total_omset_pnf']   += $omsetPnfToko;
                $summary['total_biaya_retur'] += $biayaRetur;
                $summary['total_kasbon']      += $kasbon;
                $summary['total_transaksi']   += $jumlahTxKasir;
            }

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

    public function getTopMemberBarang(Request $request)
    {
        $idToko = $request->input('toko_id', 'all');
        $startDateInput = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDateInput = $request->input('end_date', now()->endOfMonth()->toDateString());
        $period = $request->input('period', 'monthly');

        $startDate = Carbon::parse($startDateInput)->startOfDay();
        $endDate = Carbon::parse($endDateInput)->endOfDay();

        try {
            $tokoIds = [];
            if ($idToko !== 'all' && ! empty($idToko)) {
                $tokoIds = Toko::where('id', $idToko)->orWhere('parent_id', $idToko)->pluck('id')->toArray();
            }

            $subqueryReturBarang = ReturMemberDetail::select(
                'retur_member_detail.barang_id',
                DB::raw('SUM(retur_member_detail.qty_refund) as total_qty_refund'),
                DB::raw('SUM(retur_member_detail.total_refund) as total_nominal_refund')
            )
                ->join('retur_member', 'retur_member_detail.retur_id', '=', 'retur_member.id')
                ->whereBetween(DB::raw('DATE(retur_member.tanggal)'), [$startDate->toDateString(), $endDate->toDateString()])
                ->when(! empty($tokoIds), fn($q) => $q->whereIn('retur_member.toko_id', $tokoIds))
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
                ->leftJoinSub($subqueryReturBarang, 'retur_sub', fn($join) => $join->on('stock_barang.barang_id', '=', 'retur_sub.barang_id'))
                ->where('transaksi_kasir.total_qty', '>', 0)
                ->whereNull('transaksi_kasir.deleted_at')
                ->whereBetween(DB::raw('DATE(transaksi_kasir.tanggal)'), [$startDate->toDateString(), $endDate->toDateString()])
                ->when(! empty($tokoIds), fn($q) => $q->whereIn('transaksi_kasir.toko_id', $tokoIds))
                ->groupBy('stock_barang.barang_id', 'barang.nama', 'retur_sub.total_qty_refund', 'retur_sub.total_nominal_refund')
                ->orderByDesc('net_terjual')
                ->limit(10)
                ->get()
                ->map(fn($item) => [
                    'nama_barang' => class_exists('TextGenerate') ? TextGenerate::smartTail($item->nama) : $item->nama,
                    'jumlah' => (int) $item->net_terjual,
                    'total_retur' => (int) $item->total_retur,
                    'total_nilai' => round((float) $item->net_nilai, 2),
                ]);

            $subqueryReturMember = ReturMemberDetail::select(
                'retur_member_detail.transaksi_kasir_detail_id',
                DB::raw('SUM(retur_member_detail.qty_refund) as total_qty_refund')
            )
                ->join('retur_member', 'retur_member_detail.retur_id', '=', 'retur_member.id')
                ->whereBetween(DB::raw('DATE(retur_member.tanggal)'), [$startDate->toDateString(), $endDate->toDateString()])
                ->when(! empty($tokoIds), fn($sub) => $sub->whereIn('retur_member.toko_id', $tokoIds))
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
                ->when(! empty($tokoIds), fn($q) => $q->whereIn('transaksi_kasir.toko_id', $tokoIds))
                ->groupBy('transaksi_kasir.toko_id', 'toko.nama', 'member.id', 'nama_member')
                ->orderByDesc('total_pembayaran_setelah_retur')
                ->limit(10)
                ->get()
                ->map(fn($item) => [
                    'nama_member' => $item->nama_member,
                    'toko_id' => $item->toko_id,
                    'nama_toko' => $item->nama_toko,
                    'total_barang_dibeli' => (int) $item->total_barang_dibeli,
                    'total_pembayaran' => round((float) $item->total_pembayaran, 2),
                    'total_pembayaran_setelah_retur' => round((float) $item->total_pembayaran_setelah_retur, 2),
                ]);

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

    public function scanOpnameItem(Request $request)
    {
        $request->validate([
            'toko_id' => 'required|integer|exists:toko,id',
            'qrcode' => 'required|string',
        ]);

        try {
            $barang = \App\Models\Barang::where('qrcode', $request->qrcode)->first();

            if (!$barang) {
                return $this->error(404, 'Barang dengan QR Code tersebut tidak ditemukan.');
            }

            $stockBarang = \App\Models\StockBarang::where('barang_id', $barang->id)
                ->where('toko_group_id', $request->toko_id)
                ->first();

            $batches = [];
            if ($stockBarang) {
                $batches = \App\Models\StockBarangBatch::where('stock_barang_id', $stockBarang->id)
                    ->where('toko_id', $request->toko_id)
                    ->where('qty_sisa', '>', 0)
                    ->get()
                    ->map(fn($batch) => [
                        'stock_barang_batch_id' => $batch->id,
                        'qty_sisa' => $batch->qty_sisa,
                        'harga_beli' => $batch->harga_beli,
                        'tanggal' => $batch->tanggal,
                    ]);
            }

            $data = [
                'barang_id' => $barang->id,
                'nama_barang' => $barang->nama,
                'qrcode' => $barang->qrcode,
                'barcode' => $barang->barcode,
                'total_stok_sistem' => $stockBarang ? $stockBarang->stok : 0,
                'batches' => $batches,
            ];

            return $this->success($data, 200, 'Data barang berhasil di-scan.');
        } catch (\Throwable $e) {
            return $this->error(500, 'Gagal memproses scan barang', $e->getMessage());
        }
    }

    public function storeOpname(Request $request)
    {
        $request->validate([
            'toko_id' => 'required|integer|exists:toko,id',
            'user_id' => 'required|integer|exists:users,id',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.stock_barang_batch_id' => 'required|integer|exists:stock_barang_batch,id',
            'items.*.qty_fisik' => 'required|integer|min:0',
            'items.*.status_kondisi' => 'required|in:normal,mati,rusak,hilang',
            'items.*.catatan_item' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $opname = StockOpname::create([
                'toko_id' => $request->toko_id,
                'user_id' => $request->user_id,
                'tanggal' => Carbon::parse($request->tanggal)->toDateString(),
                'status' => 'selesai',
                'keterangan' => $request->keterangan,
            ]);

            foreach ($request->items as $item) {
                $batch = StockBarangBatch::find($item['stock_barang_batch_id']);
                if (!$batch) {
                    continue;
                }

                $qtySistem = $batch->qty_sisa;
                $qtyFisik = $item['qty_fisik'];
                $selisih = $qtyFisik - $qtySistem;

                StockOpnameDetail::create([
                    'stock_opname_id' => $opname->id,
                    'stock_barang_batch_id' => $batch->id,
                    'qty_sistem' => $qtySistem,
                    'qty_fisik' => $qtyFisik,
                    'selisih' => $selisih,
                    'status_kondisi' => $item['status_kondisi'],
                    'catatan_item' => $item['catatan_item'] ?? null,
                ]);
            }

            DB::commit();

            return $this->success([
                'stock_opname_id' => $opname->id,
                'total_item' => count($request->items),
            ], 200, 'Data stok opname berhasil disimpan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error(500, 'Gagal menyimpan data stok opname', $e->getMessage());
        }
    }

    private function getKomponenPengurang($startDate, $endDate, $groupBy = 'toko')
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
            ->groupBy('key_group')->pluck('key_group')->toArray();

        return ['refund' => $refund, 'untung' => $untung, 'rugi' => $rugi];
    }
}
