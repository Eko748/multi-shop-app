<?php

namespace App\Http\Controllers\DataMaster\Pengaturan;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    use ApiResponse;

    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Role',
            'Tambah Data',
            'Edit Data',
            'Atur Hak Akses',
        ];
    }

    public function getleveluser(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'desc' : 'asc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Role::query();

        // $query->where('id', '!=', 1);

        if (! empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw('LOWER(nama_level) LIKE ?', ["%$searchTerm%"]);
                $query->orWhereRaw('LOWER(informasi) LIKE ?', ["%$searchTerm%"]);
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
                'nama_level' => $item->name,
                'informasi' => $item->informasi,
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

        return view('master.role.index', compact('menu'));
    }

    public function post(Request $request)
    {
        $request->validate([
            'nama_level' => 'required|max:255',
            'informasi' => 'required|max:255',
        ], [
            'nama_level.required' => 'Nama Level User tidak boleh kosong.',
            'informasi.required' => 'Informasi tidak boleh kosong.',
        ]);

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->nama_level,
                'informasi' => $request->informasi,
                'guard_name' => 'web',
            ]);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: Role::class,
                subjectId: $role->id,
                event: 'Tambah Data',
                properties: [
                    'changes' => [
                        'new' => Arr::except($role->toArray(), ['id', 'created_at', 'updated_at']),
                    ],
                ],
                description: "Role {$role->name} (ID {$role->id}) ditambahkan.",
                userId: $request->user_id ?? null,
                message: $request->message ?? '(Sistem) Penambahan role baru.'
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
                'nama_level' => 'required|max:255',
                'informasi' => 'required|max:255',
            ], [
                'nama_level.required' => 'Nama Level User tidak boleh kosong.',
                'informasi.required' => 'Informasi tidak boleh kosong.',
            ]);

            $data = Role::findOrFail($request->id);

            $updateData = [
                'name' => $request->nama_level,
                'informasi' => $request->informasi,
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
                throw new \Exception('Gagal memperbarui data role');
            }

            if (! empty($changedData)) {
                $this->saveLogAktivitas(
                    logName: $this->title[0],
                    subjectType: Role::class,
                    subjectId: $data->id,
                    event: 'Edit Data',
                    properties: ['changes' => $changedData],
                    description: "Role {$data->name} (ID {$data->id}) diperbarui.",
                    userId: $request->user_id ?? null,
                    message: $request->message ?? '(Sistem) Perubahan data role.'
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
        $data = Role::findOrFail($request->id);

        try {
            DB::beginTransaction();

            $originalData = Arr::except($data->toArray(), ['id', 'created_at', 'updated_at']);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: Role::class,
                subjectId: $data->id,
                event: 'Hapus Data',
                properties: [
                    'changes' => [
                        'old' => $originalData,
                    ],
                ],
                description: "Role {$data->name} (ID {$data->id}) dihapus.",
                userId: $request->user_id ?? null,
                message: '(Sistem) Penghapusan data role.'
            );

            $data->delete();

            DB::commit();

            return $this->success(null, 200, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Internal Server Error', $e->getMessage());
        }
    }

    public function hakAksesUser($id)
    {
        $menu = [$this->title[3], $this->label[0]];

        return view('master.role.hak-akses', compact('id', 'menu'));
    }

    public function getHakAksesUser($id)
    {
        $level = \Spatie\Permission\Models\Role::findOrFail($id);
        $assignedPermissions = $level->permissions->pluck('name')->toArray();

        $methodOrder = ['GET' => 1, 'POST' => 2, 'PUT' => 3, 'DELETE' => 4];

        // Ambil semua permission dengan relasi menu, lalu urutkan dan group by menu_id
        $permissions = Permission::with('menu')
            ->orderBy('menu_id')
            ->get()
            ->map(function ($perm) use ($methodOrder) {
                $method = $this->detectMethodFromName($perm->name);

                return [
                    'id' => $perm->id,
                    'name' => $perm->name,
                    'alias' => $perm->alias ?? null,
                    'method' => $method,
                    'method_order' => $methodOrder[$method] ?? 99,
                    'menu_id' => $perm->menu_id ?? 0,
                    'menu_name' => $perm->menu->name ?? 'Tanpa Kategori',
                ];
            });

        // Group by menu_id
        $permissionsGrouped = $permissions->groupBy('menu_id')->map(function ($items, $menuId) {
            $menuName = $items->first()['menu_name'] ?? 'Tanpa Kategori';

            $sortedPermissions = $items->sortBy([
                fn ($a, $b) => $a['method_order'] <=> $b['method_order'],
                fn ($a, $b) => $a['name'] <=> $b['name'],
            ]);

            return [
                'menu_id' => $menuId,
                'menu_name' => $menuName,
                'permissions' => $sortedPermissions->map(function ($perm) {
                    return [
                        'id' => $perm['id'],
                        'name' => $perm['name'],
                        'alias' => $perm['alias'],
                        'method' => $perm['method'],
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'status' => 200,
            'data' => [
                'level' => $level,
                'permissions_grouped' => $permissionsGrouped,
                'assigned_permissions' => $assignedPermissions,
            ],
        ]);
    }

    private function detectMethodFromName($name)
    {
        $name = strtolower($name);

        return match (true) {
            str_contains($name, 'index'),
            str_contains($name, 'show'),
            str_contains($name, 'get') => 'GET',

            str_contains($name, 'store'),
            str_contains($name, 'post') => 'POST',

            str_contains($name, 'update'),
            str_contains($name, 'put') => 'PUT',

            str_contains($name, 'destroy'),
            str_contains($name, 'delete') => 'DELETE',

            default => null,
        };
    }

    public function createHakAksesUser(Request $request, $id)
    {
        $level = \Spatie\Permission\Models\Role::findOrFail($id);
        $permissions = $request->input('permissions', []);
        $level->syncPermissions($permissions);

        return response()->json([
            'status' => 200,
            'message' => 'Hak akses berhasil diperbarui.',
        ]);
    }
}
