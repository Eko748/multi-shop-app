<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Brand;
use App\Models\JenisBarang;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Milon\Barcode\Facades\DNS1DFacade;
use Illuminate\Support\Str; // pastikan pakai ini jika ingin gunakan Str::uuid()

class BarangSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('brand')->truncate();
        DB::table('jenis_barang')->truncate();
        DB::table('barang')->truncate();
        DB::table('pembelian_barang')->truncate();
        DB::table('detail_pembelian_barang')->truncate();
        DB::table('stock_barang')->truncate();
        DB::table('detail_stock')->truncate();
        DB::table('detail_toko')->truncate();
        DB::table('temp_detail_pengiriman')->truncate();
        DB::table('pengiriman_barang')->truncate();
        DB::table('detail_pengiriman_barang')->truncate();
        DB::table('kasir')->truncate();
        DB::table('detail_kasir')->truncate();
        DB::table('data_retur')->truncate();
        DB::table('detail_retur')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Hapus semua barcode dan QRCode dari disk 'public'
        $this->deleteStorageFiles('barcodes');
        $this->deleteStorageFiles('qrcodes/pembelian');
        $this->deleteStorageFiles('qrcodes/trx_kasir');
        $this->deleteStorageFiles('gambar_barang');

        // Jenis dan Brand awal
        $jenisBarang1 = JenisBarang::create(["id" => 1, "nama_jenis_barang" => "Laptop"]);
        $brandBarang1 = Brand::create(["id" => 1, "nama_brand" => "Asus"]);

        // Barcode dan barang pertama
        $this->createBarangWithBarcode([
            'nama_barang' => 'Asus ROG Zephyrus G14',
            'garansi' => 'No',
            'jenis' => $jenisBarang1,
            'brand' => $brandBarang1,
            'level_harga' => ["Level 1 : 1200", "Level 2 : 1300", "Level 3 : 1400", "User 1 : 2000", "User 2 : 2100"],
            'id' => 1
        ]);

        $jenisBarang2 = JenisBarang::create(["id" => 2, "nama_jenis_barang" => "Smartphone"]);
        $brandBarang2 = Brand::create(["id" => 2, "nama_brand" => "Samsung"]);

        // Barcode dan barang kedua
        $this->createBarangWithBarcode([
            'nama_barang' => 'Samsung Galaxy S21',
            'garansi' => 'Yes',
            'jenis' => $jenisBarang2,
            'brand' => $brandBarang2,
            'level_harga' => ["Level 1 : 2100", "Level 2 : 2200", "Level 3 : 2300", "User 1 : 3000", "User 2 : 3200"],
            'id' => 2
        ]);

        // Sisanya
        Brand::insert([
            ["id" => 3, "nama_brand" => "Vivo"],
            ["id" => 4, "nama_brand" => "Axioo"],
            ["id" => 5, "nama_brand" => "Acer"],
            ["id" => 6, "nama_brand" => "MSI"],
            ["id" => 7, "nama_brand" => "NYK Nemesis"],
            ["id" => 8, "nama_brand" => "Ugreen"],
        ]);

        JenisBarang::insert([
            ["id" => 3, "nama_jenis_barang" => "Aksesoris"],
            ["id" => 4, "nama_jenis_barang" => "Tools"],
            ["id" => 5, "nama_jenis_barang" => "Elektronik"],
        ]);
    }

    private function deleteStorageFiles(string $folder)
    {
        $disk = Storage::disk('public');
        if ($disk->exists($folder)) {
            $files = $disk->allFiles($folder);
            $disk->delete($files);
        }
    }

    private function createBarangWithBarcode(array $data)
    {
        $initials = strtoupper(substr($data['jenis']->nama_jenis_barang, 0, 1) . substr($data['brand']->nama_brand, 0, 1));
        $barcodeValue = $initials . random_int(100000, 999999);
        $barcodeFilename = "barcodes/{$barcodeValue}.png";

        // Generate barcode jika belum ada
        if (!Storage::disk('public')->exists($barcodeFilename)) {
            $barcodeImage = DNS1DFacade::getBarcodePNG($barcodeValue, 'C128', 3, 100);
            Storage::disk('public')->put($barcodeFilename, base64_decode($barcodeImage));
        }

        Barang::create([
            'id' => $data['id'],
            'garansi' => $data['garansi'],
            'barcode' => $barcodeValue,
            'barcode_path' => $barcodeFilename,
            'nama_barang' => $data['nama_barang'],
            'id_jenis_barang' => $data['jenis']->id,
            'id_brand_barang' => $data['brand']->id,
            'level_harga' => json_encode($data['level_harga']),
            'is_old' => true, // karena ini seeder, tandai sebagai barang lama
        ]);
    }
}

