<?php

namespace App\Helpers;

class RupiahGenerate
{
    public static function build($value, bool $withPrefix = true): ?string
    {
        if ($value === null || $value === '') {
            return "Rp 0";
        }

        if (!is_numeric($value)) {
            return "Rp 0";
        }

        $formatted = number_format((float) $value, 2, ',', '.');

        if (str_ends_with($formatted, ',00')) {
            $formatted = substr($formatted, 0, -3);
        }

        return $withPrefix ? "Rp {$formatted}" : $formatted;
    }
}
