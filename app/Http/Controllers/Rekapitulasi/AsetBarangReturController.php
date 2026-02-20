<?php

namespace App\Http\Controllers\Rekapitulasi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DompetKategoriService;
use App\Services\DompetSaldoService;
use Illuminate\Support\Facades\DB;

class AsetBarangReturController extends Controller
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
        $idTokoLogin = (int) $request->input('toko_id', 0);
        $searchTerm = trim(strtolower($request->input('search', '')));

        try {
            if ($idTokoLogin == 1) {
                $stockQuery = DB::table('retur_member_detail as ds')
                    ->join('barang as b', 'ds.barang_id', '=', 'b.id')
                    ->join('jenis_barang as jb', 'b.jenis_barang_id', '=', 'jb.id')
                    ->join('toko as t', 't.id', '=', DB::raw('1'))
                    ->selectRaw('
                        1 as toko_id,
                        t.nama,
                        t.wilayah,
                        b.jenis_barang_id,
                        jb.nama_jenis_barang,
                        SUM(ds.qty_request - COALESCE(ds.qty_ke_supplier, 0)) as total_qty,
                        SUM((ds.qty_request - COALESCE(ds.qty_ke_supplier, 0)) * ds.hpp) as total_harga
                    ')
                    ->whereNull('b.deleted_at')
                    ->groupBy(
                        'b.jenis_barang_id',
                        'jb.nama_jenis_barang',
                        't.nama',
                        't.wilayah'
                    );

                if (!empty($startDate) && !empty($endDate)) {
                    $stockQuery->whereBetween('ds.created_at', [$startDate, $endDate]);
                }

                $stockData = $stockQuery->get();

                $combined = $stockData;
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
                            'toko_id' => $item->toko_id,
                            'nama_toko' => $item->nama . ' (' . $item->wilayah . ')',
                            'id_jenis_barang' => $item->jenis_barang_id,
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
