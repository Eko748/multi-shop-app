<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Baca isi file JSON
        $json = file_get_contents(storage_path('app/private/json/permission.json'));
        $data = json_decode($json, true);

        // Validasi apakah data 'permissions' tersedia
        if (!isset($data['permissions'])) {
            $this->command->error("Data 'permissions' tidak ditemukan dalam file JSON.");
            return;
        }

        foreach ($data['permissions'] as $permission) {
            Permission::updateOrCreate(
                ['id' => $permission['id']],
                [
                    'menu_id'    => $permission['menu_id'],
                    'name'       => $permission['name'],
                    'alias'      => $permission['alias'] ?? null,
                    'guard_name' => $permission['guard_name'] ?? 'web',
                    'created_at' => $permission['created_at'],
                    'updated_at' => $permission['updated_at'],
                ]
            );
        }

        $this->command->info('PermissionSeeder berhasil dijalankan.');
    }
}
