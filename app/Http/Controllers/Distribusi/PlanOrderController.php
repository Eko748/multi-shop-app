<?php

namespace App\Http\Controllers\Distribusi;

use App\Helpers\RupiahGenerate;
use App\Helpers\TextGenerate;
use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\PengirimanBarangDetail;
use App\Models\PlanOrder;
use App\Models\StockBarangBatch;
use App\Models\Toko;
use App\Models\TransaksiKasirDetail;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanOrderController extends Controller
{
    use ApiResponse;

    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Lokasi dan Riwayat Barang',
            'Tambah Data',
            'Edit Data',
        ];
    }

    public function indexPlanorder()
    {
        $menu = ['Plan Order', 'Rekapitulasi'];

        return view('laporan.planorder.index', compact('menu'));
    }

    public function delete(Request $request)
    {
        try {
            // 1. Validasi Input Parameter
            $request->validate([
                'id' => 'required|exists:plan_order,id',
            ]);

            $id = $request->input('id');
            $tokoId = $request->input('toko_id');

            // 2. Query Data Plan Order
            $query = PlanOrder::where('id', $id);

            // Kunci berdasarkan toko_id jika user bukan Admin / Toko Utama (id != 1)
            if (! empty($tokoId) && $tokoId != 1) {
                $query->where('toko_id', $tokoId);
            }

            $planOrder = $query->first();

            if (! $planOrder) {
                return response()->json([
                    'status_code' => 404,
                    'error' => true,
                    'message' => 'Data Plan Order tidak ditemukan atau Anda tidak memiliki akses untuk menghapusnya.',
                ], 404);
            }

            // 3. Eksekusi Hapus Data
            $kodePlan = $planOrder->kode_plan;
            $planOrder->delete();

            // 4. Response JSON Sukses
            return response()->json([
                'status_code' => 200,
                'error' => false,
                'message' => "Plan Order {$kodePlan} berhasil dihapus.",
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'error' => true,
                'message' => 'Gagal menghapus Plan Order: '.$th->getMessage(),
            ], 500);
        }
    }

    public function get(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? (int) $request->limit : 30;

        // 1. Inisialisasi Query dengan Eager Loading Relasi Toko
        $query = PlanOrder::with('toko')->orderBy('created_at', $meta['orderBy']);

        // Filter Toko (Jika BUKAN Admin / Toko Utama id = 1)
        if ($request->toko_id != 1) {
            $query->where('toko_id', $request->toko_id);
        }

        // Filter Multiple Toko (Khusus Admin / Toko Utama)
        if ($request->filled('toko') && $request->toko_id == 1) {
            $query->whereIn('toko_id', (array) $request->input('toko'));
        }

        // Filter Tanggal
        if ($request->has('startDate') && $request->has('endDate')) {
            $query->whereBetween('created_at', [$request->input('startDate'), $request->input('endDate')]);
        }

        // Filter Pencarian (Kode Plan atau Nama Toko)
        if (! empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(kode_plan) LIKE ?', ["\%$searchTerm%"])
                    ->orWhereHas('toko', fn ($t) => $t->whereRaw('LOWER(nama) LIKE ?', ["\%$searchTerm%"]));
            });
        }

        // 2. Kalkulasi Total Sebelum Pagination
        $planList = $query->get();

        if ($planList->isEmpty()) {
            return $this->error(404, 'Tidak ada data');
        }

        $totalGrandTotal = $planList->sum('grand_total');

        // Hitung akumulasi total item yang dipesan dari kolom JSON
        $totalItemKeseluruhan = $planList->sum(function ($plan) {
            return collect($plan->items)->sum('qty');
        });

        // 3. Ambil Master Data Barang Berdasarkan `barang_id` yang Ada di Dalam JSON Items
        $allBarangIds = $planList->pluck('items')
            ->flatten(1)
            ->pluck('barang_id')
            ->filter()
            ->unique();

        $barangMap = Barang::whereIn('id', $allBarangIds)->pluck('nama', 'id');

        // 4. Pagination
        $data = $query->paginate($meta['limit']);

        $paginationMeta = [
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'total_pages' => $data->lastPage(),
        ];

        // 5. Mapping Output Data
        $mappedData = collect($data->items())->map(function ($item) use ($barangMap) {
            // Gabungkan data JSON items dengan Nama Barang dari tabel barang
            $formattedItems = collect($item->items)->map(function ($subItem) use ($barangMap) {
                $barangId = $subItem['barang_id'] ?? null;

                return [
                    'barang_id' => $barangId,
                    'nama_barang' => $barangMap[$barangId] ?? 'Barang Tidak Ditemukan',
                    'qty' => (int) ($subItem['qty'] ?? 0),                 'hpp' => (float) ($subItem['hpp'] ?? 0),
                    'total_hpp' => (float) ($subItem['total_hpp'] ?? 0),                 'total_hpp_rp' => RupiahGenerate::build($subItem['total_hpp'] ?? 0),
                ];
            });

            return [
                'id' => $item->id,
                'toko_id' => $item->toko_id,
                'nama_toko' => optional($item->toko)->nama ?? '-',
                'kode_plan' => $item->kode_plan,
                'total_item' => $formattedItems->sum('qty'),
                'grand_total' => (float) $item->grand_total,
                'grand_total_rp' => RupiahGenerate::build($item->grand_total),
                'tanggal' => $item->created_at ? $item->created_at->format('d-m-Y H:i:s') : '-',
                'items' => $formattedItems,
            ];
        });

        return $this->success(
            [
                'data' => $mappedData,
                'total_grand_total' => 'Rp. '.number_format($totalGrandTotal, 0, '.', '.'),
                'total_item_keseluruhan' => $totalItemKeseluruhan,         ], 200, 'Sukses', $paginationMeta
        );
    }

    public function post(Request $request)
    {
        $request->validate([
            'toko_id' => 'required|exists:toko,id', // Memastikan toko_id ada di tabel toko
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required|exists:barang,id', // Memastikan barang_id ada di tabel barang
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.hpp' => 'required|numeric|min:0',
            'items.*.total_hpp' => 'required|numeric|min:0',
        ]);

        try {
            $grandTotal = collect($request->items)->sum('total_hpp');

            $planOrder = PlanOrder::create([
                'toko_id' => $request->toko_id,
                'kode_plan' => 'PO-'.date('YmdHis').'-'.strtoupper(Str::random(3)),
                'grand_total' => $grandTotal,
                'items' => $request->items,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Plan Order berhasil disimpan',
                'data' => $planOrder,
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan Plan Order: '.$th->getMessage(),
            ], 500);
        }
    }

    public function getplanorder(Request $request)
    {
        try {
            $orderDirection = strtolower($request->input('order', 'desc'));
            if (! in_array($orderDirection, ['asc', 'desc'])) {
                $orderDirection = 'desc';
            }

            $limit = $request->has('limit') && $request->limit <= 50 ? (int) $request->limit : 10;
            $page = (int) $request->input('page', 1);

            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            $hasDate = ! empty($startDate) && ! empty($endDate);

            $selectedTokoIds = $request->input('toko_id', []);
            if (empty($selectedTokoIds)) {
                $selectedTokoIds = Toko::pluck('id')->toArray();
            }

            $tokoList = Toko::whereIn('id', $selectedTokoIds)
                ->select('id', 'singkatan', 'nama')
                ->get();

            // =========================================================
            // 2. QUERY AGGREGATION GLOBAL (Stock, OTW, Last Order, Terjual)
            // =========================================================
            $stockGrouped = StockBarangBatch::selectRaw('stock_barang.barang_id, stock_barang_batch.toko_id, SUM(qty_sisa) as total_stock')
                ->join('stock_barang', 'stock_barang.id', '=', 'stock_barang_batch.stock_barang_id')
                ->whereIn('stock_barang_batch.toko_id', $selectedTokoIds)
                ->groupBy('stock_barang.barang_id', 'stock_barang_batch.toko_id')
                ->get()
                ->groupBy('barang_id');

            $otwGrouped = PengirimanBarangDetail::selectRaw('pengiriman_barang_detail.barang_id, pengiriman_barang.toko_asal_id as toko_id, SUM(qty_send) as total_otw')
                ->join('pengiriman_barang', 'pengiriman_barang.id', '=', 'pengiriman_barang_detail.pengiriman_barang_id')
                ->where('pengiriman_barang.status', '!=', 'success')
                ->whereIn('pengiriman_barang.toko_asal_id', $selectedTokoIds)
                ->groupBy('pengiriman_barang_detail.barang_id', 'pengiriman_barang.toko_asal_id')
                ->get()
                ->groupBy('barang_id');

            $lastOrders = TransaksiKasirDetail::selectRaw('stock_barang.barang_id, transaksi_kasir.toko_id, MAX(transaksi_kasir.tanggal) as last_date')
                ->join('transaksi_kasir', 'transaksi_kasir.id', '=', 'transaksi_kasir_detail.transaksi_kasir_id')
                ->join('stock_barang_batch', 'stock_barang_batch.id', '=', 'transaksi_kasir_detail.stock_barang_batch_id')
                ->join('stock_barang', 'stock_barang.id', '=', 'stock_barang_batch.stock_barang_id')
                ->whereIn('transaksi_kasir.toko_id', $selectedTokoIds)
                ->groupBy('stock_barang.barang_id', 'transaksi_kasir.toko_id')
                ->get()
                ->groupBy('barang_id');

            $terjualQuery = TransaksiKasirDetail::selectRaw('stock_barang.barang_id, transaksi_kasir.toko_id, SUM(transaksi_kasir_detail.qty - COALESCE(retur_member_detail.qty_request,0)) as net_terjual')
                ->join('transaksi_kasir', 'transaksi_kasir.id', '=', 'transaksi_kasir_detail.transaksi_kasir_id')
                ->join('stock_barang_batch', 'stock_barang_batch.id', '=', 'transaksi_kasir_detail.stock_barang_batch_id')
                ->join('stock_barang', 'stock_barang.id', '=', 'stock_barang_batch.stock_barang_id')
                ->leftJoin('retur_member_detail', function ($join) {
                    $join->on('transaksi_kasir_detail.id', '=', 'retur_member_detail.transaksi_kasir_detail_id');
                })
                ->whereIn('transaksi_kasir.toko_id', $selectedTokoIds);

            if ($hasDate) {
                $terjualQuery->whereBetween('transaksi_kasir_detail.created_at', [$startDate, $endDate]);
            } else {
                $terjualQuery->whereDate('transaksi_kasir_detail.created_at', \Carbon\Carbon::today());
            }

            $terjualGrouped = $terjualQuery->groupBy('stock_barang.barang_id', 'transaksi_kasir.toko_id')
                ->get()
                ->groupBy('barang_id');

            // =========================================================
            // 3. FETCH BARANG DENGAN FILTER SEARCH
            // =========================================================
            $queryBarang = Barang::select('id', 'nama');

            if ($request->filled('search')) {
                $searchTerm = trim(strtolower($request->search));
                $queryBarang->whereRaw('LOWER(nama) LIKE ?', ["%{$searchTerm}%"]);
            }

            $allBarang = $queryBarang->get();

            // =========================================================
            // 4. MAP & HITUNG DATA
            // =========================================================
            $nowStartOfDay = now()->startOfDay();

            $calculatedCollection = $allBarang->map(function ($item) use ($tokoList, $stockGrouped, $otwGrouped, $lastOrders, $terjualGrouped, $nowStartOfDay) {
                $bStock = $stockGrouped->get($item->id, collect())->keyBy('toko_id');
                $bOtw = $otwGrouped->get($item->id, collect())->keyBy('toko_id');
                $bLo = $lastOrders->get($item->id, collect())->keyBy('toko_id');
                $bTerjual = $terjualGrouped->get($item->id, collect())->keyBy('toko_id');

                $grandTotalStock = 0;
                $allOtw = 0;
                $allTerjual = 0;
                $allLoList = [];

                $stokPerToko = $tokoList->mapWithKeys(function ($tk) use ($bStock, $bOtw, $bLo, $bTerjual, &$grandTotalStock, &$allOtw, &$allTerjual, &$allLoList, $nowStartOfDay) {
                    $stock = (int) ($bStock->get($tk->id)->total_stock ?? 0);
                    $otw = (int) ($bOtw->get($tk->id)->total_otw ?? 0);
                    $loRaw = $bLo->get($tk->id)->last_date ?? null;
                    $terjual = (int) ($bTerjual->get($tk->id)->net_terjual ?? 0);

                    $grandTotalStock += $stock;
                    $allOtw += $otw;
                    $allTerjual += $terjual;

                    // Hitung selisih hari Last Order
                    $lo = $loRaw ? (int) abs($nowStartOfDay->diffInDays(\Carbon\Carbon::parse($loRaw)->startOfDay())) : null;
                    if ($lo !== null) {
                        $allLoList[] = $lo;
                    }

                    return [
                        $tk->singkatan => [
                            'toko_id' => $tk->id,
                            'stock' => $stock,
                            'otw' => $otw,
                            'lo' => $lo,
                            'terjual' => $terjual,
                        ],
                    ];
                });

                // Ambil selisih hari terkecil (paling baru ada transaksi di antara semua toko)
                $allLo = ! empty($allLoList) ? min($allLoList) : null;

                // Gabungkan ALL di paling depan
                $stokPerTokoCombined = collect([
                    'ALL' => [
                        'toko_id' => null,
                        'stock' => $grandTotalStock,
                        'otw' => $allOtw,
                        'lo' => $allLo,
                        'terjual' => $allTerjual,
                    ],
                ])->merge($stokPerToko);

                return [
                    'id' => $item->id,
                    'nama_barang' => TextGenerate::smartTail($item->nama),
                    'grand_total_stock' => $grandTotalStock,
                    'stok_per_toko' => $stokPerTokoCombined,
                ];
            });

            // =========================================================
            // 5. STABLE SORTING LOGIC
            // =========================================================
            $sortBy = $request->input('sort_by');   // 'stock', 'otw', 'lo', 'terjual'
            $sortToko = $request->input('sort_toko'); // 'ALL', 'PST', 'CRB', dll.

            $isDesc = ($orderDirection === 'desc');

            $sortedCollection = $calculatedCollection->sort(function ($a, $b) use ($sortBy, $sortToko, $isDesc) {
                if ($sortBy && $sortToko) {
                    $valA = $a['stok_per_toko'][$sortToko][$sortBy] ?? null;
                    $valB = $b['stok_per_toko'][$sortToko][$sortBy] ?? null;

                    if ($valA === null && $valB === null) {
                        $cmp = 0;
                    } elseif ($valA === null) {
                        return 1;
                    } elseif ($valB === null) {
                        return -1;
                    } else {
                        $cmp = $valA <=> $valB;
                    }
                } else {
                    $cmp = $a['grand_total_stock'] <=> $b['grand_total_stock'];
                }

                if ($cmp === 0) {
                    return $a['id'] <=> $b['id'];
                }

                return $isDesc ? -$cmp : $cmp;
            });

            // =========================================================
            // 6. PAGINATION RESPONSE
            // =========================================================
            $totalRecords = $sortedCollection->count();
            $totalPages = (int) ceil($totalRecords / $limit);
            $pagedData = $sortedCollection->slice(($page - 1) * $limit, $limit)->values();

            return response()->json([
                'error' => false,
                'message' => $pagedData->isEmpty() ? 'Tidak ada data' : 'Berhasil mengambil data',
                'status_code' => 200,
                'pagination' => [
                    'total' => $totalRecords,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                ],
                'data' => $pagedData,
                'data_toko' => $tokoList,
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => 'Server Error: '.$th->getMessage(),
                'status_code' => 500,
            ], 500);
        }
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[6]];

        return view('master.planorder.index', compact('menu'));
    }
}
