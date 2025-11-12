<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\File;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = storage_path('app/private/json/role.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("File role.json tidak ditemukan di: $jsonPath");
            return;
        }

        $json = File::get($jsonPath);
        $data = json_decode($json, true);

        if (!isset($data['roles'])) {
            $this->command->error("Data 'roles' tidak ditemukan dalam file JSON.");
            return;
        }

        foreach ($data['roles'] as $item) {
            Role::updateOrCreate(
                ['id' => $item['id']],
                [
                    'name' => $item['name'],
                    'guard_name' => $item['guard_name'],
                    'informasi' => $item['informasi'] ?? null,
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                ]
            );
        }

        $this->command->info("Seeder Role berhasil dijalankan.");

        // $roles = [
        //     'Super Admin' => 'Akses penuh ke seluruh sistem, Dapat mengatur semua data, pengguna, dan pengaturan sistem',
        //     'Akunting' => 'Mengelola keuangan dan laporan keuangan toko, Fokus pada pelaporan keuangan dan kasbon',
        //     'Admin GSS' => 'Mengelola operasional gudang seperti stok, pengadaan, dan distribusi barang, Tidak memiliki akses ke laporan keuangan',
        //     'Admin Toko' => 'Mengelola operasional toko termasuk transaksi penjualan, Tidak memiliki akses ke laporan keuangan',
        //     'Karyawan' => 'Melakukan transaksi penjualan di kasir, Akses terbatas hanya untuk kasir dan kasbon',
        //     'Franchise' => 'Pihak eksternal yang bermitra dan menjalankan toko cabang, Dapat melihat performa dan laporan keuangan tokonya sendiri',
        // ];

        // foreach ($roles as $name => $desc) {
        //     Role::updateOrCreate(
        //         ['name' => $name, 'guard_name' => 'web'],
        //         ['informasi' => $desc]
        //     );
        // }

        // echo "✅ RoleSeeder selesai dijalankan.\n";
    }
}
