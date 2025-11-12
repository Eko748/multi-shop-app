<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class JenisBarangSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = storage_path('app/private/json/jenis_barang.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("File jenis_barang.json tidak ditemukan di: $jsonPath");
            return;
        }

        $json = File::get($jsonPath);
        $data = json_decode($json, true);

        if (!isset($data['jenis_barang'])) {
            $this->command->error("Data 'jenis_barang' tidak ditemukan dalam file JSON.");
            return;
        }

        foreach ($data['jenis_barang'] as $item) {
            DB::table('jenis_barang')->updateOrInsert(
                ['id' => $item['id']],
                [
                    'nama_jenis_barang' => $item['nama_jenis_barang'],
                    'deleted_at'        => $item['deleted_at'],
                ]
            );
        }

        $this->command->info("Seeder JenisBarang berhasil dijalankan.");
    }
}
