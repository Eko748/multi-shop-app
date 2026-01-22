<?php

namespace App\Helpers;

class FormatHarga
{
    /**
     * Normalisasi level harga menjadi array numeric murni
     *
     * @param mixed $value
     * @return array
     */
    public static function array($value): array
    {
        if (empty($value)) {
            return [];
        }

        // Jika sudah array
        if (is_array($value)) {
            return array_values(array_map('floatval', $value));
        }

        // Jika JSON string
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return array_values(array_map('floatval', $decoded));
            }

            // Jika string koma: "11000,12000,13000"
            if (str_contains($value, ',')) {
                return array_values(array_map('floatval', explode(',', $value)));
            }

            // Jika numeric string tunggal
            if (is_numeric($value)) {
                return [(float) $value];
            }
        }

        return [];
    }
}
