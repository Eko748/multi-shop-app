<?php

namespace App\Http\Controllers\DataMaster\Pengaturan;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Pengaturan Hak Akses',
        ];
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[0]];
        return view('master.permission.index', compact('menu'));
    }

    public function getData()
    {
        $methodOrder = ['GET' => 1, 'POST' => 2, 'PUT' => 3, 'DELETE' => 4];

        // Ambil semua permission dan mapping dengan data tambahan
        $permissions = Permission::with('menu')
            ->orderBy('menu_id')
            ->get()
            ->map(function ($perm) use ($methodOrder) {
                $method = $this->detectMethodFromName($perm->name);
                return [
                    'id' => $perm->id,
                    'name' => $perm->name,
                    'alias' => $perm->alias,
                    'method' => $method,
                    'method_order' => $methodOrder[$method] ?? 99,
                    'menu_id' => $perm->menu_id ?? 0,
                    'menu_name' => $perm->menu->name ?? 'Tanpa Menu',
                ];
            });

        // Group berdasarkan menu_id
        $groupedByMenu = $permissions->groupBy('menu_id');

        $grouped = [];

        foreach ($groupedByMenu as $menuId => $items) {
            $menuName = $items->first()['menu_name'] ?? 'Tanpa Menu';

            // Sort per group berdasarkan urutan method lalu nama
            $sortedPerms = $items->sortBy([
                fn($a, $b) => $a['method_order'] <=> $b['method_order'],
                fn($a, $b) => $a['name'] <=> $b['name'],
            ]);

            $grouped[] = [
                'menu_name' => $menuName,
                'permissions' => $sortedPerms->map(function ($perm) {
                    return [
                        'id' => $perm['id'],
                        'name' => $perm['name'],
                        'alias' => $perm['alias'],
                        'method' => $perm['method'],
                    ];
                })->values()
            ];
        }

        return response()->json([
            'status' => 200,
            'data' => $grouped,
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

    public function update(Request $request)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*.id' => 'required|exists:permissions,id',
            'permissions.*.alias' => 'nullable|string|max:255',
        ]);

        foreach ($request->permissions as $perm) {
            Permission::where('id', $perm['id'])->update([
                'alias' => $perm['alias'],
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Alias berhasil diperbarui.',
        ]);
    }
}
