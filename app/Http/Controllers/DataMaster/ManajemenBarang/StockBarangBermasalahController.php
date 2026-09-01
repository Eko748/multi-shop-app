<?php

namespace App\Http\Controllers\DataMaster\ManajemenBarang;

use App\Helpers\RupiahGenerate;
use App\Helpers\TextGenerate;
use App\Http\Controllers\Controller;
use App\Models\StockBarangBermasalah;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StockBarangBermasalahController extends Controller
{
    use ApiResponse;

    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Stok Barang Bermasalah',
        ];
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[0]];

        return view('master.stokBermasalah.index', compact('menu'));
    }

    public function get(Request $request)
    {
        try {
            $limit = min($request->input('limit', 30), 30);
            $tokoId = $request->input('id_toko', $request->input('toko_id'));

            $query = StockBarangBermasalah::with(['batch.stockBarang.barang']);

            if ($tokoId && $tokoId !== 'all' && $tokoId != 0) {
                $query->whereHas('batch', fn ($q) => $q->where('toko_id', $tokoId));
            }

            if (! empty($request->search)) {
                $searchTerm = trim(strtolower($request->search));
                $query->whereHas('batch.stockBarang.barang', function ($q) use ($searchTerm) {
                    $q->whereRaw('LOWER(nama) LIKE ?', ["%{$searchTerm}%"]);
                });
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = $request->start_date.' 00:00:00';
                $endDate = $request->end_date.' 23:59:59';
                $query->whereBetween('created_at', [$startDate, $endDate]);
            } else {
                $query->whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ]);
            }

            $orderDirection = $request->input('ascending', 0) ? 'asc' : 'desc';
            $data = $query->orderBy('created_at', $orderDirection)->paginate($limit);

            if ($data->isEmpty()) {
                return response()->json([
                    'status_code' => 400,
                    'errors' => true,
                    'message' => 'Tidak ada data',
                ], 400);
            }

            $mappedData = collect($data->items())->map(function ($item) {
                $batch = $item->batch;
                $barang = $batch?->stockBarang?->barang;
                $total = (int) ($item->qty ?? 0) * $batch?->harga_beli ?? 0;

                return [
                    'id' => $item->id,
                    'barang_id' => $barang?->id,
                    'nama_barang' => $barang ? TextGenerate::smartTail($barang->nama) : '-',
                    'tanggal_masuk' => $batch?->created_at ? $batch->created_at->format('d-m-Y H:i:s') : '-',
                    'harga_beli' => RupiahGenerate::build($batch?->harga_beli ?? 0),
                    'qty' => (int) ($item->qty ?? 0),
                    'total' => RupiahGenerate::build($total ?? 0),
                    'status' => ($item->status === 'mati') ? 'Mati/Rusak' : ($item->status ?? '-'),
                    'created_at' => $item->created_at ? $item->created_at->format('d-m-Y H:i:s') : '-',
                ];
            });

            return response()->json([
                'status_code' => 200,
                'errors' => false,
                'message' => 'Sukses',
                'data' => $mappedData,
                'pagination' => [
                    'total' => $data->total(),
                    'per_page' => $data->perPage(),
                    'current_page' => $data->currentPage(),
                    'total_pages' => $data->lastPage(),
                ],
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'errors' => true,
                'message' => 'Terjadi kesalahan pada server',
                'data' => $th->getMessage(),
            ], 500);
        }
    }
}
