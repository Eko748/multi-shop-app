<?php

namespace App\Http\Controllers\Distribusi;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\DetailKasir;
use App\Models\DetailPengirimanBarang;
use App\Models\DetailToko;
use App\Models\PengirimanBarangDetail;
use App\Models\StockBarang;
use App\Models\StockBarangBatch;
use App\Models\Toko;
use App\Models\TransaksiKasirDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlanOrderController extends Controller
{
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Lokasi dan Riwayat Barang',
            'Tambah Data',
            'Edit Data'
        ];
    }

    public function getplanorder(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $selectedTokoIds = $request->input('toko_id', []);

        if (empty($selectedTokoIds)) {
            $selectedTokoIds = Toko::pluck('id')->toArray();
        }

        // ===============================
        // QUERY BARANG
        // ===============================
        $query = Barang::select('id', 'nama')
            ->orderBy('id', $meta['orderBy']);

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));
            $query->whereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]);
        }

        $data = $query->paginate($meta['limit']);

        $paginationMeta = [
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'total_pages' => $data->lastPage(),
        ];

        $tokoList = Toko::whereIn('id', $selectedTokoIds)
            ->select('id', 'singkatan')
            ->get();

        // ===============================
        // MAP DATA
        // ===============================
        $mappedData = collect($data->items())->map(function ($item) use ($selectedTokoIds, $tokoList) {

            // ===============================
            // 🔥 STOCK GROUPED
            // ===============================
            $stockGrouped = StockBarangBatch::selectRaw('toko_id, SUM(qty_sisa) as total_stock')
                ->whereHas('stockBarang', function ($query) use ($item) {
                    $query->where('barang_id', $item->id);
                })
                ->whereIn('toko_id', $selectedTokoIds)
                ->groupBy('toko_id')
                ->pluck('total_stock', 'toko_id');

            // ===============================
            // 🔥 OTW GROUPED
            // ===============================
            $otwGrouped = PengirimanBarangDetail::selectRaw('pengiriman_barang.toko_asal_id as toko_id, SUM(qty_send) as total_otw')
                ->join('pengiriman_barang', 'pengiriman_barang.id', '=', 'pengiriman_barang_detail.pengiriman_barang_id')
                ->where('pengiriman_barang_detail.barang_id', $item->id)
                ->where('pengiriman_barang.status', '!=', 'success')
                ->whereIn('pengiriman_barang.toko_asal_id', $selectedTokoIds)
                ->groupBy('pengiriman_barang.toko_asal_id')
                ->pluck('total_otw', 'toko_id');

            // ===============================
            // 🔥 LAST ORDER GROUPED
            // ===============================
            $lastOrders = TransaksiKasirDetail::selectRaw('transaksi_kasir.toko_id, MAX(transaksi_kasir_detail.created_at) as last_date')
                ->join('transaksi_kasir', 'transaksi_kasir.id', '=', 'transaksi_kasir_detail.transaksi_kasir_id')
                ->join('stock_barang_batch', 'stock_barang_batch.id', '=', 'transaksi_kasir_detail.stock_barang_batch_id')
                ->join('stock_barang', 'stock_barang.id', '=', 'stock_barang_batch.stock_barang_id')
                ->where('stock_barang.barang_id', $item->id)
                ->whereIn('transaksi_kasir.toko_id', $selectedTokoIds)
                ->groupBy('transaksi_kasir.toko_id')
                ->pluck('last_date', 'toko_id');

            // ===============================
            // BUILD RESPONSE PER TOKO
            // ===============================
            $stokPerToko = $tokoList->mapWithKeys(function ($tk) use ($stockGrouped, $otwGrouped, $lastOrders) {

                $stock = $stockGrouped[$tk->id] ?? 0;
                $otw   = $otwGrouped[$tk->id] ?? 0;

                $lo = isset($lastOrders[$tk->id])
                    ? abs(now()->startOfDay()->diffInDays(
                        \Carbon\Carbon::parse($lastOrders[$tk->id])->startOfDay()
                    ))
                    : null;

                return [
                    $tk->singkatan => [
                        'stock' => $stock,
                        'otw'   => $otw,
                        'lo'    => $lo,
                    ]
                ];
            });

            return [
                'id' => $item->id,
                'nama_barang' => $item->nama,
                'stok_per_toko' => $stokPerToko,
            ];
        });

        return response()->json([
            "error" => false,
            "message" => $mappedData->isEmpty() ? "No data found" : "Data retrieved successfully",
            "status_code" => 200,
            "pagination" => $paginationMeta,
            "data" => $mappedData,
            "data_toko" => $tokoList,
        ]);
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[6]];

        return view('master.planorder.index', compact('menu'));
    }
}
