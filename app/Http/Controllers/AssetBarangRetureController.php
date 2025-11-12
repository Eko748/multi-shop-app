<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use Illuminate\Http\Request;
use App\Services\DompetKategoriService;
use App\Services\DompetSaldoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssetBarangRetureController extends Controller
{
    private array $menu = [];
    protected $service;
    protected $service2;

    public function __construct(DompetSaldoService $service, DompetKategoriService $service2)
    {
        $this->menu;
        $this->title = [
            'Aset Barang Retur',
        ];
        $this->service = $service;
        $this->service2 = $service2;
    }

    public function getAsetBarangRetur(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $idTokoLogin = (int) $request->input('id_toko', 0);
        $searchTerm = trim(strtolower($request->input('search', '')));

        try {
            if ($idTokoLogin == 1) {
                $stockQuery = DB::table('retur_member_detail as ds')
                    ->join('barang as b', 'ds.barang_id', '=', 'b.id')
                    ->join('jenis_barang as jb', 'b.id_jenis_barang', '=', 'jb.id')
                    ->join('toko as t', 't.id', '=', DB::raw('1'))
                    ->selectRaw('
                        1 as id_toko,
                        t.nama_toko,
                        t.wilayah,
                        b.id_jenis_barang,
                        jb.nama_jenis_barang,
                        SUM(ds.qty_request - COALESCE(ds.qty_ke_supplier, 0)) as total_qty,
                        SUM((ds.qty_request - COALESCE(ds.qty_ke_supplier, 0)) * ds.hpp) as total_harga
                    ')
                    ->whereNull('b.deleted_at')
                    ->groupBy(
                        'b.id_jenis_barang',
                        'jb.nama_jenis_barang',
                        't.nama_toko',
                        't.wilayah'
                    );

                if (!empty($startDate) && !empty($endDate)) {
                    $stockQuery->whereBetween('ds.created_at', [$startDate, $endDate]);
                }

                $stockData = $stockQuery->get();

                $detailQuery = DB::table('detail_toko')
                    ->join('toko', 'detail_toko.id_toko', '=', 'toko.id')
                    ->join('barang', 'detail_toko.id_barang', '=', 'barang.id')
                    ->join('jenis_barang', 'barang.id_jenis_barang', '=', 'jenis_barang.id')
                    ->select(
                        'detail_toko.id_toko',
                        'toko.nama_toko',
                        'toko.wilayah',
                        'barang.id_jenis_barang',
                        'jenis_barang.nama_jenis_barang',
                        DB::raw('SUM(detail_toko.qty) as total_qty'),
                        DB::raw('SUM(detail_toko.harga) as total_harga')
                    )
                    ->whereNull('barang.deleted_at')
                    ->groupBy(
                        'detail_toko.id_toko',
                        'toko.nama_toko',
                        'toko.wilayah',
                        'barang.id_jenis_barang',
                        'jenis_barang.nama_jenis_barang'
                    );

                if (!empty($startDate) && !empty($endDate)) {
                    $detailQuery->whereBetween('detail_toko.created_at', [$startDate, $endDate]);
                }

                if (!empty($searchTerm)) {
                    $detailQuery->where(function ($q) use ($searchTerm) {
                        $q->orWhereRaw('LOWER(toko.nama_toko) LIKE ?', ["%$searchTerm%"]);
                        $q->orWhereRaw('LOWER(toko.wilayah) LIKE ?', ["%$searchTerm%"]);
                        $q->orWhereRaw('LOWER(jenis_barang.nama_jenis_barang) LIKE ?', ["%$searchTerm%"]);
                    });
                }

                $detailData = $detailQuery->get();

                $combined = $stockData->merge($detailData);
            } else {
                $combined = DB::table('detail_toko')
                    ->join('toko', 'detail_toko.id_toko', '=', 'toko.id')
                    ->join('barang', 'detail_toko.id_barang', '=', 'barang.id')
                    ->join('jenis_barang', 'barang.id_jenis_barang', '=', 'jenis_barang.id')
                    ->where('detail_toko.id_toko', $idTokoLogin)
                    ->whereNull('barang.deleted_at')
                    ->select(
                        'detail_toko.id_toko',
                        'toko.nama_toko',
                        'toko.wilayah',
                        'barang.id_jenis_barang',
                        'jenis_barang.nama_jenis_barang',
                        DB::raw('SUM(detail_toko.qty) as total_qty'),
                        DB::raw('SUM(detail_toko.harga) as total_harga')
                    )
                    ->groupBy(
                        'detail_toko.id_toko',
                        'toko.nama_toko',
                        'toko.wilayah',
                        'barang.id_jenis_barang',
                        'jenis_barang.nama_jenis_barang'
                    );

                if (!empty($startDate) && !empty($endDate)) {
                    $combined->whereBetween('detail_toko.created_at', [$startDate, $endDate]);
                }

                if (!empty($searchTerm)) {
                    $combined->where(function ($q) use ($searchTerm) {
                        $q->orWhereRaw('LOWER(toko.nama_toko) LIKE ?', ["%$searchTerm%"]);
                        $q->orWhereRaw('LOWER(toko.wilayah) LIKE ?', ["%$searchTerm%"]);
                        $q->orWhereRaw('LOWER(jenis_barang.nama_jenis_barang) LIKE ?', ["%$searchTerm%"]);
                    });
                }

                $combined = $combined->get();
            }

            // Grouping berdasarkan jenis_barang
            $grouped = $combined->groupBy('nama_jenis_barang');

            // Hitung total global
            $totalQty = $combined->sum('total_qty');
            $totalHarga = $combined->sum('total_harga');

            $finalData = $grouped->map(function ($items, $namaJenis) {
                return [
                    'nama_jenis_barang' => $namaJenis,
                    'items' => $items->map(function ($item) {
                        return [
                            'id_toko' => $item->id_toko,
                            'nama_toko' => $item->nama_toko . ' (' . $item->wilayah . ')',
                            'id_jenis_barang' => $item->id_jenis_barang,
                            'nama_jenis_barang' => $item->nama_jenis_barang,
                            'total_qty' => $item->total_qty,
                            'total_harga' => 'Rp ' . number_format($item->total_harga, 0, ',', '.'),
                            'keterangan' => '-'
                        ];
                    })->values()
                ];
            })->values();

            return response()->json([
                'data' => $finalData,
                'total_summary' => [
                    'total_qty' => $totalQty,
                    'total_harga' => 'Rp ' . number_format($totalHarga, 0, ',', '.'),
                ],
                'status_code' => 200,
                'errors' => false,
                'message' => 'Data retrieved successfully',
                'pagination' => [
                    'total' => $finalData->count(),
                    'per_page' => $meta['limit'],
                    'current_page' => 1,
                    'total_pages' => 1,
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => 'Error retrieving data',
                'status_code' => 500,
                'data' => $th->getMessage(),
            ], 500);
        }
    }


    public function index(Request $request)
    {
        $menu = [$this->title[0], $this->label[2]];

        return view('laporan.asetbarangretur.index', compact('menu'));
    }
}
