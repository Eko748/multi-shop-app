<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\JenisBarang;
use App\Models\LevelHarga;
use App\Models\Member;
use App\Models\Supplier;
use App\Models\Toko;
use Illuminate\Database\Seeder;
use Monolog\Level;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        $this->call([
            RoleSeeder::class,
            LevelHargaSeeder::class,
            BrandSeeder::class,
            JenisBarangSeeder::class,
            JenisPemasukanSeeder::class,
            JenisPengeluaranSeeder::class,
            JenisHutangSeeder::class,
            // SupplierSeeder::class,
            MenuSeeder::class,
            PermissionSeeder::class,
            RoleHasPermissionSeeder::class,
            TokoSeeder::class,
            // MemberSeeder::class,
            UserFixSeeder::class,
            // RolePermissionFromExcelSeeder::class,
            // LevelHargaSeeder::class,
            // JenisPemasukanSeeder::class,
            // JenisPengeluaranSeeder::class,
            // SupplierSeeder::class,
            // MemberSeeder::class,
            // BarangSeeder::class,
            // PembelianSeeder::class,
            // StockBarangSeeder::class,
            // DetailStockSeeder::class,
        ]);
    }
}
