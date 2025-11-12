<?php

namespace App\Imports;

use App\Models\PembelianBarang;
use App\Models\DetailPembelianBarang;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PembelianBarangImport implements ToCollection, WithHeadingRow, WithValidation
{
    private array $errors = [];
    private array $pembelianCache = [];

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function collection(Collection $rows)
    {
        $pembelianMap = [];

        // Ambil daftar level harga yang tersedia dari tabel level_harga
        $availableLevels = \App\Models\LevelHarga::pluck('nama_level_harga')->toArray(); // urut sesuai ID atau urutan DB
        $maxLevel = count($availableLevels);

        foreach ($rows as $row) {
            DB::beginTransaction();
            try {
                if (
                    !isset($row['no_nota']) || trim($row['no_nota']) === '' ||
                    !isset($row['id_barang']) || trim($row['id_barang']) === '' ||
                    !isset($row['qty']) || !is_numeric($row['qty']) ||
                    !isset($row['harga_barang']) || !is_numeric($row['harga_barang'])
                ) {
                    throw new \Exception('Data tidak valid pada baris: ' . json_encode($row));
                }

                $baseQrCode = $this->prepareBaseQrCode($row['qrcode'] ?? null);
                $uniqueQrCode = $this->generateUniqueQrCode($baseQrCode);

                $groupKey = $row['id_pembelian_barang'] ?? uniqid('pb_');

                if (!isset($pembelianMap[$groupKey])) {
                    $pembelianMap[$groupKey] = \App\Models\PembelianBarang::create([
                        'no_nota' => (string) $row['no_nota'],
                        'id_supplier' => !empty($row['id_supplier']) ? (string) $row['id_supplier'] : null,
                        'id_users' => '1',
                        'tgl_nota' => $this->transformExcelDate($row['tgl_nota'] ?? null) ?? now(),
                        'status' => 'success',
                    ]);
                }

                $pembelian = $pembelianMap[$groupKey];

                $qrCodeFilename = "{$uniqueQrCode}.png";
                $qrCodeRelativePath = "qrcodes/pembelian/{$qrCodeFilename}";

                if (!Storage::disk('public')->exists($qrCodeRelativePath)) {
                    $qrCode = new \Endroid\QrCode\QrCode($uniqueQrCode);
                    $writer = new \Endroid\QrCode\Writer\PngWriter();
                    $result = $writer->write($qrCode);
                    $qrCodeBase64 = base64_encode($result->getString());
                    Storage::disk('public')->put($qrCodeRelativePath, base64_decode($qrCodeBase64));
                }

                $detailPembelian = \App\Models\DetailPembelianBarang::create([
                    'id_pembelian_barang' => $pembelian->id,
                    'id_barang' => (string) $row['id_barang'],
                    'id_supplier' => !empty($row['id_supplier']) ? (string) $row['id_supplier'] : null,
                    'qty' => (int) $row['qty'],
                    'harga_barang' => (float) $row['harga_barang'],
                    'total_harga' => (float) $row['qty'] * $row['harga_barang'],
                    'qrcode' => $uniqueQrCode,
                    'qrcode_path' => $qrCodeRelativePath,
                    'status' => 'success',
                ]);

                $id_barang = (string) $row['id_barang'];
                $qty = (int) $row['qty'];
                $harga_barang = (float) $row['harga_barang'];
                $id_supplier = !empty($row['id_supplier']) ? (string) $row['id_supplier'] : null;

                $barang = \App\Models\Barang::find($id_barang);
                $stockBarang = \App\Models\StockBarang::firstOrNew(['id_barang' => $id_barang]);

                $hpp_awal = $stockBarang->hpp_baru ?: $stockBarang->hpp_awal ?: $harga_barang;
                $stock_awal = $stockBarang->stock ?: 0;

                $qty_detail_toko = DB::table('detail_toko')->where('id_barang', $id_barang)->sum('qty');
                $total_stock_lama = $stock_awal + $qty_detail_toko;
                $nilai_total_lama = $total_stock_lama * $hpp_awal;
                $nilai_pembelian_baru = $qty * $harga_barang;
                $total_qty_baru = $total_stock_lama + $qty;

                $hpp_baru = $total_qty_baru > 0
                    ? ($nilai_total_lama + $nilai_pembelian_baru) / $total_qty_baru
                    : $hpp_awal;

                $stockBarang->stock = $stock_awal + $qty;
                $stockBarang->hpp_awal = $hpp_awal;
                $stockBarang->hpp_baru = $hpp_baru;
                $stockBarang->nilai_total = $hpp_baru * $stockBarang->stock;
                $stockBarang->nama_barang = $barang?->nama_barang ?? '-';
                $stockBarang->save();

                \App\Models\DetailStockBarang::create([
                    'id_stock' => $stockBarang->id,
                    'id_barang' => $id_barang,
                    'id_supplier' => $id_supplier,
                    'id_pembelian' => $pembelian->id,
                    'id_detail_pembelian' => $detailPembelian->id,
                    'qty_buy' => $qty,
                    'qty_now' => $qty,
                ]);

                // === Proses level harga ===
                $levelHargaBarang = [];

                if (!empty($row['level_harga'])) {
                    // $levelHargaInput = json_decode($row['level_harga'], true);
                    $rawLevelHarga = $row['level_harga'] ?? '';
                    $levelHargaInput = [];

                    if (!empty($rawLevelHarga)) {
                        if (str_starts_with($rawLevelHarga, '[')) {
                            // Format JSON seperti ["17000", "18000", ...]
                            $levelHargaInput = json_decode($rawLevelHarga, true);
                        } else {
                            // Format ringkas seperti 17000, 18000, ...
                            $levelHargaInput = array_map('trim', explode(',', $rawLevelHarga));
                        }
                    }

                    if (is_array($levelHargaInput)) {
                        foreach ($availableLevels as $i => $levelNama) {
                            $harga = $levelHargaInput[$i] ?? null;
                            if (!is_null($harga) && is_numeric($harga)) {
                                $levelHargaBarang[] = "{$levelNama} : {$harga}";
                            }
                        }
                    }
                }

                if ($barang && !empty($levelHargaBarang)) {
                    $barang->level_harga = json_encode($levelHargaBarang);
                    $barang->save();

                    $stockBarang->level_harga = json_encode($levelHargaBarang);
                    $stockBarang->save();
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->errors[] = 'Baris ' . ($row['no'] ?? '-') . ': ' . $e->getMessage();
            }
        }

        foreach ($pembelianMap as $pembelian) {
            $totals = \App\Models\DetailPembelianBarang::where('id_pembelian_barang', $pembelian->id)
                ->selectRaw('COALESCE(SUM(qty), 0) as total_item, COALESCE(SUM(total_harga), 0) as total_nilai')
                ->first();

            $pembelian->update([
                'total_item' => $totals->total_item,
                'total_nilai' => $totals->total_nilai,
                'status' => 'success',
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'no_nota' => 'required|string',
            'id_supplier' => 'nullable|string',
            'id_supplier' => [
                'nullable',
                'integer',
                Rule::exists('supplier', 'id')->whereNull('deleted_at'),
            ],
            'qty' => 'required|numeric|min:1',
            'harga_barang' => 'required|numeric|min:0',
            'qrcode' => 'nullable',
        ];
    }

    private function transformExcelDate($excelDate)
    {
        // Jika null atau kosong
        if (empty($excelDate)) return null;

        // Jika sudah berupa string tanggal, langsung parse
        if (is_string($excelDate) && strtotime($excelDate)) {
            return date('Y-m-d', strtotime($excelDate));
        }

        // Jika angka, konversi dari format Excel
        if (is_numeric($excelDate)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelDate)->format('Y-m-d');
        }

        return null;
    }

    /**
     * Siapkan base QR code dari input pengguna atau buat otomatis (PB00001, dst)
     */
    private function prepareBaseQrCode(?string $inputQr): string
    {
        if (!empty($inputQr)) {
            // Tambahkan prefix "PB" jika belum ada
            return str_starts_with($inputQr, 'PB') ? $inputQr : 'PB' . $inputQr;
        }

        // Jika kosong, buat auto-increment
        $lastQr = DetailPembelianBarang::where('qrcode', 'like', 'PB%')
            ->orderBy('id', 'desc')
            ->value('qrcode');

        // Ambil angka terakhir dari QR yang ditemukan
        $number = 1;
        if ($lastQr && preg_match('/^PB(\d+)/', $lastQr, $match)) {
            $number = (int) $match[1] + 1;
        }

        return 'PB' . str_pad($number, 5, '0', STR_PAD_LEFT); // Misalnya PB00001
    }

    /**
     * Generate QR code unik dari string awal
     */
    private function generateUniqueQrCode(string $baseQrCode): string
    {
        $suffix = '';
        $attempt = 0;
        $maxAttempts = 1000;

        do {
            $candidate = $baseQrCode . $suffix;
            $exists = DetailPembelianBarang::where('qrcode', $candidate)->exists();

            if (!$exists) {
                return $candidate;
            }

            $suffix = $this->intToLetters(++$attempt);
        } while ($attempt < $maxAttempts);

        throw new \Exception("Gagal membuat QR code unik untuk: {$baseQrCode}");
    }


    /**
     * Ubah angka ke huruf seperti Excel: A, B, ..., Z, AA, AB, ...
     */
    private function intToLetters(int $n): string
    {
        $result = '';
        while ($n > 0) {
            $n--;
            $result = chr(65 + ($n % 26)) . $result;
            $n = intdiv($n, 26);
        }
        return $result;
    }
}
