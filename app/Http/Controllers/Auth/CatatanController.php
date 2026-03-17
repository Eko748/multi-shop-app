<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\CatatanService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Traits\HasFilter;
use Illuminate\Validation\ValidationException;
use Exception;

class CatatanController extends Controller
{
    use ApiResponse, HasFilter;

    private array $menu = [];
    protected $service;

    public function __construct(CatatanService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $menu = ['Catatan'];
        $title = 'Catatan';
        return view('catatan.index', compact('menu', 'title'));
    }

    public function get(Request $request)
    {
        try {
            $filter = $this->makeFilter($request, 30, [
                'toko_id' => $request->toko_id
            ]);
            $data = $this->service->getAll($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (Exception $e) {
            return $this->error(500, "Gagal mengambil data", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function post(Request $request)
    {
        try {
            $validated = $request->validate([
                'keterangan' => 'required|string',
                'user_id' => 'required|integer|exists:users,id',
                'toko_id' => 'required|integer|exists:toko,id',
                'toko_tujuan_id' => 'required|integer|exists:toko,id',
            ]);

            $data = $this->service->create($validated);

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
                'id' => 'required|integer|exists:catatan,id',
                'keterangan' => 'required|string',
                'user_id' => 'required|integer|exists:users,id',
                'toko_id' => 'required|integer|exists:toko,id',
                'toko_tujuan_id' => 'required|integer|exists:toko,id',
            ]);

            $data = $this->service->update($validated['id'], $validated);

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

    public function read(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:catatan,id',
                'is_read' => 'required|accepted',
                'user_id' => 'required|integer|exists:users,id',
                'toko_id' => 'required|integer|exists:toko,id',
            ]);

            $data = $this->service->read($validated['id'], $validated);

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
                'id' => 'required|integer|exists:catatan,id',
                'user_id' => 'required|integer|exists:users,id',
                'toko_id' => 'required|integer|exists:toko,id',
            ]);

            $deleted = $this->service->delete($validated['id'], $validated);

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
