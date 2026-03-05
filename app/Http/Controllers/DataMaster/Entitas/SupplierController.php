<?php

namespace App\Http\Controllers\DataMaster\Entitas;

use App\Http\Controllers\Controller;
use App\Helpers\ActivityLogger;
use App\Imports\SupplierImport;
use App\Models\Supplier;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class SupplierController extends Controller
{
    use ApiResponse;
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Data Supplier',
            'Tambah Data',
            'Edit Data'
        ];
    }

    public function getsupplier(Request $request)
    {

        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Supplier::query();

        $query->with([])->orderBy('id', $meta['orderBy']);

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereRaw("LOWER(telepon) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereRaw("LOWER(alamat) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereRaw("LOWER(email) LIKE ?", ["%$searchTerm%"]);
            });
        }

        if ($request->has('startDate') && $request->has('endDate')) {
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');

            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $data = $query->paginate($meta['limit']);

        $paginationMeta = [
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'total_pages' => $data->lastPage()
        ];

        $data = [
            'data' => $data->items(),
            'meta' => $paginationMeta
        ];

        if (empty($data['data'])) {
            return response()->json([
                'status_code' => 400,
                'errors' => true,
                'message' => 'Tidak ada data'
            ], 400);
        }

        $mappedData = collect($data['data'])->map(function ($item) {
            return [
                'id' => $item['id'],
                'nama' => $item->nama,
                'email' => $item->email,
                'alamat' => $item->alamat,
                'telepon' => $item->telepon,
            ];
        });

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => true,
            'message' => 'Sukses',
            'pagination' => $data['meta']
        ], 200);
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[0]];

        $supplier = Supplier::orderBy('id', 'desc')->get();

        return view('master.supplier.index', compact('menu', 'supplier'));
    }

    public function post(Request $request)
    {
        try {
            $data = $request->all();

            $validatedData = $request->validate([
                'nama' => 'required|max:255',
                'alamat' => 'required|max:255',
                'email' => 'nullable',
                'telepon' => 'nullable',
            ], [
                'nama.required' => 'Nama Supplier tidak boleh kosong.',
                'alamat.required' => 'Alamat tidak boleh kosong.',
            ]);

            DB::beginTransaction();

            $data = Supplier::create([
                'nama' => $validatedData['nama'],
                'alamat' => $validatedData['alamat'],
                'email' => $validatedData['email'],
                'telepon' => $validatedData['telepon'],
            ]);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: Supplier::class,
                subjectId: $data->id,
                event: 'Tambah Data',
                properties: [
                    'changes' => [
                        'new' => Arr::except($data->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']),
                    ],
                ],
                description: "Suplier {$data->nama} (ID {$data->id}) ditambahkan.",
                userId: $request->user_id ?? null,
                message: $request->message ?? '(Sistem) Penambahan suplier baru.'
            );

            DB::commit();
            return $this->success($data, 201, 'Data berhasil ditambahkan');
        } catch (Throwable $th) {
            DB::rollBack();
            return $this->error(500, 'Internal Server Error', $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'nama' => 'required|string',
                'email' => 'nullable|string',
                'alamat' => 'nullable|string',
                'telepon' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $supplier = Supplier::findOrFail($request->id);

            $originalData = Arr::except($supplier->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']);

            $updateData = [
                'nama' => $request->nama,
                'email' => $request->email,
                'alamat' => $request->alamat,
                'telepon' => $request->telepon,
            ];

            $changedData = [];
            foreach ($updateData as $key => $value) {
                $oldValue = $originalData[$key] ?? null;
                if ((string)$oldValue !== (string)$value) {
                    $changedData['old'][$key] = $oldValue;
                    $changedData['new'][$key] = $value;
                }
            }

            $updated = $supplier->update($updateData);

            if (!$updated) {
                throw new \Exception('Gagal memperbarui data member');
            }

            if (!empty($changedData)) {
                $this->saveLogAktivitas(
                    logName: $this->title[0],
                    subjectType: Supplier::class,
                    subjectId: $supplier->id,
                    event: 'Edit Data',
                    properties: ['changes' => $changedData],
                    description: "Suplier {$supplier->nama} (ID {$supplier->id}) diperbarui.",
                    userId: $request->user_id ?? null,
                    message: $request->message ?? '(Sistem) Perubahan data suplier.'
                );
            }

            DB::commit();
            return $this->success($updateData, 201, 'Data berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Internal Server Error', $e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        $supplier = Supplier::findOrFail($request->id);

        try {
            DB::beginTransaction();

            $originalData = Arr::except($supplier->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: Supplier::class,
                subjectId: $supplier->id,
                event: 'Hapus Data',
                properties: [
                    'changes' => [
                        'old' => $originalData,
                    ],
                ],
                description: "Suplier {$supplier->nama} (ID {$supplier->id}) dihapus.",
                userId: $request->user_id ?? null,
                message: '(Sistem) Penghapusan data suplier.'
            );

            $supplier->delete();

            DB::commit();

            return $this->success(null, 200, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Internal Server Error', $e->getMessage());
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        Excel::import(new SupplierImport, $request->file('file'));

        return back()->with('success', 'Data berhasil diimpor!');
    }
}
