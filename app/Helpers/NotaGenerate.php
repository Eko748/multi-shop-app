<?php

namespace App\Helpers;

use App\Models\PenjualanNonFisik;
use App\Models\TransaksiKasir;
use App\Models\Toko;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotaGenerate
{
    public static function build(int $tokoId, ?string $tanggal = null): string
    {
        $date = $tanggal
            ? Carbon::parse($tanggal)
            : Carbon::now();

        $dateKey = $date->format('Ymd');

        $toko = Toko::findOrFail($tokoId);

        if (!empty($toko->singkatan)) {
            $kodeToko = strtoupper($toko->singkatan);
        } else {
            $kodeToko = collect(explode(' ', $toko->nama))
                ->map(fn($word) => Str::substr($word, 0, 1))
                ->implode('');

            $kodeToko = strtoupper($kodeToko);
        }

        return DB::transaction(function () use ($tokoId, $date, $dateKey, $kodeToko) {

            $lastNota = TransaksiKasir::where('toko_id', $tokoId)
                ->whereDate('tanggal', $date->toDateString())
                ->lockForUpdate()
                ->latest('id')
                ->value('nota');

            $number = $lastNota
                ? ((int) substr($lastNota, -4)) + 1
                : 1;

            return sprintf(
                'INVTK-%s-%s-%06d',
                $kodeToko,
                $dateKey,
                $number
            );
        });
    }

    public static function nonfisik(int $tokoId, ?string $tanggal = null): string
    {
        $date = $tanggal
            ? Carbon::parse($tanggal)
            : Carbon::now();

        $dateKey = $date->format('Ymd');

        $toko = Toko::findOrFail($tokoId);

        if (!empty($toko->singkatan)) {
            $kodeToko = strtoupper($toko->singkatan);
        } else {
            $kodeToko = collect(explode(' ', $toko->nama))
                ->map(fn($word) => Str::substr($word, 0, 1))
                ->implode('');

            $kodeToko = strtoupper($kodeToko);
        }

        return DB::transaction(function () use ($tokoId, $date, $dateKey, $kodeToko) {

            $lastNota = PenjualanNonFisik::where('toko_id', $tokoId)
                ->whereDate('created_at', $date->toDateString())
                ->lockForUpdate()
                ->latest('id')
                ->value('nota');

            $number = $lastNota
                ? ((int) substr($lastNota, -4)) + 1
                : 1;

            return sprintf(
                'INVNF-%s-%s-%06d',
                $kodeToko,
                $dateKey,
                $number
            );
        });
    }
}
