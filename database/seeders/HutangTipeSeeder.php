<?php

namespace Database\Seeders;

use App\Models\HutangTipe;
use Illuminate\Database\Seeder;

class HutangTipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HutangTipe::create([
            "id" => 1,
            "tipe" => "Pembelian Barang",
        ]);

        HutangTipe::create([
            "id" => 2,
            "tipe" => "Pengiriman Barang",
        ]);

        HutangTipe::create([
            "id" => 3,
            "tipe" => "Bank",
        ]);

        HutangTipe::create([
            "id" => 4,
            "tipe" => "Pinjaman",
        ]);

        HutangTipe::create([
            "id" => 5,
            "tipe" => "Lainnya",
        ]);
    }
}
