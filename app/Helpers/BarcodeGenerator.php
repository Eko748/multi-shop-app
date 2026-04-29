<?php

namespace App\Helpers;

use App\Models\Barang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Milon\Barcode\Facades\DNS1DFacade;

class BarcodeGenerator
{
    public static function generateIncremental(): string
    {
        do {
            $barcode = (string) random_int(100000, 999999);
        } while (
            Barang::where('barcode', $barcode)->exists()
        );

        return $barcode;
    }

    public static function generateImage(
        string $barcodeValue,
        string $folder = 'barcodes/'
    ): string {
        $filename = $barcodeValue . '.png';
        $path = $folder . $filename;

        if (!Storage::disk('public')->exists($path)) {
            $png = DNS1DFacade::getBarcodePNG(
                $barcodeValue,
                'C128',
                3,
                100
            );

            Storage::disk('public')->put(
                $path,
                base64_decode($png)
            );
        }

        return $path;
    }

    public static function generate(
        string $folder = 'barcodes/'
    ): array {
        $value = self::generateIncremental();
        $path = self::generateImage($value, $folder);

        return [
            'value' => $value,
            'path'  => $path,
        ];
    }
}
