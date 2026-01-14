<?php

namespace Database\Seeders;

use App\Models\PiutangTipe;
use Illuminate\Database\Seeder;

class PiutangTipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PiutangTipe::create([
            "id" => 1,
            "tipe" => "Pengiriman Barang",
        ]);

        PiutangTipe::create([
            "id" => 2,
            "tipe" => "Karyawan",
        ]);

        PiutangTipe::create([
            "id" => 3,
            "tipe" => "Member",
        ]);

        PiutangTipe::create([
            "id" => 4,
            "tipe" => "Lainnya",
        ]);
    }
}
