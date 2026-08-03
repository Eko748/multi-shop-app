<?php

namespace App\Http\Controllers\LaporanKeuangan;

use App\Http\Controllers\Controller;
use App\Models\KasTransaksi;
use App\Services\ArusKasService;
use Illuminate\Http\Request;

class ArusKasController extends Controller
{
    private array $menu = [];

    protected $arusKasService;

    public function __construct(ArusKasService $arusKasService)
    {
        $this->menu;
        $this->title = [
            'Arus Kas',
        ];

        $this->arusKasService = $arusKasService;
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[4]];

        return view('laporankeuangan.aruskas.index', compact('menu'));
    }

    public function getArusKas(Request $request)
    {
        try {
            $result = $this->arusKasService->getArusKasData($request);

            // 🔥 Jika data kosong
            if (empty($result['data']) || count($result['data']) === 0) {
                return response()->json([
                    'data' => $result['data'],
                    'data_total' => $result['data_total'],
                    'status_code' => 200,
                    'errors' => false,
                    'message' => 'Data Bulan ini Belum ada',
                ], 200);
            }

            return response()->json([
                'data' => $result['data'],
                'data_total' => $result['data_total'],
                'status_code' => 200,
                'errors' => false,
                'message' => 'Berhasil',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data Tidak Ada',
                'message_back' => $th->getMessage(),
                'status_code' => 500,
            ]);
        }
    }

    public function getKategori(Request $request)
    {
        try {
            $meta['orderBy'] = $request->input('ascending', 0) ? 'asc' : 'desc';
            $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? (int) $request->limit : 30;

            // Ambil data DISTINCT pada kolom kategori
            $query = KasTransaksi::query()
                ->with('kas')
                ->select('kategori')
                ->whereNotNull('kategori')
                ->where('kategori', '!=', '')
                ->distinct();

            // Filter Toko jika diperlukan
            if ($request->filled('toko_id') && $request->toko_id !== 'all') {
                $query->whereHas('kas', function ($q) use ($request) {
                    $q->where('toko_id', $request->toko_id);
                });
            }

            // Filter Search berdasarkan nama kategori
            if ($request->filled('search')) {
                $searchTerm = trim(strtolower($request->search));
                $query->whereRaw('LOWER(kategori) LIKE ?', ["%{$searchTerm}%"]);
            }

            $query->orderBy('kategori', $meta['orderBy']);

            // Menggunakan pagination
            $data = $query->paginate($meta['limit']);

            if ($data->isEmpty()) {
                return response()->json([
                    'status_code' => 400,
                    'errors' => true,
                    'message' => 'Tidak ada data',
                ], 400);
            }

            // Format data: id & text berisi nama kategori
            $mappedData = collect($data->items())->map(function ($item) {
                return [
                    'id' => $item->kategori,
                    'text' => $item->kategori,
                ];
            });

            return response()->json([
                'status_code' => 200,
                'errors' => false,
                'message' => 'Berhasil',
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
                'message' => 'Terjadi kesalahan server',
                'data' => $th->getMessage(),
            ], 500);
        }
    }
}
