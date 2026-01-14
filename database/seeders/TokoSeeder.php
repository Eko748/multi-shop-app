<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TokoSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = storage_path('app/private/json/toko.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("File toko.json tidak ditemukan di: $jsonPath");
            return;
        }

        $json = File::get($jsonPath);
        $data = json_decode($json, true);

        if (!isset($data['toko'])) {
            $this->command->error("Data 'toko' tidak ditemukan dalam file JSON.");
            return;
        }

        foreach ($data['toko'] as $item) {
            DB::table('toko')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'parent_id' => $item['parent_id'],
                    'nama' => $item['nama'],
                    'singkatan' => $item['singkatan'],
                    'level_harga' => $item['level_harga'],
                    'wilayah' => $item['wilayah'],
                    'alamat' => $item['alamat'],
                    'deleted_at' => $item['deleted_at'],
                ]
            );
        }

        $this->command->info("Seeder Toko berhasil dijalankan.");
    }
}
