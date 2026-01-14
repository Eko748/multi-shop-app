<?php

namespace App\Enums;

enum TipeKas: string
{
    case KAS_BESAR = 'besar';
    case KAS_KECIL = 'kecil';

    public function label(): string
    {
        return match ($this) {
            self::KAS_BESAR => 'Kas Besar',
            self::KAS_KECIL => 'Kas Kecil',
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
