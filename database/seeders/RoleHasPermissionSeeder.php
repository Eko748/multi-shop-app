<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RoleHasPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $json = file_get_contents(storage_path('app/private/json/role_has_permission.json'));
        $data = json_decode($json, true);

        if (!isset($data['role_has_permissions'])) {
            $this->command->error("Data 'role_has_permissions' tidak ditemukan dalam file JSON.");
            return;
        }

        foreach ($data['role_has_permissions'] as $item) {
            DB::table('role_has_permissions')->updateOrInsert(
                [
                    'permission_id' => $item['permission_id'],
                    'role_id'       => $item['role_id'],
                ]
            );
        }

        $this->command->info('RoleHasPermissionSeeder berhasil dijalankan.');
    }
}
