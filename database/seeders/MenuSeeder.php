<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Models\Menu;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Baca file JSON
        $json = file_get_contents(storage_path('app/private/json/menu.json'));
        $data = json_decode($json, true);

        // Pastikan 'menus' ada
        if (!isset($data['menus'])) {
            $this->command->error("Data 'menus' tidak ditemukan dalam file JSON.");
            return;
        }

        foreach ($data['menus'] as $menu) {
            // Simpan ke database
            Menu::updateOrCreate(
                ['id' => $menu['id']],
                [
                    'name' => $menu['name'],
                    'slug' => $menu['slug'],
                    'created_at' => $menu['created_at'],
                    'updated_at' => $menu['updated_at'],
                ]
            );
        }

        $this->command->info('MenuSeeder berhasil dijalankan.');
    }
}
