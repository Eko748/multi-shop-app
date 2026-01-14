<?php

namespace App\Helpers;

class RupiahGenerate
{
    public static function build($value, bool $withPrefix = true): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $formatted = number_format((float) $value, 2, ',', '.');

        if (str_ends_with($formatted, ',00')) {
            $formatted = substr($formatted, 0, -3);
        }

        return $withPrefix ? "Rp {$formatted}" : $formatted;
    }
}
