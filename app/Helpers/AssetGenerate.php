<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class AssetGenerate
{
    public static function build(string $storagePath, string $disk = 'public'): ?string
    {
        if (!Storage::disk($disk)->exists($storagePath)) {
            return null;
        }

        $file = Storage::disk($disk)->get($storagePath);

        $ext = pathinfo($storagePath, PATHINFO_EXTENSION);

        $mimeMap = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
        ];

        $mime = $mimeMap[$ext] ?? 'image/png';

        return "data:{$mime};base64," . base64_encode($file);
    }
}
