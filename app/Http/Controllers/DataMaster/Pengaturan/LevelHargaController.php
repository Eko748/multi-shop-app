<?php

namespace App\Http\Controllers\DataMaster\Pengaturan;

use App\Http\Controllers\Controller;
use App\Models\LevelHarga;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class LevelHargaController extends Controller
{
    use ApiResponse;

    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Level Harga',
            'Tambah Data',
            'Edit Data',
        ];
    }

    public function getlevelharga(Request $request)
    {

        $meta['orderBy'] = $request->descending ? 'desc' : 'asc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = LevelHarga::query();

        $query->with([])->orderBy('id', $meta['orderBy']);

        if (! empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw('LOWER(nama_level_harga) LIKE ?', ["%$searchTerm%"]);
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
            'total_pages' => $data->lastPage(),
        ];

        $data = [
            'data' => $data->items(),
            'meta' => $paginationMeta,
        ];

        if (empty($data['data'])) {
            return response()->json([
                'status_code' => 400,
                'errors' => true,
                'message' => 'Tidak ada data',
            ], 400);
        }

        $mappedData = collect($data['data'])->map(function ($item) {
            return [
                'id' => $item['id'],
                'text' => $item->nama_level_harga,
            ];
        });

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => true,
            'message' => 'Sukses',
            'pagination' => $data['meta'],
        ], 200);
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[0]];

        return view('master.levelharga.index', compact('menu'));
    }

    public function post(Request $request)
    {
        $request->validate([
            'nama_level_harga' => 'required|max:255',
        ], [
            'nama_level_harga.required' => 'Nama Level Harga tidak boleh kosong.',
        ]);

        try {
            DB::beginTransaction();

            $data = LevelHarga::create([
                'nama_level_harga' => $request->nama_level_harga,
            ]);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: LevelHarga::class,
                subjectId: $data->id,
                event: 'Tambah Data',
                properties: [
                    'changes' => [
                        'new' => Arr::except($data->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']),
                    ],
                ],
                description: "LevelHarga {$data->nama_level_harga} (ID {$data->id}) ditambahkan.",
                userId: $request->user_id ?? null,
                message: $request->message ?? '(Sistem) Penambahan level harga baru.'
            );

            DB::commit();

            return $this->success(null, 200, 'Data berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error(500, 'Terjadi kesalahan saat menyimpan data: '.$e->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'nama_level_harga' => 'required|max:255',
            ], [
                'nama_level_harga.required' => 'Nama Level Harga tidak boleh kosong.',
            ]);

            $data = LevelHarga::findOrFail($request->id);

            $updateData = [
                'nama_level_harga' => $request->nama_level_harga,
            ];

            $changedData = [];
            foreach ($updateData as $key => $value) {
                $oldValue = $originalData[$key] ?? null;
                if ((string) $oldValue !== (string) $value) {
                    $changedData['old'][$key] = $oldValue;
                    $changedData['new'][$key] = $value;
                }
            }

            $updated = $data->update($updateData);

            if (! $updated) {
                throw new \Exception('Gagal memperbarui data level harga');
            }

            if (! empty($changedData)) {
                $this->saveLogAktivitas(
                    logName: $this->title[0],
                    subjectType: LevelHarga::class,
                    subjectId: $data->id,
                    event: 'Edit Data',
                    properties: ['changes' => $changedData],
                    description: "Level Harga {$data->nama_level_harga} (ID {$data->id}) diperbarui.",
                    userId: $request->user_id ?? null,
                    message: $request->message ?? '(Sistem) Perubahan data level harga.'
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
        $data = LevelHarga::findOrFail($request->id);

        try {
            DB::beginTransaction();

            $originalData = Arr::except($data->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: LevelHarga::class,
                subjectId: $data->id,
                event: 'Hapus Data',
                properties: [
                    'changes' => [
                        'old' => $originalData,
                    ],
                ],
                description: "Level Harga {$data->nama_level_harga} (ID {$data->id}) dihapus.",
                userId: $request->user_id ?? null,
                message: '(Sistem) Penghapusan data level harga.'
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
