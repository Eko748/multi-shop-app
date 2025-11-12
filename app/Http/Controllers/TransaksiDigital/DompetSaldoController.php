<?php

namespace App\Http\Controllers\TransaksiDigital;

use App\Http\Controllers\Controller;
use App\Services\DompetSaldoService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Validation\ValidationException;
use Exception;

class DompetSaldoController extends Controller
{
    use ApiResponse;

    private array $menu = [];
    protected $service;

    public function __construct(DompetSaldoService $service)
    {
        $this->menu;
        $this->title = [
            'Saldo Dompet Digital',
        ];
        $this->service = $service;
    }

    public function get(Request $request)
    {
        try {
            $filter = (object) [
                'limit' => $request->input('limit', 10),
                'search' => $request->input('search'),
                'saldo' => $request->input('saldo'),
            ];

            $data = $this->service->getAll($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (\Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function getSisaSaldo()
    {
        try {
            $data = $this->service->sumSisaSaldo();

            return $this->success($data, 200, 'Berhasil');
        } catch (\Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function getTotalPerKategori(Request $request)
    {
        try {
            $filter = (object) [
                'limit' => $request->input('limit', 10),
                'search' => $request->input('search'),
                'dompet_kategori' => $request->input('dompet_kategori'),
            ];

            $data = $this->service->getTotalPerKategori($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (\Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function getSaldoAkhir(Request $request)
    {
        try {
            $filter = (object) [
                'limit' => $request->input('limit', 30),
                'search' => $request->input('search'),
                'dompet_kategori' => $request->input('dompet_kategori'),
            ];

            $data = $this->service->getSaldoAkhir($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (\Exception $e) {
            return $this->error(500, 'Gagal mengambil data {$this->title[0]}', [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function getSaldo(Request $request)
    {
        try {
            $limit = $request->input('limit', 30);
            $search = $request->input('search');

            $data = $this->service->getSaldo($limit, $search);

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
                'dompet_kategori_id' => 'required|integer|exists:td_dompet_kategori,id',
                'kas_jenis_barang'   => 'nullable|integer',
                'saldo'              => ['required', 'numeric', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
                'harga_beli'         => ['required', 'numeric', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
                'created_by'         => 'required|integer|exists:users,id',
                'kas'                => 'required|string',
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
                'public_id'          => 'required|string|exists:td_dompet_saldo,public_id',
                'kas_jenis_barang'   => 'nullable|integer',
                'dompet_kategori_id' => 'required|integer|exists:td_dompet_kategori,id',
                'saldo'              => ['required', 'numeric', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
                'harga_beli'         => ['required', 'numeric', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
                'updated_by'         => 'required|integer|exists:users,id',
                'kas'                => 'required|string',
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
                'public_id' => 'required|string|exists:td_dompet_saldo,public_id',
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
