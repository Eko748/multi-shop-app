<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class ImportPermissionsFromExcel extends Command
{
    protected $signature = 'import:permissions';
    protected $description = 'Import permissions from Excel and assign to roles.';

    public function handle()
    {
        $filePath = storage_path('app/private/role_middleware_analysis.xlsx');

        if (!file_exists($filePath)) {
            $this->error("❌ File tidak ditemukan: role_middleware_analysis.xlsx");
            return Command::FAILURE;
        }

        $data = Excel::toArray([], $filePath)[0]; // first sheet
        $rows = collect($data)->skip(1); // skip heading

        foreach ($rows as $row) {
            [$method, $uri, $controllerMethod, $rolesCsv, $status] = $row;

            $permissionName = strtoupper($method) . ' ' . $uri;

            // Buat permission
            $permission = Permission::firstOrCreate(['name' => $permissionName]);

            // Buat role dan assign permission
            $roleIds = explode(',', $rolesCsv);
            foreach ($roleIds as $roleId) {
                $roleName = 'Role_' . trim($roleId);
                $role = Role::firstOrCreate(['name' => $roleName]);
                $role->givePermissionTo($permission);
            }
        }

        // Assign semua permission ke Super Admin (user id = 1)
        $superAdmin = User::find(1);
        if ($superAdmin) {
            $superAdmin->syncRoles([]);
            $superAdmin->givePermissionTo(Permission::all());
            $this->info('✅ Super Admin (id=1) diberi semua permissions');
        }

        $this->info('✅ Permissions dan roles berhasil di-import.');
        return Command::SUCCESS;
    }
}
