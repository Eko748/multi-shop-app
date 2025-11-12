<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Role Super Admin jika belum ada
        $superAdminRole = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ], [
            'informasi' => 'Memiliki semua akses penuh terhadap sistem',
        ]);

        // 2. Ambil semua permission
        $permissions = Permission::all();

        // 3. Assign semua permission ke Super Admin
        $superAdminRole->syncPermissions($permissions);

        // 4. Assign role Super Admin ke user id = 1
        $user = User::find(1);
        if ($user) {
            $user->syncRoles([$superAdminRole->name]);
        }
    }
}
