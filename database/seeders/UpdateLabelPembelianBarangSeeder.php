<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PembelianBarang;

class UpdateLabelPembelianBarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PembelianBarang::query()->update(['label' => '1']);

        $this->command->info('Semua data label pada PembelianBarang berhasil diubah menjadi 1.');
    }
}
