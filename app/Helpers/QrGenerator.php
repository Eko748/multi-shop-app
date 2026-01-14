<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;

class QrGenerator
{
    /**
     * Generate QR + simpan ke storage
     */
    public static function generate()
    {
        $qrValue = self::generateValue();

        $fileName = "{$qrValue}.png";
        $path = "qrcodes/pembelian/{$fileName}";

        if (!Storage::disk('public')->exists($path)) {
            $qrCode = QrCode::create($qrValue)
                ->setEncoding(new Encoding('UTF-8'))
                ->setSize(200)
                ->setMargin(10);

            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            Storage::disk('public')->put($path, $result->getString());
        }

        return [
            'value' => $qrValue,
            'path'  => $path,
            'file'  => $fileName
        ];
    }

    /**
     * 🔹 HANYA generate nama file (tanpa QR, tanpa storage)
     */
    public static function generateFileName()
    {
        $qrValue = self::generateValue();

        return "{$qrValue}.png";
    }

    /**
     * 🔹 Centralized QR value generator
     */       
    private static function generateValue()
    {
        return 'QR-' . bin2hex(random_bytes(6));
    }
}
