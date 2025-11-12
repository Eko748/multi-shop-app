<?php

namespace App\Repositories;

use App\Models\Hutang;

class HutangRepository
{
    public function getActiveHutang()
    {
        return Hutang::where('status', '1')
            ->withSum('detailhutang', 'nilai')
            ->get()
            ->map(function ($item, $index) {
                $sisaNilai = (float) $item->nilai - (float) $item->detailhutang_sum_nilai;
                if ($sisaNilai <= 0) return null;

                $jenis = ((int) $item->jangka === 1)
                    ? 'Hutang Jangka Pendek'
                    : 'Hutang Jangka Panjang';

                return [
                    "kode"   => "III." . ($index + 1),
                    "nama"   => "$jenis - {$item->keterangan}",
                    "nilai"  => $sisaNilai,
                    "format" => number_format($sisaNilai, 0, ',', '.'),
                ];
            })->filter()->values()->toArray();
    }
}
