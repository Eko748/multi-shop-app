<?php

namespace Database\Seeders;

use App\Models\PengeluaranTipe;
use Illuminate\Database\Seeder;

class PengeluaranTipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PengeluaranTipe::insert([
            ["id" => 1, "tipe" => "Biaya Perlengkapan"],
            ["id" => 2, "tipe" => "Biaya Operasional"],
            ["id" => 3, "tipe" => "Biaya Gaji Staff"],
            ["id" => 4, "tipe" => "Biaya Transport"],
            ["id" => 5, "tipe" => "Biaya Listrik"],
            ["id" => 6, "tipe" => "Biaya Iklan"],
            ["id" => 7, "tipe" => "Biaya Administrasi"],
            ["id" => 8, "tipe" => "Biaya K3"],
            ["id" => 9, "tipe" => "Biaya Perbaikan Bangunan"],
            ["id" => 10, "tipe" => "Biaya Tak Terduga"],
            ["id" => 11, "tipe" => "Pembelian Aset"],
        ]);

    }
}
