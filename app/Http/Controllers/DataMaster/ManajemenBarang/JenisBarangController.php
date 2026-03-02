<?php

namespace App\Http\Controllers\DataMaster\ManajemenBarang;

use App\Http\Controllers\Controller;
use App\Models\JenisBarang;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class JenisBarangController extends Controller
{
    use ApiResponse;
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Data Jenis Barang',
            'Tambah Data',
            'Edit Data'
        ];
    }

    public function getjenisbarang(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = JenisBarang::query();

        $query->with([])->orderBy('id', $meta['orderBy']);

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama_jenis_barang) LIKE ?", ["%$searchTerm%"]);
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
                'nama_jenis_barang' => $item->nama_jenis_barang,
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

        $jenisbarang = JenisBarang::orderBy('id', 'desc')->get();

        return view('master.jenisbarang.index', compact('menu', 'jenisbarang'));
    }

    public function post(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nama_jenis_barang' => 'required|max:255',
            ], [
                'nama_jenis_barang.required' => 'Jenis Barang tidak boleh kosong.',
            ]);

            DB::beginTransaction();

            $data = JenisBarang::create([
                'nama_jenis_barang' => $validatedData['nama_jenis_barang'],
            ]);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: JenisBarang::class,
                subjectId: $data->id,
                event: 'Tambah Data',
                properties: [
                    'changes' => [
                        'new' => Arr::except($data->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']),
                    ],
                ],
                description: "Jenis Barang {$data->nama_jenis_barang} (ID {$data->id}) ditambahkan.",
                userId: $request->user_id ?? null,
                message: $request->message ?? '(Sistem) Penambahan jenis barang baru.'
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
                'nama_jenis_barang' => 'required|string',
            ]);
            
            DB::beginTransaction();

            $jenisbarang = JenisBarang::findOrFail($request->id);
            $originalData = Arr::except($jenisbarang->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']);

            $updateData = [
                'nama_jenis_barang' => $request->nama_jenis_barang,
            ];

            $changedData = [];
            foreach ($updateData as $key => $value) {
                $oldValue = $originalData[$key] ?? null;
                if ((string)$oldValue !== (string)$value) {
                    $changedData['old'][$key] = $oldValue;
                    $changedData['new'][$key] = $value;
                }
            }

            $updated = $jenisbarang->update($updateData);

            if (!$updated) {
                throw new \Exception('Gagal memperbarui data jenis barang');
            }

            if (!empty($changedData)) {
                $this->saveLogAktivitas(
                    logName: $this->title[0],
                    subjectType: JenisBarang::class,
                    subjectId: $jenisbarang->id,
                    event: 'Edit Data',
                    properties: ['changes' => $changedData],
                    description: "Jenis Barang {$jenisbarang->nama_jenis_barang} (ID {$jenisbarang->id}) diperbarui.",
                    userId: $request->user_id ?? null,
                    message: $request->message ?? '(Sistem) Perubahan data jenis barang.'
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
        $jenisbarang = JenisBarang::findOrFail($request->id);

        try {
            DB::beginTransaction();

            $originalData = Arr::except($jenisbarang->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: JenisBarang::class,
                subjectId: $jenisbarang->id,
                event: 'Hapus Data',
                properties: [
                    'changes' => [
                        'old' => $originalData,
                    ],
                ],
                description: "Jenis Barang {$jenisbarang->nama_jenis_barang} (ID {$jenisbarang->id}) dihapus.",
                userId: $request->user_id ?? null,
                message: '(Sistem) Penghapusan data jenis barang.'
            );

            $jenisbarang->delete();

            DB::commit();

            return $this->success(null, 200, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Internal Server Error', $e->getMessage());
        }
    }
}
