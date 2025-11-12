<?php

namespace App\Http\Controllers\TransaksiDigital;

use App\Http\Controllers\Controller;
use App\Services\ItemNonFisikService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Traits\HasFilter;
use Illuminate\Validation\ValidationException;
use Exception;

class ItemNonFisikController extends Controller
{
    use ApiResponse;
    use HasFilter;

    private array $menu = [];
    protected $service;

    public function __construct(ItemNonFisikService $service)
    {
        $this->menu;
        $this->title = [
            'Item Non Fisik',
        ];
        $this->service = $service;
    }


    public function get(Request $request)
    {
        try {
            $filter = $this->makeFilter($request, 30);

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
                'item_nonfisik_tipe_id' => 'required|string|exists:td_item_nonfisik_tipe,id',
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
                'public_id' => 'required|string|exists:td_item_nonfisik,public_id',
                'nama' => 'required|string|max:25',
                'item_nonfisik_tipe_id' => 'required|string|exists:td_item_nonfisik_tipe,id',
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
                'public_id' => 'required|string|exists:td_item_nonfisik,public_id',
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
