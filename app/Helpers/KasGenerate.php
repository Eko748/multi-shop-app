<?php

namespace App\Helpers;

use App\Models\Kas;

class KasGenerate
{
    public static function resolveKasId(
        string $kasId,
        int $tokoId,
        int $jenisBarangId,
        string $tipeKas,
        $tanggal
    ) {

        if (str_starts_with($kasId, 'NEW-')) {
            $kasBaru = Kas::create([
                'toko_id' => $tokoId,
                'jenis_barang_id' => $jenisBarangId,
                'tipe_kas' => $tipeKas,
                'saldo_awal' => 0,
                'saldo' => 0,
                'tanggal' => $tanggal
            ]);

            return $kasBaru->id;
        }

        $kas = Kas::find($kasId);

        if (!$kas) {
            throw new \Exception("Kas dengan ID {$kasId} tidak ditemukan.");
        }

        return $kas->id;
    }
}
