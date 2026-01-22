<?php

namespace App\Http\Controllers\DataMaster\Entitas;

use App\Http\Controllers\Controller;
use App\Services\DataMaster\TokoGroupService;
use Illuminate\Http\Request;
use App\Traits\{ApiResponse, HasFilter};
use Illuminate\Validation\ValidationException;
use Exception;

class TokoGroupController extends Controller
{
    use ApiResponse, HasFilter;

    private array $menu = [];
    protected $service;

    public function __construct(TokoGroupService $service)
    {
        $this->menu;
        $this->title = [
            'Grup Toko',
        ];
        $this->service = $service;
    }

    public function select(Request $request)
    {
        try {
            $filter = $this->makeFilter($request, 30,
            [
                'toko_id' => $request->input('toko_id'),
            ]);
            $data = $this->service->select($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }
}
