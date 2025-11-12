<?php

namespace App\Http\Controllers\TransaksiDigital;

use App\Http\Controllers\Controller;
use App\Services\ItemNonFisikHargaService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Validation\ValidationException;
use Exception;

class ItemNonFisikHargaController extends Controller
{
    use ApiResponse;

    private array $menu = [];
    protected $service;

    public function __construct(ItemNonFisikHargaService $service)
    {
        $this->menu;
        $this->title = [
            'Tipe Item Non Fisik',
        ];
        $this->service = $service;
    }

    public function getItemHarga(Request $request)
    {
        try {
            $limit = $request->input('limit', 30);
            $search = $request->input('search');

            $data = $this->service->getItemHarga($limit, $search);

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
                'user_id' => 'required|integer|exists:users,id',
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
                'id' => 'required|string|exists:td_item_nonfisik_tipe,public_id',
                'nama' => 'required|string|max:25',
                'user_id' => 'required|integer|exists:users,id',
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

    public function delete(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|string|exists:td_item_nonfisik_tipe,public_id',
            ]);

            $deleted = $this->service->delete($validated['id']);

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
