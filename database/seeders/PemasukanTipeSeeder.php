<?php

namespace Database\Seeders;

use App\Models\PemasukanTipe;
use Illuminate\Database\Seeder;

class PemasukanTipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PemasukanTipe::create([
            "id" => 1,
            "tipe" => "Modal Awal",
        ]);

        PemasukanTipe::create([
            "id" => 2,
            "tipe" => "Tambahan Modal",
        ]);

        PemasukanTipe::create([
            "id" => 3,
            "tipe" => "Penjualan Lainnya",
        ]);
    }
}
