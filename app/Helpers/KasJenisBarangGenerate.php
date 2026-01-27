<?php

namespace App\Helpers;

use App\Enums\TipeKas;
use App\Models\JenisBarang;

class KasJenisBarangGenerate
{
    protected static ?array $cache = null;

    public static function list(): array
    {
        if (self::$cache === null) {
            self::$cache = JenisBarang::pluck('nama_jenis_barang', 'id')->toArray();
        }

        return self::$cache;
    }

    public static function label(int|string|null $id): ?string
    {
        if ($id === null) {
            return null;
        }

        if ((string)$id === '0') {
            return 'Dompet Digital';
        }

        $list = self::list();
        $id = (int) $id;

        return $list[$id] ?? null;
    }

    public static function labelFull(string $labelText, int|string|null $id): string
    {
        $jenis = self::label($id);

        return $jenis
            ? "{$labelText} - {$jenis}"
            : $labelText;
    }

    public static function labelForKas(object $item, $extra = null, $saldo = false): string
    {
        $toko = $item->toko ?? $item->kas->toko_id ?? null;

        $tipeKasValue = $item->kas->tipe_kas ?? $item->tipe_kas ?? null;
        $labelTipeKas = $tipeKasValue
            ? TipeKas::from($tipeKasValue)->label()
            : '';

        if (!$toko) {
            return $extra && $labelTipeKas
                ? "{$labelTipeKas} {$extra}"
                : ($labelTipeKas ?: ($extra ?? ''));
        }

        $jenisBarangId = $item->kas->jenis_barang_id
            ?? $item->jenis_barang_id
            ?? '0';

        $jenis = self::label($jenisBarangId);

        if (!$jenis) {
            return $extra && $labelTipeKas
                ? "{$labelTipeKas} {$extra}"
                : ($labelTipeKas ?: ($extra ?? ''));
        }

        if ($extra) {
            return "{$labelTipeKas} {$extra} - {$jenis}";
        }

        if ($saldo) {
            $ss = RupiahGenerate::build($item->kas->saldo);
            return "{$labelTipeKas} - {$jenis} ({$ss})";
        }

        return "{$labelTipeKas} - {$jenis}";
    }

    public static function labelForKasMutasi(object $item, ?string $extra = null): string
    {
        // Tentukan asal data berdasarkan extra
        $kasData = $extra === 'OUT' ? $item->kasAsal : $item->kasTujuan;

        $toko        = $kasData->toko ?? null;
        $tipeKas     = $kasData->tipe_kas;
        $jenisBarang = $kasData->jenis_barang_id;

        $labelTipeKas = TipeKas::from($tipeKas)->label();

        // Jika toko tidak punya kas_detail → tidak ada jenis barang
        if (!$toko || !$toko->kas_detail) {
            return $extra
                ? "{$labelTipeKas} {$extra}"
                : $labelTipeKas;
        }

        $jenis = self::label($jenisBarang);

        // Jika jenis tidak ditemukan
        if (!$jenis) {
            return $extra
                ? "{$labelTipeKas} {$extra}"
                : $labelTipeKas;
        }

        // Semua kondisi terpenuhi
        return $extra
            ? "{$labelTipeKas} {$extra} - {$jenis}"
            : "{$labelTipeKas} - {$jenis}";
    }
}
