<?php

namespace App\Http\Controllers\Rekapitulasi;

use App\Helpers\RupiahGenerate;
use App\Http\Controllers\Controller;
use App\Models\PembelianBarangDetail;
use App\Models\PengirimanBarangDetail;
use App\Models\StockBarangBatch;
use App\Models\Toko;
use App\Services\DompetKategoriService;
use App\Services\DompetSaldoService;
use Illuminate\Http\Request;

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
        $idTokoLogin = $request->input('toko_id');
        $searchTerm = trim(strtolower($request->input('search', '')));

        try {
            $isAllToko = empty($idTokoLogin) || $idTokoLogin === 'all' || $idTokoLogin === 'ALL' || $idTokoLogin == 0;
            $tokoIds = [];
            $toko = null;

            if (!$isAllToko) {
                $toko = Toko::find($idTokoLogin);

                if (! $toko) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Toko tidak ditemukan',
                        'status_code' => 404,
                    ], 404);
                }

                if ($toko->parent_id === null) {
                    $tokoIds = Toko::where('parent_id', $toko->id)
                        ->orWhere('id', $toko->id)
                        ->pluck('id')
                        ->toArray();
                } else {
                    $tokoIds = [$toko->id];
                }
            }

            $stockQuery = StockBarangBatch::query()
                ->with([
                    'stockBarang.barang.jenis',
                    'sumber',
                    'toko',
                ])
                ->when(!$isAllToko, function ($query) use ($tokoIds) {
                    $query->whereIn('toko_id', $tokoIds);
                })
                ->whereHas('stockBarang', function ($q) {
                    $q->whereNull('deleted_at');
                })
                ->whereHas('stockBarang.barang', function ($q) {
                    $q->whereNull('deleted_at');
                })
                ->whereIn('sumber_type', [PembelianBarangDetail::class, PengirimanBarangDetail::class]);

            if (! empty($startDate) && ! empty($endDate)) {
                $stockQuery->whereHas('stockBarang', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                });
            }

            $batches = $stockQuery->get();

            $combined = $batches->groupBy(function ($item) {
                return $item->stockBarang->barang->jenis->id.'-'.$item->toko_id;
            })->map(function ($items) {

                $first = $items->first();
                $jenis = $first->stockBarang->barang->jenis;

                return (object) [
                    'toko_id' => $first->toko->id,
                    'nama_toko' => $first->toko->nama,
                    'wilayah' => $first->toko->wilayah,
                    'id_jenis_barang' => $jenis->id,
                    'nama_jenis_barang' => $jenis->nama_jenis_barang,
                    'total_qty' => $items->sum('qty_sisa'),
                    'total_harga' => $items->sum(function ($item) {
                        return $item->qty_sisa * ($item->harga_beli ?? 0);
                    }),
                ];
            })->values();

            // Grouping berdasarkan jenis_barang
            $grouped = $combined->groupBy('nama_jenis_barang');

            $totalQty = $combined->sum('total_qty');
            $totalHarga = $combined->sum('total_harga');

            $filterTokoService = $isAllToko ? null : $idTokoLogin;

            $hppDompetSaldo = $this->service->sumHPP(null, null, $filterTokoService);

            // SIMPAN NILAI SALDO
            $dompetSaldoRaw = $this->service->sumSisaSaldo(null, null, $filterTokoService);

            // Validasi tipe data: Jika mengembalikan array pakai nilainya, jika angka/float langsung pakai angkanya
            if (is_array($dompetSaldoRaw)) {
                $dompetSaldoVal = (float) ($dompetSaldoRaw['saldo'] ?? 0);
                $dompetSaldoFormatted = $dompetSaldoRaw['format'] ?? RupiahGenerate::build($dompetSaldoVal);
            } else {
                $dompetSaldoVal = (float) $dompetSaldoRaw;
                $dompetSaldoFormatted = RupiahGenerate::build($dompetSaldoVal);
            }

            $dompetKategori = $this->service2->count($filterTokoService);

            $finalData = $grouped->map(function ($items, $namaJenis) {
                return [
                    'nama_jenis_barang' => $namaJenis,
                    'items' => $items->map(function ($item) {
                        return [
                            'toko_id' => $item->toko_id,
                            'nama_toko' => $item->nama_toko.' ('.$item->wilayah.')',
                            'id_jenis_barang' => $item->id_jenis_barang,
                            'nama_jenis_barang' => $item->nama_jenis_barang,
                            'total_qty' => $item->total_qty,
                            'total_harga' => RupiahGenerate::build($item->total_harga),
                            'keterangan' => '-',
                        ];
                    })->values(),
                ];
            })->values();

            // Tambahan saldo digital
            $namaTokoLabel = $isAllToko ? 'Semua Toko' : ($toko->nama.' ('.$toko->wilayah.')');
            $tokoIdValue = $isAllToko ? 'all' : $toko->id;

            $finalData->push([
                'nama_jenis_barang' => 'Saldo Digital',
                'items' => [[
                    'toko_id' => $tokoIdValue,
                    'nama_toko' => $namaTokoLabel,
                    'id_jenis_barang' => 'dompet-saldo',
                    'nama_jenis_barang' => 'Dompet Saldo Digital',
                    'total_qty' => $dompetKategori,
                    'total_harga' => $dompetSaldoFormatted,
                    'keterangan' => '-',
                ]],
            ]);

            return response()->json([
                'data' => $finalData,
                'total_summary' => [
                    'total_qty' => $totalQty + $dompetKategori,
                    'total_harga' => RupiahGenerate::build($totalHarga + $dompetSaldoVal),
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
