<?php

namespace App\Http\Controllers\DataMaster\ManajemenBarang;

use App\Http\Controllers\Controller;
use App\Services\StockBarangBatchService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Traits\HasFilter;
use Exception;

class StockBarangBatchController extends Controller
{
    use ApiResponse;
    use HasFilter;

    private array $menu = [];
    protected $service;

    public function __construct(StockBarangBatchService $service)
    {
        $this->menu;
        $this->title = [
            'Stock Barang Batch',
        ];
        $this->service = $service;
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

            $data = $this->service->getAll($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (\Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function getByQR(Request $request)
    {
        try {
            $filter = (object) [
                'search' => $request->input('search'),
                'toko_id' => $request->input('toko_id'),
            ];

            $data = $this->service->getByQR($filter);

            return $this->success($data['data'], 200, 'Berhasil');
        } catch (\Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }
}
