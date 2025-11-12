<?php

namespace App\Imports;

use App\Models\Barang;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;
use Milon\Barcode\Facades\DNS1DFacade;

class BarangImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows->skip(1) as $row) {
            try {
                // Validasi data
                if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4])) {
                    throw new \Exception('Data tidak valid pada baris: ' . json_encode($row));
                }

                $barcodeValue = $row[0] ?: $this->generateIncrementalBarcode();

                if (Barang::where('barcode', $barcodeValue)->exists()) {
                    continue;
                }

                // Nama file barcode
                $barcodeFilename = "{$row[0]}.png";
                $barcodeRelativePath = "barcodes/{$barcodeFilename}";

                // Cek apakah barcode sudah ada di storage
                if (!Storage::disk('public')->exists($barcodeRelativePath)) {
                    $barcodeImage = DNS1DFacade::getBarcodePNG($row[0], 'C128', 3, 100);

                    if (!$barcodeImage) {
                        throw new \Exception('Gagal membuat barcode JPG dari base64');
                    }

                    // Simpan barcode ke storage
                    Storage::disk('public')->put($barcodeRelativePath, base64_decode($barcodeImage));
                }

                // Simpan data ke database
                Barang::create([
                    'barcode' => $row[0],
                    'nama_barang' => $row[1],
                    'id_jenis_barang' => $row[2],
                    'id_brand_barang' => $row[3],
                    'garansi' => $row[4],
                    'barcode_path' => $barcodeRelativePath, // Path relatif dari public
                    'gambar_path' => null,
                    'level_harga' => null,
                    'is_old' => true,
                ]);
            } catch (\Exception $e) {
                Log::error('Error pada baris: ' . json_encode($row) . ' - ' . $e->getMessage());
                continue;
            }
        }
    }

    private function generateIncrementalBarcode(): string
    {
        $lastBarcode = Barang::whereRaw('LENGTH(barcode) = 6')
            ->where('barcode', 'REGEXP', '^[0-9]+$')
            ->orderByDesc('barcode')
            ->value('barcode');

        $lastNumber = $lastBarcode ? intval($lastBarcode) : 0;
        $newNumber = $lastNumber + 1;

        return str_pad($newNumber, 6, '0', STR_PAD_LEFT); // jadi format 000001
    }
}
