<?php

namespace App\Traits;

use App\Enums\TipeKas;
use App\Models\JenisBarang;

trait HasJenisBarang
{
    protected function jenisBarangList(): array
    {
        static $cache = null;

        if ($cache === null) {
            $cache = JenisBarang::pluck('nama_jenis_barang', 'id')->toArray();
        }

        return $cache;
    }

    public function jenisBarangLabel(int|string|null $id): ?string
    {
        if ($id === null) {
            return null;
        }

        if ((string)$id === '0') {
            return 'Dompet Digital';
        }

        $list = $this->jenisBarangList();

        $id = (int) $id;

        return $list[$id] ?? null;
    }

    public function jenisBarangLabelFull(
        string $labelText,
        int|string|null $id
    ): string {
        $jenis = $this->jenisBarangLabel($id);

        return $jenis
            ? "{$labelText} - {$jenis}"
            : $labelText;
    }

    public function jenisBarangLabelForKas(object $item): string
    {
        $toko = $item->toko ?? null;

        $labelTipeKas = TipeKas::from($item->kas->tipe_kas)->label();

        if (!$toko || !$toko->kas_detail) {
            return $labelTipeKas;
        }

        $jenis = $this->jenisBarangLabel($item->kas->jenis_barang_id);

        return $jenis
            ? "{$labelTipeKas} - {$jenis}"
            : $labelTipeKas;
    }
}
