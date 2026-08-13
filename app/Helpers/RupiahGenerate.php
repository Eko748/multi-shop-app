<?php

namespace App\Helpers;

class RupiahGenerate
{
    public static function build($value, bool $withPrefix = true): ?string
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return $withPrefix ? 'Rp 0' : '0';
        }

        $numericValue = (float) $value;
        $isNegative = $numericValue < 0;

        // Gunakan abs() untuk format angkanya saja
        $formatted = number_format(abs($numericValue), 2, ',', '.');

        if (str_ends_with($formatted, ',00')) {
            $formatted = substr($formatted, 0, -3);
        }

        if ($withPrefix) {
            // Jika minus, tanda '-' ditaruh paling depan
            return $isNegative ? "-Rp {$formatted}" : "Rp {$formatted}";
        }

        return $isNegative ? "-{$formatted}" : $formatted;
    }

    public static function laba($value, bool $withPrefix = true): ?string
    {
        if ($value === null || $value === '') {
            return 'Rp 0';
        }

        if (! is_numeric($value)) {
            return 'Rp 0';
        }

        $formatted = number_format((float) $value, 6, ',', '.');

        if (str_ends_with($formatted, ',00')) {
            $formatted = substr($formatted, 0, -3);
        }

        return $withPrefix ? "Rp {$formatted}" : $formatted;
    }
}
