<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = storage_path('app/private/json/brand.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("File brand.json tidak ditemukan di: $jsonPath");
            return;
        }

        $json = File::get($jsonPath);
        $data = json_decode($json, true);

        if (!isset($data['brand'])) {
            $this->command->error("Data 'brand' tidak ditemukan dalam file JSON.");
            return;
        }

        foreach ($data['brand'] as $item) {
            DB::table('brand')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'nama_brand' => $item['nama_brand'],
                    'deleted_at' => $item['deleted_at'],
                ]
            );
        }

        $this->command->info("Seeder Brand berhasil dijalankan.");
    }
}
