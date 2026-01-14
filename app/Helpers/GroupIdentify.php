<?php

namespace App\Helpers;

class GroupIdentify
{
    /* =====================================================
     |  KONJUNGSI (AND)
     |  Semua parameter harus memenuhi
     ===================================================== */

    // 🔒 Ketat (===)
    public static function andStrict(...$values): bool
    {
        if (count($values) < 2) return false;

        $first = array_shift($values);

        foreach ($values as $value) {
            if ($value !== $first) {
                return false;
            }
        }

        return true;
    }

    // 🟡 Longgar (==)
    public static function andLoose(...$values): bool
    {
        if (count($values) < 2) return false;

        $first = array_shift($values);

        foreach ($values as $value) {
            if ($value != $first) {
                return false;
            }
        }

        return true;
    }

    /* =====================================================
     |  DISJUNGSI (OR)
     |  Minimal satu yang sama
     ===================================================== */

    // 🔒 Ketat (===)
    public static function orStrict(...$values): bool
    {
        if (count($values) < 2) return false;

        foreach ($values as $i => $a) {
            foreach ($values as $j => $b) {
                if ($i !== $j && $a === $b) {
                    return true;
                }
            }
        }

        return false;
    }

    // 🟡 Longgar (==)
    public static function orLoose(...$values): bool
    {
        if (count($values) < 2) return false;

        foreach ($values as $i => $a) {
            foreach ($values as $j => $b) {
                if ($i !== $j && $a == $b) {
                    return true;
                }
            }
        }

        return false;
    }

    /* =====================================================
     |  IMPLIKASI (→)
     |  Jika A benar maka B harus benar
     ===================================================== */

    // 🔒 Ketat
    public static function impliesStrict($a, $b): bool
    {
        return !($a === true) || ($b === true);
    }

    // 🟡 Longgar
    public static function impliesLoose($a, $b): bool
    {
        return !$a || $b;
    }

    /* =====================================================
     |  EKUIVALENSI (↔)
     |  Nilai kebenaran sama
     ===================================================== */

    // 🔒 Ketat
    public static function equivalentStrict($a, $b): bool
    {
        return $a === $b;
    }

    // 🟡 Longgar
    public static function equivalentLoose($a, $b): bool
    {
        return $a == $b;
    }
}
