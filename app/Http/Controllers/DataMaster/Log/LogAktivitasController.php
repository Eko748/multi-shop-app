<?php

namespace App\Http\Controllers\DataMaster\Log;

use App\Http\Controllers\Controller;
use App\Services\LogAktivitasService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Exception;

class LogAktivitasController extends Controller
{
    use ApiResponse;

    private array $menu = [];
    protected $service;

    public function __construct(LogAktivitasService $service)
    {
        $this->menu;
        $this->title = [
            'Log Aktivitas',
        ];
        $this->service = $service;
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[0]];

        return view('master.logAktivitas.index', compact('menu'));
    }

    public function get(Request $request)
    {
        try {
            $filter = (object) [
                'limit' => $request->input('limit', 10),
                'search' => $request->input('search'),
                'log_event' => $request->input('log_event'),
            ];

            $data = $this->service->getAll($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (\Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function getLogEvent(Request $request)
    {
        try {
            $limit = $request->input('limit', 30);
            $search = $request->input('search');

            $data = $this->service->getLogEvent($limit, $search);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (\Exception $e) {
            return $this->error(500, 'Gagal mengambil data {$this->title[0]}', [
                'exception' => $e->getMessage()
            ]);
        }
    }
}
