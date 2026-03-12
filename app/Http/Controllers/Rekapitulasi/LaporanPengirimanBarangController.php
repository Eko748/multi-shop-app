<?php

namespace App\Http\Controllers\Rekapitulasi;

use App\Http\Controllers\Controller;
use App\Services\Distribusi\PengirimanBarangService;
use App\Traits\{ApiResponse, HasFilter};
use Exception;
use Illuminate\Http\Request;

class LaporanPengirimanBarangController extends Controller
{
    use ApiResponse, HasFilter;
    private array $menu = [];
    protected $service;

    public function __construct(PengirimanBarangService $service)
    {
        $this->menu;
        $this->title = [
            'Rekap Pengiriman Barang',
        ];
                $this->service = $service;
    }

    public function index(Request $request)
    {
        $menu = [$this->title[0], $this->label[2]];
        return view('laporan.pengiriman.index', compact('menu'));
    }

        public function get(Request $request)
    {
        try {
            $filter = $this->makeFilter(
                $request,
                30,
                [
                    'toko_id' => $request->input('toko_id'),
                ]
            );
            $data = $this->service->getLaporan($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }
}
