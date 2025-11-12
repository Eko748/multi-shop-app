<?php

namespace App\Enums;

enum StatusStockBarang: string
{
    case HILANG = 'hilang';
    case MATI = 'mati';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
