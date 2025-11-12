<?php

namespace App\Http\Controllers\LaporanKeuangan;

use App\Http\Controllers\Controller;
use App\Services\NeracaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Validation\ValidationException;
use Exception;

class NeracaPenyesuaianController extends Controller
{
    use ApiResponse;

    private array $menu = [];
    protected $neracaService;

    public function __construct(NeracaService $neracaService)
    {
        $this->menu;
        $this->title = [
            'Penyesuaian Neraca',
        ];
        $this->neracaService = $neracaService;
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[4]];

        return view('laporankeuangan.neracaPenyesuaian.index', compact('menu'));
    }

    public function get(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $year = $request->input('year');
            $month = $request->input('month');

            $data = $this->neracaService->getAll($limit, $year, $month);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (\Exception $e) {
            return $this->error(500, 'Gagal mengambil data neraca penyesuaian', [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function post(Request $request)
    {
        try {
            $validated = $request->validate([
                'nilai' => 'required|numeric',
                'tanggal' => 'required|date',
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $tanggal = new \Carbon\Carbon($validated['tanggal']);
            $bulan = $tanggal->format('m');
            $tahun = $tanggal->format('Y');

            $existing = $this->neracaService->model()
                ->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $bulan)
                ->exists();

            if ($existing) {
                return $this->error(422, "Data bulan {$bulan} tahun {$tahun} sudah ada, silahkan edit data tersebut.");
            }

            $data = $this->neracaService->create($validated);

            return $this->success($data, 201, 'Data berhasil ditambahkan');
        } catch (ValidationException $e) {
            return $this->error(422, 'Validation Error', $e->errors());
        } catch (Exception $e) {
            return $this->error(500, 'Internal Server Error', $e->getMessage());
        }
    }

    public function put(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:neraca_penyesuaian,id',
                'nilai' => 'required|numeric',
                'tanggal' => 'required|date',
                'user_id' => 'required|integer|exists:users,id',
            ]);

            $data = $this->neracaService->update($validated['id'], $validated);

            if (!$data) {
                return $this->error(404, 'Data not found');
            }

            return $this->success($data, 200, 'Data berhasil diperbarui');
        } catch (ValidationException $e) {
            return $this->error(422, 'Validation Error', $e->errors());
        } catch (Exception $e) {
            return $this->error(500, 'Internal Server Error', $e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:neraca_penyesuaian,id',
            ]);

            $deleted = $this->neracaService->delete($validated['id']);

            if (!$deleted) {
                return $this->error(404, 'Data not found');
            }

            return $this->success(null, 200, 'Data berhasil dihapus');
        } catch (ValidationException $e) {
            return $this->error(422, 'Validation Error', $e->errors());
        } catch (Exception $e) {
            return $this->error(500, 'Internal Server Error', $e->getMessage());
        }
    }
}
