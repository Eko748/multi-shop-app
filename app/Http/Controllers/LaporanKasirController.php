<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Services\LaporanPenjualanService;

class LaporanKasirController extends Controller
{
    use ApiResponse;

    protected $service;
    private array $menu = [];

    public function __construct(LaporanPenjualanService $service)
    {
        $this->service = $service;
        $this->menu;
        $this->title = [
            'Laporan Kasir',
        ];
    }

    public function getSalesReport(Request $request)
    {
        try {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $idToko = $request->id_toko;

            if (($startDate && !strtotime($startDate)) || ($endDate && !strtotime($endDate))) {
                return $this->error(422, 'Format tanggal tidak valid');
            }

            $report = $this->service->generateReport($startDate, $endDate, $idToko);

            if (!$report) {
                return $this->error(404, 'Data laporan tidak ditemukan');
            }

            return $this->success($report, 200, 'Laporan penjualan berhasil diambil');
        } catch (\Throwable $e) {

            return $this->error(500, 'Terjadi kesalahan saat mengambil laporan', [
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function index(Request $request)
    {
        $menu = [$this->title[0], $this->label[2]];

        return view('laporan.laporanKasir.index', compact('menu'));
    }
}
