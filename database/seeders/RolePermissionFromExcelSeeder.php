<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Menu; // ← Import model Menu
use Illuminate\Support\Str;

class RolePermissionFromExcelSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $path = storage_path('app/private/permission_seeder.xlsx');
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $deferredDashboardApiRows = [];

        foreach (array_slice($rows, 1) as $row) {
            $methods = $row[0] ?? null;
            $uri = $row[1] ?? null;
            $controllerAction = $row[2] ?? null;
            $roles = $row[3] ?? null;

            if (!$methods || !$uri || !$controllerAction) continue;

            if (str_starts_with($uri, 'admin/')) {
                $this->processPermissionRow($methods, $uri, $controllerAction, $roles);
            } elseif (str_starts_with($uri, 'api/') && Str::contains($controllerAction, 'DashboardController')) {
                // Simpan untuk diproses nanti
                $deferredDashboardApiRows[] = [$methods, $uri, $controllerAction, $roles];
            }
        }

        // Proses baris api/ dengan DashboardController setelah baris admin/
        foreach ($deferredDashboardApiRows as [$methods, $uri, $controllerAction, $roles]) {
            $this->processPermissionRow($methods, $uri, $controllerAction, $roles);
        }

        // Assign semua permission ke Super Admin
        $superAdmin = User::find(1);
        if ($superAdmin) {
            $superAdminRole = Role::firstOrCreate([
                'name' => 'Super Admin',
                'guard_name' => 'web',
            ]);
            $superAdminRole->syncPermissions(Permission::all());
            $superAdmin->assignRole('Super Admin');
        }

        echo "✅ PermissionSeeder selesai dijalankan.\n";
    }

    private function processPermissionRow($methods, $uri, $controllerAction, $roles): void
    {
        $menuName = $this->extractMenuName($controllerAction);
        $menuSlug = Str::slug($menuName);

        $menu = Menu::firstOrCreate(
            ['slug' => $menuSlug],
            ['name' => $menuName]
        );

        foreach (explode(',', $methods) as $method) {
            $method = strtoupper(trim($method));
            $cleanedUri = ltrim($uri, '/');

            $cleanedUri = preg_replace('#^(api/admin/|admin/|api/)#', '', $cleanedUri);
            $permissionName = $method . ' /' . ltrim($cleanedUri, '/');

            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ], [
                'menu_id' => $menu->id
            ]);

            if (!$permission->menu_id) {
                $permission->menu_id = $menu->id;
                $permission->save();
            }

            if ($roles) {
                foreach (explode(',', $roles) as $roleId) {
                    $roleId = trim($roleId);
                    if ($roleId === '') continue;

                    $roleNameMap = [
                        '1' => 'Super Admin',
                        '2' => 'Akunting',
                        '3' => 'Admin GSS',
                        '4' => 'Admin Toko',
                        '5' => 'Karyawan',
                        '6' => 'Franchise',
                    ];

                    $roleName = $roleNameMap[$roleId] ?? null;
                    if (!$roleName) continue;

                    $role = Role::where('name', $roleName)->first();
                    if ($role) {
                        $role->givePermissionTo($permission);
                    }
                }
            }
        }
    }

    private function extractMenuName(string $controllerAction): string
    {
        // Case spesial: jika controller adalah AuthController → ubah ke "Dashboard"
        if (Str::contains($controllerAction, 'AuthController')) {
            return 'Dashboard';
        }

        // Contoh input: App\Http\Controllers\LaporanKeuangan\LabaRugiController@getlabarugi
        $parts = explode('\\', $controllerAction);
        $controllerMethod = end($parts); // misalnya: LabaRugiController@getlabarugi

        $controllerName = explode('@', $controllerMethod)[0] ?? '';
        $name = preg_replace('/Controller$/', '', $controllerName); // Hapus 'Controller'

        // Pisahkan camelCase menjadi spasi → LabaRugi → "Laba Rugi"
        return trim(preg_replace('/(?<!^)([A-Z])/', ' $1', $name));
    }
}
