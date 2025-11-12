<?php

namespace App\Http\Controllers\TransaksiDigital;

use App\Http\Controllers\Controller;
use App\Services\ItemNonFisikTipeService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Validation\ValidationException;
use Exception;

class ItemNonFisikTipeController extends Controller
{
    use ApiResponse;

    private array $menu = [];
    protected $service;

    public function __construct(ItemNonFisikTipeService $service)
    {
        $this->menu;
        $this->title = [
            'Tipe Item Non Fisik',
        ];
        $this->service = $service;
    }

    public function get(Request $request)
    {
        try {
            $filter = (object) [
                'limit' => $request->input('limit', 10),
                'search' => $request->input('search'),
                'nama' => $request->input('nama'),
            ];

            $data = $this->service->getAll($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (\Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function getNama(Request $request)
    {
        try {
            $limit = $request->input('limit', 30);
            $search = $request->input('search');

            $data = $this->service->getNama($limit, $search);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (\Exception $e) {
            return $this->error(500, 'Gagal mengambil data {$this->title[0]}', [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function post(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required|string|max:25',
                'created_by' => 'required|integer|exists:users,id',
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
                'public_id' => 'required|string|exists:td_item_nonfisik_tipe,public_id',
                'nama' => 'required|string|max:25',
                'updated_by' => 'required|integer|exists:users,id',
            ]);

            $data = $this->service->update($validated['public_id'], $validated);

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
                'public_id' => 'required|string|exists:td_item_nonfisik_tipe,public_id',
                'deleted_by' => 'required|integer|exists:users,id',
            ]);

            $deleted = $this->service->delete($validated['public_id'], $validated);

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
