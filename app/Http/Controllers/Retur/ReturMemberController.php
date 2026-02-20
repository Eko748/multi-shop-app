<?php

namespace App\Http\Controllers\Retur;

use App\Http\Controllers\Controller;
use App\Services\ReturMemberService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Traits\HasFilter;
use Illuminate\Validation\ValidationException;
use Exception;

class ReturMemberController extends Controller
{
    use ApiResponse, HasFilter;

    private array $menu = [];
    protected $service;

    public function __construct(ReturMemberService $service)
    {
        $this->menu;
        $this->title = ['Retur Member'];
        $this->service = $service;
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[3]];

        return view('retur.member.index', compact('menu'));
    }

    public function get(Request $request)
    {
        try {
            $filter = $this->makeFilter($request, 30);
            $data = $this->service->getAll($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function getDetail(Request $request)
    {
        try {
            $filter = $this->makeFilter($request, 30, [
                'id' => $request->input('id'),
            ]);
            $data = $this->service->getDetail($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (\Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function getQRCode(Request $request)
    {
        try {
            $filter = $this->makeFilter($request, 1, [
                'member_id' => $request->input('member_id'),
            ]);

            $data = $this->service->getQRCode($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function getHargaBarang(Request $request)
    {
        try {
            // Tambahkan referensi dari request ke filter
            $filter = $this->makeFilter($request, 1, [
                'id' => $request->input('id'),
            ]);

            $data = $this->service->getHargaBarang($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function post(Request $request)
    {
        try {
            $validated = $request->validate([
                'toko_id' => 'required|exists:toko,id',
                'member_id' => 'required',
                'status' => 'required|in:draft,proses,selesai',
                'tanggal' => 'required|date',
                'created_by' => 'required|exists:users,id',

                'items' => 'required|array|min:1',
                'items.*.supplier_id' => 'nullable|exists:barang,id',
                'items.*.barang_id' => 'required|exists:barang,id',
                'items.*.transaksi_kasir_detail_id' => 'required|integer',
                'items.*.tipe_kompensasi' => 'nullable|in:refund,barang',
                'items.*.qty_request' => 'required|integer|min:1',
                'items.*.qty_barang' => 'required|integer',
                'items.*.hpp' => 'nullable|numeric|min:0',
                'items.*.harga_jual' => 'nullable|numeric|min:0',
                'items.*.total_hpp_barang' => 'nullable|numeric|min:0',
                'items.*.qty_refund' => 'required|integer',
                'items.*.jumlah_refund' => 'nullable|numeric|min:0',
                'items.*.total_refund' => 'nullable|numeric|min:0',
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
                'id' => 'required|exists:retur,id',
                'toko_id' => 'required|exists:toko,id',
                'member_id' => 'nullable|exists:member,id',
                'referensi' => 'required|string',
                'status' => 'required|in:draft,dari_member,dari_cabang,di_toko_utama,ke_supplier,selesai',
                'tanggal' => 'required|date',
                'updated_by' => 'required|exists:users,id',
            ]);

            $data = $this->service->update($validated['id'], $validated);

            if (!$data) {
                return $this->error(404, 'Data tidak ditemukan');
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
                'id' => 'required|exists:retur,id',
                'deleted_by' => 'required|exists:users,id',
            ]);

            $deleted = $this->service->delete($validated['id'], $validated);

            if (!$deleted) {
                return $this->error(404, 'Data tidak ditemukan');
            }

            return $this->success(null, 200, 'Data berhasil dihapus');
        } catch (ValidationException $e) {
            return $this->error(422, 'Validation Error', $e->errors());
        } catch (Exception $e) {
            return $this->error(500, 'Internal Server Error', $e->getMessage());
        }
    }
}
