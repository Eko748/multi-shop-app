<?php

namespace App\Http\Controllers\LaporanKeuangan;

use App\Http\Controllers\Controller;
use App\Services\LabaRugiService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LabaRugiController extends Controller
{
    private LabaRugiService $labaRugiService;

    public function __construct(LabaRugiService $labaRugiService)
    {
        $this->labaRugiService = $labaRugiService;
        $this->title = ['Laba Rugi'];
    }

    public function index()
    {
        $menu = [$this->title[0]];

        return view('laporankeuangan.labarugi.index', compact('menu'));
    }

    public function getlabarugi(Request $request)
    {
        try {
            $month = $request->get('month', Carbon::now()->month);
            $year  = $request->get('year', Carbon::now()->year);

            $hasil = $this->labaRugiService->hitungDetailLabaRugi($month, $year, $request->toko_id);

            return response()->json([
                'error'   => false,
                'message' => 'Data Laba Rugi berhasil didapatkan',
                'status'  => 200,
                'data'    => $hasil,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error'   => true,
                'message' => 'Gagal mendapatkan data Laba Rugi: ' . $e->getMessage(),
                'status'  => 500,
                'data'    => null,
            ]);
        }
    }
}
