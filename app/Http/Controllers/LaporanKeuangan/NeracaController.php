<?php

namespace App\Http\Controllers\LaporanKeuangan;

use App\Helpers\RupiahGenerate;
use App\Http\Controllers\Controller;
use App\Models\DetailPembelianBarang;
use App\Models\DetailRetur;
use App\Models\Hutang;
use App\Models\NeracaPenyesuaian;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\ReturMemberDetail;
use App\Models\ReturSupplier;
use App\Models\ReturSupplierDetail;
use App\Models\StockBarangBermasalah;
use App\Models\Toko;
use App\Services\ArusKasService;
use App\Services\DompetSaldoService;
use App\Services\KasService;
use App\Services\LabaRugiService;
use App\Services\NeracaKeuanganService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            $year  = $request->input('year', now()->year);
            $tokoId = $request->input('toko_id');

            if (empty($tokoId)) {
                $tokos = \App\Models\Toko::all();

                $accumulatedData = null;
                $accumulatedNote = [
                    'stock_hilang' => [
                        'qty' => 0,
                        'total_hpp' => 0,
                    ]
                ];

                foreach ($tokos as $toko) {
                    $result = $this->neracaKeuanganService->generateNeraca($month, $year, $toko->id);

                    // Akumulasikan note jika strukturnya memiliki stock_hilang
                    if (!empty($result['note']['stock_hilang'])) {
                        $accumulatedNote['stock_hilang']['qty'] += (int) ($result['note']['stock_hilang']['qty'] ?? 0);
                        $accumulatedNote['stock_hilang']['total_hpp'] += (int) ($result['note']['stock_hilang']['total_hpp'] ?? 0);
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
                'message' => 'Berhasil'
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

    /**
     * Helper privat untuk menjumlahkan dua struktur neraca agar format tetap konsisten.
     */
    private function sumNeracaStructure(array $base, array $incoming): array
    {
        foreach ($base as $i => $category) {
            $base[$i]['total'] += $incoming[$i]['total'] ?? 0;
            $base[$i]['format'] = RupiahGenerate::build($base[$i]['total']);

            foreach ($category['subkategori'] as $j => $subcat) {
                $base[$i]['subkategori'][$j]['total'] += $incoming[$i]['subkategori'][$j]['total'] ?? 0;
                $base[$i]['subkategori'][$j]['format'] = RupiahGenerate::build($base[$i]['subkategori'][$j]['total']);

                // Akumulasi nilai item jika memiliki kode/pos yang sama
                foreach ($subcat['item'] as $k => $item) {
                    $incItem = $incoming[$i]['subkategori'][$j]['item'][$k] ?? null;
                    if ($incItem && isset($item['nilai'])) {
                        $base[$i]['subkategori'][$j]['item'][$k]['nilai'] += $incItem['nilai'] ?? 0;
                        $base[$i]['subkategori'][$j]['item'][$k]['format'] = RupiahGenerate::build($base[$i]['subkategori'][$j]['item'][$k]['nilai']);
                    }
                }
            }
        }

        return $base;
    }
}
