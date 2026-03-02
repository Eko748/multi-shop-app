<?php

namespace App\Http\Controllers\DataMaster\ManajemenBarang;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Support\Arr;
use Throwable;

class BrandController extends Controller
{
    use ApiResponse;
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Data Brand',
            'Tambah Data',
            'Edit Data'
        ];
    }

    public function getbrand(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Brand::query();

        $query->with([])->orderBy('id', $meta['orderBy']);

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama_brand) LIKE ?", ["%$searchTerm%"]);
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
                'nama_brand' => $item->nama_brand,
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

        return view('master.brand.index', compact('menu'));
    }

    public function getBrandsByJenis(Request $request)
    {
        $request->validate([
            'id_jenis_barang' => 'required|exists:jenis_barang,id'
        ]);

        $brands = Brand::where('id_jenis_barang', $request->id_jenis_barang)->get();

        return response()->json($brands);
    }

    public function post(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nama_brand' => 'required|string|max:255',
            ], [
                'nama_brand.required' => 'Nama Brand tidak boleh kosong.',
            ]);

            DB::beginTransaction();

            $data = Brand::create([
                'nama_brand' => $validatedData['nama_brand'],
            ]);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: Brand::class,
                subjectId: $data->id,
                event: 'Tambah Data',
                properties: [
                    'changes' => [
                        'new' => Arr::except($data->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']),
                    ],
                ],
                description: "Brand {$data->nama_brand} (ID {$data->id}) ditambahkan.",
                userId: $request->user_id ?? null,
                message: $request->message ?? '(Sistem) Penambahan brand baru.'
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
                'nama_brand' => 'required|string',
            ]);

            DB::beginTransaction();

            $brand = Brand::findOrFail($request->id);

            $originalData = Arr::except($brand->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']);

            $updateData = [
                'nama_brand' => $request->nama_brand,
            ];

            $changedData = [];
            foreach ($updateData as $key => $value) {
                $oldValue = $originalData[$key] ?? null;
                if ((string)$oldValue !== (string)$value) {
                    $changedData['old'][$key] = $oldValue;
                    $changedData['new'][$key] = $value;
                }
            }

            $updated = $brand->update($updateData);

            if (!$updated) {
                throw new \Exception('Gagal memperbarui data brand');
            }

            if (!empty($changedData)) {
                $this->saveLogAktivitas(
                    logName: $this->title[0],
                    subjectType: Brand::class,
                    subjectId: $brand->id,
                    event: 'Edit Data',
                    properties: ['changes' => $changedData],
                    description: "Brand {$brand->nama_brand} (ID {$brand->id}) diperbarui.",
                    userId: $request->user_id ?? null,
                    message: $request->message ?? '(Sistem) Perubahan data brand.'
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
        $data = Brand::findOrFail($request->id);

        try {
            DB::beginTransaction();

            $originalData = Arr::except($data->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: Brand::class,
                subjectId: $data->id,
                event: 'Hapus Data',
                properties: [
                    'changes' => [
                        'old' => $originalData,
                    ],
                ],
                description: "Brand {$data->nama_brand} (ID {$data->id}) dihapus.",
                userId: $request->user_id ?? null,
                message: '(Sistem) Penghapusan data brand.'
            );

            $data->delete();

            DB::commit();

            return $this->success(null, 200, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Internal Server Error', $e->getMessage());
        }
    }
}
