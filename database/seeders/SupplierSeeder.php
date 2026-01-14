<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = storage_path('app/private/json/supplier.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("File supplier.json tidak ditemukan di: $jsonPath");
            return;
        }

        $json = File::get($jsonPath);
        $data = json_decode($json, true);

        if (!isset($data['supplier'])) {
            $this->command->error("Data 'supplier' tidak ditemukan dalam file JSON.");
            return;
        }

        foreach ($data['supplier'] as $item) {
            Supplier::updateOrCreate(
                ['id' => $item['id']],
                [
                    'nama' => $item['nama_supplier'],
                    'email'         => $item['email'],
                    'alamat'        => $item['alamat'],
                    'telepon'       => $item['contact'],
                    'deleted_at'    => $item['deleted_at'],
                ]
            );
        }

        $this->command->info("Seeder Supplier berhasil dijalankan.");
    }
}
