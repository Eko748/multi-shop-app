<?php

namespace App\Http\Controllers\Retur;

use App\Http\Controllers\Controller;
use App\Services\ReturSupplierService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Traits\HasFilter;
use Illuminate\Validation\ValidationException;
use Exception;

class ReturSupplierController extends Controller
{
    use ApiResponse, HasFilter;

    private array $menu = [];
    protected $service;

    public function __construct(ReturSupplierService $service)
    {
        $this->menu;
        $this->title = ['Retur Suplier'];
        $this->service = $service;
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[3]];

        return view('retur.supplier.index', compact('menu'));
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

    public function getData(Request $request)
    {
        try {
            $filter = $this->makeFilter($request, 30, [
                'id' => $request->input('id'),
            ]);
            $data = $this->service->getData($filter);

            return $this->success($data['data'], 200, 'Berhasil');
        } catch (\Exception $e) {
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

    public function getSupplier(Request $request)
    {
        try {
            $filter = $this->makeFilter($request, 1);

            $data = $this->service->getSupplier($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function getReturMember(Request $request)
    {
        try {
            $filter = $this->makeFilter($request, 1, [
                'supplier_id' => $request->input('supplier_id'),
            ]);

            $data = $this->service->getReturMember($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function getQRCode(Request $request)
    {
        try {
            // Tambahkan referensi dari request ke filter
            $filter = $this->makeFilter($request, 1);

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
            $filter = $this->makeFilter($request, 500, [
                'id' => $request->input('id'),
                'tipe' => $request->input('tipe'),
            ]);

            $data = $this->service->getHargaBarang($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    // public function post(Request $request)
    // {
    //     try {
    //         $validated = $request->validate([
    //             'retur' => 'required|array|min:1',
    //             'retur.*.tanggal' => 'required|date',
    //             'retur.*.created_by' => 'required|exists:users,id',
    //             'retur.*.toko_id' => 'required|exists:toko,id',
    //             'retur.*.tipe_retur' => 'required|in:pembelian,member',
    //             'retur.*.supplier_id' => 'nullable|exists:supplier,id',
    //             'retur.*.total_refund' => 'required|numeric|min:0',
    //             'retur.*.total_hpp' => 'required|numeric|min:0',
    //             'retur.*.selisih' => 'required|numeric|min:0',
    //             'retur.*.status' => 'required|in:seimbang,rugi,untung',

    //             // detail items
    //             'retur.*.items' => 'required|array|min:1',
    //             'retur.*.items.*.id' => 'required|integer',
    //             'retur.*.items.*.barang_id' => 'nullable|exists:barang,id',
    //             'retur.*.items.*.supplier_id' => 'nullable|exists:supplier,id',
    //             'retur.*.items.*.kompensasi' => 'nullable|in:refund,barang,kombinasi',
    //             'retur.*.items.*.qty' => 'required|integer|min:0',
    //             'retur.*.items.*.qty_refund' => 'required|integer|min:0',
    //             'retur.*.items.*.qty_barang' => 'required|integer|min:0',
    //             'retur.*.items.*.jumlah_refund' => 'nullable|numeric|min:0',
    //             'retur.*.items.*.hpp' => 'nullable|numeric|min:0',
    //             'retur.*.items.*.harga_jual' => 'nullable|numeric|min:0',
    //             'retur.*.items.*.total_refund' => 'nullable|numeric|min:0',
    //             'retur.*.items.*.total_hpp' => 'nullable|numeric|min:0',
    //             'retur.*.items.*.selisih' => 'nullable|numeric|min:0',
    //             'retur.*.items.*.status' => 'required|in:seimbang,rugi,untung',

    //             // summary
    //             'summary' => 'required|array',
    //             'summary.sub_total_refund' => 'required|numeric|min:0',
    //             'summary.sub_total_hpp' => 'required|numeric|min:0',
    //             'summary.sub_total_selisih' => 'required|numeric|min:0',
    //             'summary.status' => 'required|in:seimbang,rugi,untung',
    //             'summary.tanggal' => 'required|date',
    //             'summary.created_by' => 'required|exists:users,id',
    //         ]);

    //         $data = $this->service->create($validated);

    //         return $this->success($data, 201, 'Data berhasil ditambahkan');
    //     } catch (ValidationException $e) {
    //         return $this->error(422, 'Validation Error', $e->errors());
    //     } catch (Exception $e) {
    //         return $this->error(500, 'Internal Server Error', $e->getMessage());
    //     }
    // }

    public function post(Request $request)
    {
        try {
            $validated = $request->validate([
                'created_by' => 'required|exists:users,id',
                'toko_id' => 'required|exists:toko,id',
                'tipe_retur' => 'required|in:pembelian,member',
                'tanggal' => 'required|date',
                'total_hpp' => 'required|numeric|min:0',
                'qty' => 'required|numeric|min:1',
                'retur' => 'required|array|min:1',
                'retur.*.supplier_id' => 'required|exists:supplier,id',
                'retur.*.detail' => 'required|array|min:1',
                'retur.*.detail.*.id' => 'required|integer',
                'retur.*.detail.*.barang_id' => 'required|exists:barang,id',
                'retur.*.detail.*.qty' => 'required|numeric|min:1',
                'retur.*.detail.*.hpp' => 'required|numeric|min:0',
                'retur.*.detail.*.harga_jual' => 'required|numeric|min:0',
            ]);

            $data = $this->service->create($validated);

            return $this->success($data, 201, 'Data retur berhasil dibuat');
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
                'id' => 'required|integer|exists:retur_supplier,id',
                'toko_id' => 'required|integer|exists:toko,id',
                'qty' => 'required|numeric|min:1',
                'total_qty' => 'required|numeric|min:1',
                'total_hpp' => 'required|numeric|min:0',
                'subtotal_refund' => 'required|numeric|min:0',
                'subtotal_hpp' => 'required|numeric|min:0',
                'subtotal_selisih' => 'required|numeric',
                'updated_by' => 'required|integer|exists:users,id',

                'retur' => 'required|array|min:1',
                'retur.*.supplier_id' => 'required|integer|exists:supplier,id',

                'retur.*.detail' => 'required|array|min:1',
                'retur.*.detail.*.id' => 'required|integer|exists:retur_supplier_detail,id',
                'retur.*.detail.*.barang_id' => 'required|integer|exists:barang,id',
                'retur.*.detail.*.kompensasi' => 'required|string|in:refund,barang,kombinasi',
                'retur.*.detail.*.qty_refund' => 'nullable|numeric|min:0',
                'retur.*.detail.*.qty_barang' => 'nullable|numeric|min:0',
                'retur.*.detail.*.jumlah_refund' => 'nullable|numeric|min:0',
                'retur.*.detail.*.total_refund' => 'nullable|numeric|min:0',
                'retur.*.detail.*.total_hpp' => 'nullable|numeric|min:0',
                'retur.*.detail.*.selisih' => 'nullable|numeric',
                'retur.*.detail.*.hpp' => 'nullable|numeric|min:0',
                'retur.*.detail.*.harga_jual' => 'nullable|numeric|min:0',
                'retur.*.detail.*.retur_id' => 'required|integer|exists:retur_supplier,id',
                'retur.*.detail.*.updated_by' => 'required|integer|exists:users,id',
            ]);

            // 🔹 Validasi tambahan: qty utama harus sama dengan total_qty
            if ((float) $validated['qty'] !== (float) $validated['total_qty']) {
                return $this->error(422, 'Validation Error', [
                    'qty' => ['Qty utama harus sama dengan total qty awal pengajuan retur.']
                ]);
            }

            // 🔹 Validasi tambahan: total qty detail harus sama dengan qty utama
            $totalDetailQty = 0;

            foreach ($validated['retur'] as $retur) {
                foreach ($retur['detail'] as $detail) {
                    $totalDetailQty +=
                        ($detail['qty_refund'] ?? 0) +
                        ($detail['qty_barang'] ?? 0);
                }
            }

            if ((float) $validated['qty'] !== (float) $totalDetailQty) {
                return $this->error(422, 'Validation Error', [
                    'qty' => ['Jumlah total (Qty Refund + Qty Barang) tidak sama dengan qty utama.']
                ]);
            }

            // 🔹 Lanjutkan ke service update
            $this->service->update($validated);

            return $this->success(null, 200, 'Data retur berhasil diperbarui');
        } catch (ValidationException $e) {
            return $this->error(422, 'Validation Error', $e->errors());
        } catch (Exception $e) {
            return $this->error(500, 'Internal Server Error', $e->getMessage());
        }
    }

    public function verify(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:retur_supplier,id',
                'updated_by' => 'required|integer|exists:users,id',
            ]);

            $this->service->verify($validated);

            return $this->success(null, 200, 'Data retur berhasil diperbarui');
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
