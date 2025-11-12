<?php

namespace App\Http\Controllers\TransaksiDigital;

use App\Http\Controllers\Controller;
use App\Services\PenjualanNonFisikService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Traits\HasFilter;
use Illuminate\Validation\ValidationException;
use Exception;

class PenjualanNonFisikController extends Controller
{
    use ApiResponse;
    use HasFilter;

    private array $menu = [];
    protected $service;

    public function __construct(PenjualanNonFisikService $service)
    {
        $this->menu;
        $this->title = [
            'Transaksi Non Fisik',
        ];
        $this->service = $service;
    }

    public function getSisaSaldo(Request $request)
    {
        try {
            $filter = $this->makeFilter($request, 30);
            $data = $this->service->getTotalHarga($filter);

            return $this->success($data, 200, 'Berhasil');
        } catch (\Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
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

    public function getDetail(Request $request)
    {
        try {
            $filter = (object) [
                'limit' => $request->input('limit', 10),
                'search' => $request->input('search'),
                'id' => $request->input('id'),
            ];

            $data = $this->service->getDetail($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (\Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function getNota(Request $request)
    {
        try {
            $limit = $request->input('limit', 30);
            $search = $request->input('search');

            $data = $this->service->getNota($limit, $search);

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
                'total_bayar' => ['required', 'numeric', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
                'total_hpp' => ['required', 'numeric', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
                'total_harga_jual' => ['required', 'numeric', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
                'dompet_kategori_id' => 'required|integer|exists:td_dompet_kategori,id',
                'saldo' => 'required|numeric|min:1',
                'items' => 'required|array|min:1',
                'items.*.id' => 'required|integer',
                'items.*.qty' => 'required|integer|min:1',
                'items.*.hpp' => ['required', 'numeric', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
                'items.*.harga_jual' => ['required', 'numeric', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
                'created_by' => 'required|integer',
            ]);

            $data = $this->service->create($validated);

            return $this->success($data, 201, 'Data berhasil ditambahkan');
        } catch (ValidationException $e) {
            return $this->error(422, collect($e->errors())->map(fn($err) => $err[0])->implode(', '), $e->errors());
        } catch (Exception $e) {
            return $this->error(500, 'Internal Server Error', $e->getMessage());
        }
    }

    public function put(Request $request)
    {
        try {
            $validated = $request->validate([
                'public_id'          => 'required|string|exists:td_dompet_saldo,public_id',
                'dompet_kategori_id' => 'required|integer|exists:td_dompet_kategori,id',
                'saldo'              => ['required', 'numeric', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
                'harga_beli'         => ['required', 'numeric', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
                'updated_by'         => 'required|integer|exists:users,id',
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
                'public_id' => 'required|string|exists:td_penjualan_nonfisik,public_id',
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
