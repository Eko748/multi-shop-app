<?php

namespace App\Http\Controllers\Rekapitulasi;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RatingMemberController extends Controller
{

    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Rating Member',
        ];
    }

    public function getMember(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $selectedTokoIds = $request->input('id_toko');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        try {
            // Query utama untuk data member
            $query = Member::select(
                'member.id',
                'member.nama as nama_member',
                'transaksi_kasir.toko_id',
                'toko.nama as nama_toko',
                DB::raw('COUNT(stock_barang.barang_id) as total_trx'),
                DB::raw('SUM(transaksi_kasir_detail.qty) as total_barang_dibeli'),
                DB::raw('SUM(transaksi_kasir_detail.subtotal - COALESCE(transaksi_kasir_detail.diskon,0)) as total_pembayaran'),
                DB::raw('SUM(transaksi_kasir_detail.qty * stock_barang_batch.harga_beli) as total_hpp'),
                DB::raw('SUM(transaksi_kasir_detail.subtotal - COALESCE(transaksi_kasir_detail.diskon,0) - transaksi_kasir_detail.qty * stock_barang_batch.harga_beli) as laba')
            )
                ->join('transaksi_kasir', 'member.id', '=', 'transaksi_kasir.member_id')
                ->join('transaksi_kasir_detail', 'transaksi_kasir.id', '=', 'transaksi_kasir_detail.transaksi_kasir_id')
                ->join('stock_barang_batch', 'transaksi_kasir_detail.stock_barang_batch_id', '=', 'stock_barang_batch.id')
                ->join('stock_barang', 'stock_barang_batch.stock_barang_id', '=', 'stock_barang.id')
                ->join('toko', 'transaksi_kasir.toko_id', '=', 'toko.id');

            // Tambahkan filter toko jika diperlukan
            if (!empty($selectedTokoIds) && $selectedTokoIds !== 'all') {
                $query->where('transaksi_kasir.toko_id', $selectedTokoIds);
            }

            // Tambahkan filter berdasarkan tanggal
            if (!empty($startDate) && !empty($endDate)) {
                $query->whereBetween('transaksi_kasir.created_at', [$startDate, $endDate]);
            }

            // Tambahkan grouping
            $query->groupBy('transaksi_kasir.toko_id', 'toko.nama', 'member.id', 'member.nama');

            // Tambahkan sorting
            $query->orderBy('total_pembayaran', $meta['orderBy']);

            if (!empty($request['search'])) {
                $searchTerm = trim(strtolower($request['search']));

                $query->where(function ($query) use ($searchTerm) {
                    // Pencarian pada kolom langsung
                    $query->orWhereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]);
                    $query->orWhereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]);
                });
            }

            // Eksekusi query dengan pagination
            $dataMember = $query->paginate($meta['limit']);

            // Format data menjadi array yang sesuai
            $mappedData = collect($dataMember->items())->map(function ($item) {
                return [
                    'nama_member' => $item->nama_member,
                    'id_toko' => $item->toko_id,
                    'nama_toko' => $item->nama_toko,
                    'total_trx' => $item->total_trx,
                    'total_barang_dibeli' => $item->total_barang_dibeli,
                    'total_pembayaran' => $item->total_pembayaran,
                    'total_hpp' => $item->total_hpp,
                    'laba' => $item->laba,
                ];
            });

            // Buat metadata pagination
            $paginationMeta = [
                'total' => $dataMember->total(),
                'per_page' => $dataMember->perPage(),
                'current_page' => $dataMember->currentPage(),
                'total_pages' => $dataMember->lastPage(),
            ];

            return response()->json([
                'data' => $mappedData,
                'status_code' => 200,
                'errors' => false,
                'message' => $dataMember->isEmpty() ? 'No data found' : 'Data retrieved successfully',
                'pagination' => $paginationMeta,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => 'Error retrieving data',
                'status_code' => 500,
                'data' => $th->getMessage(),
            ]);
        }
    }


    public function index(Request $request)
    {
        $menu = [$this->title[0], $this->label[2]];

        return view('laporan.ratingmember.index', compact('menu'));
    }
}
