<?php

namespace App\Http\Controllers\LaporanKeuangan;

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
            $tokoId = $request->input('id_toko');

            $data = $this->neracaKeuanganService->generateNeraca($month, $year, $tokoId);

            return response()->json([
                'data' => $data['data'],
                'note' => $data['note'],
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
            ]);
        }
    }
}
