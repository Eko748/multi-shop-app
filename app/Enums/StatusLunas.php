<?php

namespace App\Enums;

enum StatusLunas: int
{
    case L = 1;
    case BL = 0;

    public function label(): string
    {
        return match ($this) {
            self::L => 'Lunas',
            self::BL => 'Belum Lunas',
        };
    }

    public function attr(): string
    {
        return match ($this) {
            self::L => 'success',
            self::BL => 'danger',
        };
    }

    public function type(): string
    {
        return match ($this) {
            self::L => 'OUT',
            self::BL => 'IN',
        };
    }

    public function reverseType(): string
    {
        return match ($this) {
            self::L => 'IN',
            self::BL => 'OUT',
        };
    }

    public function identify(): bool
    {
        return match ($this) {
            self::L => false,
            self::BL => true,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::L => 'fa-circle-check',
            self::BL => 'fa-exclamation-triangle',
        };
    }

    public static function list(): array
    {
        return [
            self::L->value => self::L->label(),
            self::BL->value => self::BL->label(),
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
