<?php

namespace App\Enums;

enum LabelKas: int
{
    case KAS_BESAR = 0;
    case KAS_KECIL = 1;

    public function label(): string
    {
        return match ($this) {
            self::KAS_BESAR => 'Kas Besar (Owner)',
            self::KAS_KECIL => 'Kas Kecil (Toko)',
        };
    }

    public static function list(): array
    {
        return [
            self::KAS_BESAR->value => self::KAS_BESAR->label(),
            self::KAS_KECIL->value => self::KAS_KECIL->label(),
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
