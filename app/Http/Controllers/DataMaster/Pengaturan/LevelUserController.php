<?php

namespace App\Http\Controllers\DataMaster\Pengaturan;

use App\Http\Controllers\Controller;
use App\Helpers\ActivityLogger;
use App\Models\LevelUser;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\String_;

class LevelUserController extends Controller
{
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Data Level User',
            'Tambah Data',
            'Edit Data',
            'Atur Hak Akses'
        ];
    }

    public function getleveluser(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'desc' : 'asc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Role::query();

        // $query->where('id', '!=', 1);

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama_level) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereRaw("LOWER(informasi) LIKE ?", ["%$searchTerm%"]);
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
                'nama_level' => $item->name,
                'informasi' => $item->informasi,
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

        return view('master.leveluser.index', compact('menu'));
    }

    public function create()
    {
        $menu = [$this->title[0], $this->label[0], $this->title[1]];

        return view('master.leveluser.create', compact('menu'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nama_level' => 'required|max:255',
            'informasi' => 'required|max:255',
        ], [
            'nama_level.required' => 'Nama Level User tidak boleh kosong.',
            'informasi.required' => 'Informasi tidak boleh kosong.',
        ]);

        ActivityLogger::log('Tambah Level User', $request->all());

        try {

            Role::create([
                'name' => $request->nama_level,
                'informasi' => $request->informasi,
            ]);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', $th->getMessage())->withInput();
        }

        return redirect()->route('master.leveluser.index')->with('success', 'Sukses menambahkan User Baru');
    }

    public function edit(string $id)
    {
        $menu = [$this->title[0], $this->label[0], $this->title[2]];

        $leveluser = Role::findOrFail($id);

        return view('master.leveluser.edit', compact('menu', 'leveluser'));
    }

    public function update(Request $request, string $id)
    {
        $leveluser = Role::findOrFail($id);

        ActivityLogger::log('Update Level User', ['id' => $id]);

        try {

            $leveluser->update([
                'name' => $request->nama_level,
                'informasi' => $request->informasi,
            ]);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', $th->getMessage())->withInput();
        }

        return redirect()->route('master.leveluser.index')->with('success', 'Sukses Mengubah Data Level User');
    }


    public function delete(string $id)
    {
        $leveluser = Role::findOrFail($id);

        // Cek apakah ada user yang masih pakai role ini
        $relatedUsers = User::where('id_level', $id)->exists();

        if ($relatedUsers) {
            return response()->json([
                'success' => false,
                'message' => 'Edit User yang terkait dengan Role ini terlebih dahulu sebelum menghapus.'
            ], 400);
        }

        ActivityLogger::log('Delete Level User', ['id' => $id]);

        try {
            DB::beginTransaction();

            // Hapus data dari tabel role_has_permissions
            DB::table('role_has_permissions')->where('role_id', $id)->delete();

            // Hapus role-nya
            $leveluser->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sukses menghapus Data Level User'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Data Level User: ' . $th->getMessage()
            ], 500);
        }
    }


    public function hakAksesUser($id)
    {
        $menu = [$this->title[3], $this->label[0]];
        return view('master.leveluser.hak-akses', compact('id', 'menu'));
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
                fn($a, $b) => $a['method_order'] <=> $b['method_order'],
                fn($a, $b) => $a['name'] <=> $b['name'],
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
            ]
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
            'message' => 'Hak akses berhasil diperbarui.'
        ]);
    }
}
