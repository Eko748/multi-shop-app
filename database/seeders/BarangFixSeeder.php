<?php

namespace Database\Seeders;

use App\Helpers\BarcodeGenerator;
use App\Helpers\QrGenerator;
use App\Models\Barang;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BarangFixSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = storage_path('app/private/json/barang.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("File tidak ditemukan: {$jsonPath}");
            return;
        }

        $json = File::get($jsonPath);
        $decode = json_decode($json, true);

        if (!isset($decode['barang']) || !is_array($decode['barang'])) {
            $this->command->error("Format JSON salah. Key 'barang' tidak ditemukan.");
            return;
        }

        $rows  = $decode['barang'];
        $total = count($rows);

        $this->command->info("Total data JSON : {$total}");
        $this->command->info("Memulai migrasi data barang...");

        $bar = $this->command->getOutput()->createProgressBar($total);
        $bar->start();

        DB::beginTransaction();

        try {
            $inserted = 0;
            $skippedDeleted = 0;
            $fixedBarcode = 0;
            $fixedRelation = 0;
            $errors = 0;

            /**
             * Untuk menampilkan barang yg barcode diperbaiki
             */
            $fixedBarcodeItems = [];

            $usedBarcodes = Barang::pluck('barcode')
                ->filter()
                ->toArray();

            $insertData = [];

            foreach ($rows as $item) {
                try {

                    if (!empty($item['deleted_at'])) {
                        $skippedDeleted++;
                        $bar->advance();
                        continue;
                    }

                    $nama = trim($item['nama_barang'] ?? '-');

                    /**
                     * Relasi fallback
                     */
                    $jenisId = (int) ($item['id_jenis_barang'] ?? 1);
                    $brandId = (int) ($item['id_brand_barang'] ?? 1);

                    if ($jenisId <= 0) {
                        $jenisId = 1;
                        $fixedRelation++;
                    }

                    if ($brandId <= 0) {
                        $brandId = 1;
                        $fixedRelation++;
                    }

                    /**
                     * Barcode
                     */
                    $oldBarcode = trim($item['barcode'] ?? '');

                    if (
                        empty($oldBarcode) ||
                        in_array($oldBarcode, $usedBarcodes, true)
                    ) {
                        $barcode = BarcodeGenerator::generateIncremental();

                        $fixedBarcode++;
                        $fixedBarcodeItems[] = [
                            'nama' => $nama,
                            'lama' => $oldBarcode ?: '(kosong)',
                            'baru' => $barcode,
                        ];
                    } else {
                        $barcode = $oldBarcode;
                    }

                    $usedBarcodes[] = $barcode;

                    BarcodeGenerator::generateImage($barcode, 'barcodes/');

                    $qrcode = QrGenerator::generate(
                        'QR-',
                        'qrcodes/barang/'
                    )['value'];

                    $insertData[] = [
                        'nama' => $nama,
                        'barcode' => $barcode,
                        'qrcode' => $qrcode,
                        'jenis_barang_id' => $jenisId,
                        'brand_id' => $brandId,
                        'gambar' => $item['gambar_path'] ?? null,
                        'garansi' => $this->parseGaransi($item['garansi'] ?? 0),
                        'created_by' => 1,
                        'updated_by' => null,
                        'created_at' => $item['created_at'] ?? now(),
                        'updated_at' => $item['updated_at'] ?? now(),
                    ];

                    if (count($insertData) >= 100) {
                        Barang::insert($insertData);
                        $inserted += count($insertData);
                        $insertData = [];
                    }

                } catch (\Throwable $e) {
                    $errors++;

                    $this->command->warn(
                        "Skip item: {$nama} | {$e->getMessage()}"
                    );
                }

                $bar->advance();
            }

            if (!empty($insertData)) {
                Barang::insert($insertData);
                $inserted += count($insertData);
            }

            DB::commit();

            $bar->finish();

            echo PHP_EOL . PHP_EOL;

            $this->command->info("=== MIGRASI SELESAI ===");
            $this->command->info("Inserted      : {$inserted}");
            $this->command->info("Skip Deleted  : {$skippedDeleted}");
            $this->command->info("Fix Barcode   : {$fixedBarcode}");
            $this->command->info("Fix Relation  : {$fixedRelation}");
            $this->command->info("Error Item    : {$errors}");

            /**
             * Tampilkan detail barcode yg diperbaiki
             */
            if (!empty($fixedBarcodeItems)) {

                echo PHP_EOL;
                $this->command->warn("=== DETAIL BARCODE DIPERBAIKI ===");

                foreach ($fixedBarcodeItems as $row) {
                    $this->command->line(
                        "Nama : {$row['nama']}"
                    );
                    $this->command->line(
                        "Lama : {$row['lama']} -> Baru : {$row['baru']}"
                    );
                    $this->command->line(str_repeat('-', 50));
                }
            }

        } catch (\Throwable $e) {
            DB::rollBack();

            echo PHP_EOL;

            $this->command->error("Seeder gagal total:");
            $this->command->error($e->getMessage());
        }
    }

    private function parseGaransi($value): int
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, [
            '1',
            'yes',
            'ya',
            'true',
            'y'
        ], true) ? 1 : 0;
    }
}
