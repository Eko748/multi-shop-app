<?php

namespace App\Http\Controllers\LaporanKeuangan;

use App\Helpers\RupiahGenerate;
use App\Http\Controllers\Controller;
use App\Models\Toko;
use App\Services\DompetSaldoService;
use App\Services\KasService;
use App\Services\LabaRugiService;
use App\Services\NeracaKeuanganService;
use Illuminate\Http\Request;

class NeracaController extends Controller
{
    private array $menu = [];

    protected $labaRugiService;

    protected $kasService;

    protected $dompetSaldoService;

    protected $neracaKeuanganService;

    public function __construct(LabaRugiService $labaRugiService, KasService $kasService, DompetSaldoService $dompetSaldoService, NeracaKeuanganService $neracaKeuanganService)
    {
        $this->menu;
        $this->title = [
            'Neraca',
        ];

        $this->labaRugiService = $labaRugiService;
        $this->kasService = $kasService;
        $this->dompetSaldoService = $dompetSaldoService;
        $this->neracaKeuanganService = $neracaKeuanganService;
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[4]];

        return view('laporankeuangan.neraca.index', compact('menu'));
    }

    public function getNeraca(Request $request)
    {
        try {
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);
            $tokoId = $request->input('toko_id');

            if (empty($tokoId)) {
                $tokos = \App\Models\Toko::all();

                $accumulatedData = null;

                // Inisialisasi struktur note agar sama persis dengan return getStockProblem()
                $accumulatedNote = [
                    'stock_hilang' => ['qty' => 0, 'total_hpp' => 0],
                    'stock_mati' => ['qty' => 0, 'total_hpp' => 0],
                ];

                foreach ($tokos as $toko) {
                    $result = $this->neracaKeuanganService->generateNeraca($month, $year, $toko->id);

                    // Akumulasi langsung nilai stock_hilang
                    if (isset($result['note']['stock_hilang'])) {
                        $accumulatedNote['stock_hilang']['qty'] += (int) ($result['note']['stock_hilang']['qty'] ?? 0);
                        $accumulatedNote['stock_hilang']['total_hpp'] += (int) ($result['note']['stock_hilang']['total_hpp'] ?? 0);
                    }

                    // Akumulasi langsung nilai stock_mati
                    if (isset($result['note']['stock_mati'])) {
                        $accumulatedNote['stock_mati']['qty'] += (int) ($result['note']['stock_mati']['qty'] ?? 0);
                        $accumulatedNote['stock_mati']['total_hpp'] += (int) ($result['note']['stock_mati']['total_hpp'] ?? 0);
                    }

                    if ($accumulatedData === null) {
                        $accumulatedData = $result['data'];
                    } else {
                        $accumulatedData = $this->sumNeracaStructure($accumulatedData, $result['data']);
                    }
                }

                $finalData = $accumulatedData ?? [];
                $finalNote = $accumulatedNote;
            } else {
                $data = $this->neracaKeuanganService->generateNeraca($month, $year, $tokoId);
                $finalData = $data['data'];
                $finalNote = $data['note'];
            }

            return response()->json([
                'data' => $finalData,
                'note' => $finalNote,
                'status_code' => 200,
                'errors' => false,
                'message' => 'Berhasil',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data Tidak Ada',
                'message_back' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'status_code' => 500,
            ], 500);
        }
    }

    private function sumNeracaStructure(array $base, array $incoming): array
    {
        foreach ($base as $i => $category) {
            $base[$i]['total'] += $incoming[$i]['total'] ?? 0;
            $base[$i]['format'] = RupiahGenerate::build($base[$i]['total']);

            foreach ($category['subkategori'] as $j => $subcat) {
                $base[$i]['subkategori'][$j]['total'] += $incoming[$i]['subkategori'][$j]['total'] ?? 0;
                $base[$i]['subkategori'][$j]['format'] = RupiahGenerate::build($base[$i]['subkategori'][$j]['total']);

                // Petakan item incoming berdasarkan 'kode'
                $incomingItemsMap = [];
                if (isset($incoming[$i]['subkategori'][$j]['item'])) {
                    foreach ($incoming[$i]['subkategori'][$j]['item'] as $incItem) {
                        if (isset($incItem['kode'])) {
                            $incomingItemsMap[$incItem['kode']] = $incItem;
                        }
                    }
                }

                // Loop item di base, lalu gabungkan nilai dan format ulang teks qty di dalam kurung
                foreach ($subcat['item'] as $k => $item) {
                    $code = $item['kode'] ?? null;
                    if ($code && isset($incomingItemsMap[$code])) {
                        $incItem = $incomingItemsMap[$code];

                        // 1. Akumulasi nilai nominal rupiah
                        $base[$i]['subkategori'][$j]['item'][$k]['nilai'] += $incItem['nilai'] ?? 0;
                        $base[$i]['subkategori'][$j]['item'][$k]['format'] = RupiahGenerate::build($base[$i]['subkategori'][$j]['item'][$k]['nilai']);

                        // 2. Akumulasi angka di dalam kurung (misal: "Stok Barang (10)" atau "Aksesoris (5)")
                        $baseName = $item['nama'] ?? '';
                        $incName = $incItem['nama'] ?? '';

                        // Ambil angka di dalam kurung dari base dan incoming menggunakan regex
                        preg_match('/\(([\d\.]+)\)$/', $baseName, $baseMatch);
                        preg_match('/\(([\d\.]+)\)$/', $incName, $incMatch);

                        if (!empty($baseMatch) && !empty($incMatch)) {
                            // Bersihkan titik pemisah ribuan jika ada, lalu jadikan integer
                            $baseQty = (int) str_replace('.', '', $baseMatch[1]);
                            $incQty = (int) str_replace('.', '', $incMatch[1]);
                            $totalQty = $baseQty + $incQty;

                            // Ganti bagian teks di dalam kurung dengan total kuantitas yang baru
                            $base[$i]['subkategori'][$j]['item'][$k]['nama'] = preg_replace(
                                '/\([\d\.]+\)$/',
                                '(' . number_format($totalQty, 0, ',', '.') . ')',
                                $baseName
                            );
                        }
                    }
                }
            }
        }

        return $base;
    }
}
