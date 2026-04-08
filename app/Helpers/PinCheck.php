<?php

namespace App\Helpers;

use App\Models\Toko;
use Illuminate\Support\Facades\Hash;

class PinCheck
{
    public static function validate($tokoId, $inputPin)
    {
        $toko = Toko::find($tokoId);

        if (!$toko) {
            return [
                'status' => false,
                'message' => 'Toko tidak ditemukan'
            ];
        }

        // 🔐 Kalau PIN sudah di-hash
        if (strlen($toko->pin) > 20) {
            if (!Hash::check($inputPin, $toko->pin)) {
                return [
                    'status' => false,
                    'message' => 'PIN salah'
                ];
            }
        } else {
            // ⚠️ fallback kalau masih plain text
            if ($inputPin != $toko->pin) {
                return [
                    'status' => false,
                    'message' => 'PIN salah'
                ];
            }
        }

        return [
            'status' => true,
            'message' => 'PIN valid'
        ];
    }
}
