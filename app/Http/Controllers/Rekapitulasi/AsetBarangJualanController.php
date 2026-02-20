<?php

namespace App\Http\Controllers\Rekapitulasi;

use App\Http\Controllers\Controller;
use App\Models\Toko;
use App\Services\DompetKategoriService;
use App\Services\DompetSaldoService;
use Illuminate\Http\Request;
use App\Models\StockBarangBatch;
use App\Models\PembelianBarangDetail;

class AsetBarangJualanController extends Controller
{
    private array $menu = [];
    protected $service;
    protected $service2;

    public function __construct(DompetSaldoService $service, DompetKategoriService $service2)
    {
        $this->menu;
        $this->title = [
            'Aset Barang Jualan',
        ];
        $this->service = $service;
        $this->service2 = $service2;
    }

    public function getAssetBarang(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $idTokoLogin = (int) $request->input('toko_id', 0);
        $searchTerm = trim(strtolower($request->input('search', '')));

        try {
            if ($idTokoLogin == 1) {

                $stockQuery = StockBarangBatch::query()
                    ->with([
                        'stockBarang.barang.jenis',
                        'sumber',
                        'toko'
                    ])
                    ->whereHas('stockBarang', function ($q) {
                        $q->whereNull('deleted_at');
                    })
                    ->whereHas('stockBarang.barang', function ($q) {
                        $q->whereNull('deleted_at');
                    })
                    ->where('sumber_type', PembelianBarangDetail::class);

                if (!empty($startDate) && !empty($endDate)) {
                    $stockQuery->whereHas('stockBarang', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('created_at', [$startDate, $endDate]);
                    });
                }

                $batches = $stockQuery->get();

                $combined = $batches->groupBy(function ($item) {
                    return $item->stockBarang->barang->jenis->nama_jenis_barang;
                })->map(function ($items) {

                    $first = $items->first();
                    $jenis = $first->stockBarang->barang->jenis;

                    return (object)[
                        'toko_id' => 1,
                        'nama_toko' => $first->toko->nama,
                        'wilayah' => $first->toko->wilayah,
                        'id_jenis_barang' => $jenis->id,
                        'nama_jenis_barang' => $jenis->nama_jenis_barang,
                        'total_qty' => $items->sum('qty_sisa'),
                        'total_harga' => $items->sum(function ($item) {
                            return $item->qty_sisa * ($item->sumber->harga_beli ?? 0);
                        })
                    ];
                })->values();
            }
            // Grouping berdasarkan jenis_barang
            $grouped = $combined->groupBy('nama_jenis_barang');

            // Hitung total global
            $totalQty = $combined->sum('total_qty');
            $totalHarga = $combined->sum('total_harga');

            // $dompetSaldo = $this->service->getTotalPerKategori((object) ['limit' => null, 'search' => $searchTerm]);
            $hppDompetSaldo = $this->service->sumHPP($idTokoLogin);
            $dompetSaldo = $this->service->sumSisaSaldo($idTokoLogin);
            $dompetKategori = $this->service2->count($idTokoLogin);
            $toko = Toko::where('id', $request->toko_id)->first();

            $finalData = $grouped->map(function ($items, $namaJenis) {
                return [
                    'nama_jenis_barang' => $namaJenis,
                    'items' => $items->map(function ($item) {
                        return [
                            'toko_id' => $item->toko_id,
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

            // tambahkan data tambahan ke paling bawah
            $finalData->push([
                'nama_jenis_barang' => 'Saldo Digital',
                'items' => [[
                    'toko_id' => $toko?->id,
                    'nama_toko' => $toko?->nama . ' (' . $toko->wilayah . ')',
                    'id_jenis_barang' => 'dompet-saldo',
                    'nama_jenis_barang' => 'Dompet Saldo Digital',
                    'total_qty' => $dompetKategori,
                    'total_harga' => $hppDompetSaldo['format'],
                    'keterangan' => 'Sisa Saldo: ' . $dompetSaldo['format']
                ]]
            ]);

            return response()->json([
                'data' => $finalData,
                'total_summary' => [
                    'total_qty' => $totalQty + $dompetKategori,
                    'total_harga' => 'Rp ' . number_format($totalHarga + $hppDompetSaldo['saldo'], 0, ',', '.'),
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

        return view('laporan.asetbarang.index', compact('menu'));
    }
}
