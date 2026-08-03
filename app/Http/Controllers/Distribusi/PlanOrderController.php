<?php

namespace App\Http\Controllers\Distribusi;

use App\Helpers\TextGenerate;
use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\PengirimanBarangDetail;
use App\Models\StockBarangBatch;
use App\Models\Toko;
use App\Models\TransaksiKasirDetail;
use Illuminate\Http\Request;

class PlanOrderController extends Controller
{
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

    public function getplanorder(Request $request)
    {
        // 1. Direction Sorting (asc / desc)
        $orderDirection = strtolower($request->input('order', $request->ascending ? 'asc' : 'desc'));
        if (! in_array($orderDirection, ['asc', 'desc'])) {
            $orderDirection = 'desc';
        }

        $limit = $request->has('limit') && $request->limit <= 30 ? (int) $request->limit : 10;
        $selectedTokoIds = $request->input('toko_id', []);

        if (empty($selectedTokoIds)) {
            $selectedTokoIds = Toko::pluck('id')->toArray();
        }

        $tokoList = Toko::whereIn('id', $selectedTokoIds)
            ->select('id', 'singkatan', 'nama')
            ->get();

        // =========================================================
        // 2. QUERY AGGREGATION GLOBAL (Bebas N+1 Query)
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

        $lastOrders = TransaksiKasirDetail::selectRaw('stock_barang.barang_id, transaksi_kasir.toko_id, MAX(transaksi_kasir_detail.created_at) as last_date')
            ->join('transaksi_kasir', 'transaksi_kasir.id', '=', 'transaksi_kasir_detail.transaksi_kasir_id')
            ->join('stock_barang_batch', 'stock_barang_batch.id', '=', 'transaksi_kasir_detail.stock_barang_batch_id')
            ->join('stock_barang', 'stock_barang.id', '=', 'stock_barang_batch.stock_barang_id')
            ->whereIn('transaksi_kasir.toko_id', $selectedTokoIds)
            ->groupBy('stock_barang.barang_id', 'transaksi_kasir.toko_id')
            ->get()
            ->groupBy('barang_id');

        // =========================================================
        // 3. FETCH BARANG & KALKULASI STOK PER TOKO
        // =========================================================
        $queryBarang = Barang::select('id', 'nama');

        if (! empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));
            $queryBarang->whereRaw('LOWER(nama) LIKE ?', ["%$searchTerm%"]);
        }

        $allBarang = $queryBarang->get();

        $calculatedCollection = $allBarang->map(function ($item) use ($tokoList, $stockGrouped, $otwGrouped, $lastOrders) {
            $bStock = $stockGrouped->get($item->id, collect())->keyBy('toko_id');
            $bOtw = $otwGrouped->get($item->id, collect())->keyBy('toko_id');
            $bLo = $lastOrders->get($item->id, collect())->keyBy('toko_id');

            $grandTotalStock = 0;

            $stokPerToko = $tokoList->mapWithKeys(function ($tk) use ($bStock, $bOtw, $bLo, &$grandTotalStock) {
                $stock = (int) ($bStock->get($tk->id)->total_stock ?? 0);
                $otw = (int) ($bOtw->get($tk->id)->total_otw ?? 0);
                $loRaw = $bLo->get($tk->id)->last_date ?? null;

                $grandTotalStock += $stock;

                $lo = $loRaw ? abs(now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($loRaw)->startOfDay())) : null;

                return [
                    $tk->singkatan => [
                        'toko_id' => $tk->id,
                        'stock' => $stock,
                        'otw' => $otw,
                        'lo' => $lo,
                    ],
                ];
            });

            return [
                'id' => $item->id,
                'nama_barang' => TextGenerate::smartTail($item->nama),
                'grand_total_stock' => $grandTotalStock,
                'stok_per_toko' => $stokPerToko,
            ];
        });

        // =========================================================
        // 4. LOGIKA SORTING
        // =========================================================
        $sortBy = $request->input('sort_by');   // 'stock', 'otw', atau 'lo'
        $sortToko = $request->input('sort_toko'); // Misal: 'PST', 'CRB'

        if ($sortBy && $sortToko) {
            // Sort spesifik per toko dan jenis kolom
            $calculatedCollection = $calculatedCollection->sortBy(function ($item) use ($sortBy, $sortToko) {
                $val = $item['stok_per_toko'][$sortToko][$sortBy] ?? null;
                if ($val === null) {
                    return PHP_INT_MAX; // Nilai null ditaruh di paling akhir
                }

                return $val;
            }, SORT_REGULAR, $orderDirection === 'desc');
        } else {
            // DEFAULT: Mengurutkan dari total stok terbanyak semua toko di atas
            $calculatedCollection = $calculatedCollection->sortBy('grand_total_stock', SORT_REGULAR, $orderDirection === 'desc');
        }

        // =========================================================
        // 5. MANUAL PAGINATION RESULT
        // =========================================================
        $page = (int) $request->input('page', 1);
        $totalRecords = $calculatedCollection->count();
        $totalPages = (int) ceil($totalRecords / $limit);
        $pagedData = $calculatedCollection->slice(($page - 1) * $limit, $limit)->values();

        $paginationMeta = [
            'total' => $totalRecords,
            'per_page' => $limit,
            'current_page' => $page,
            'total_pages' => $totalPages,
        ];

        return response()->json([
            'error' => false,
            'message' => $pagedData->isEmpty() ? 'Tidak ada data' : 'Berhasil mengambil data',
            'status_code' => 200,
            'pagination' => $paginationMeta,
            'data' => $pagedData,
            'data_toko' => $tokoList,
        ]);
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[6]];

        return view('master.planorder.index', compact('menu'));
    }
}
