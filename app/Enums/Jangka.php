<?php

namespace App\Enums;

enum Jangka: string
{
    case PANJANG = 'panjang';
    case PENDEK = 'pendek';

    public function label(): string
    {
        return match ($this) {
            self::PANJANG => 'Jangka Panjang',
            self::PENDEK => 'Jangka Pendek',
        };
    }

    public function labelSort(): string
    {
        return match ($this) {
            self::PANJANG => 'Panjang',
            self::PENDEK => 'Pendek',
        };
    }

    public static function list(): array
    {
        return [
            self::PANJANG->value => self::PANJANG->label(),
            self::PENDEK->value => self::PENDEK->label(),
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
