<?php

namespace App\Repositories;

use App\Models\Hutang;
use Carbon\Carbon;

class HutangRepository
{
    public function getActiveHutang($month, $year, $tokoId)
    {
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        return Hutang::where('status', 0)
            ->whereDate('tanggal', '<=', $endDate)
            ->where('toko_id', $tokoId)
            ->withSum('hutangDetail', 'nominal')
            ->get()
            ->map(function ($item, $index) {

                $sisaNilai = (float) $item->sisa;

                $jenis = $item->jangka === 'pendek'
                    ? 'Hutang Jangka Pendek'
                    : 'Hutang Jangka Panjang';

                return [
                    "kode"   => "III." . ($index + 1),
                    "nama"   => "$jenis - {$item->keterangan}",
                    "nilai"  => $sisaNilai,
                    "format" => 'Rp ' . number_format($sisaNilai, 0, ',', '.'),
                ];
            })
            ->values()
            ->toArray();
    }

    public function getActiveHutangPengiriman($month, $year, $tokoId)
    {
        $data = Hutang::query()
            ->where('status', '0')
            ->whereYear('tanggal', $year)
            ->whereMonth('tanggal', '<=', $month)

            ->whereHasMorph(
                'sumber',
                [\App\Models\PengirimanBarang::class],
                function ($q) use ($tokoId) {
                    $q->where('toko_asal_id', $tokoId);
                }
            )

            ->with([
                'sumber',
                'jenisBarang:id,nama_jenis_barang'
            ])

            ->withSum('hutangDetail', 'nominal')
            ->get();

        $grouped = [];

        foreach ($data as $item) {
            $sisa = (float) $item->nominal - (float) $item->hutang_detail_sum_nominal;
            if ($sisa <= 0) continue;

            $jenisId   = $item->jenis_barang_id;
            $jenisNama = $item->jenisBarang->nama_jenis_barang ?? 'Tidak diketahui';

            if (!isset($grouped[$jenisId])) {
                $grouped[$jenisId] = [
                    'nama'  => "Pengiriman Barang - {$jenisNama}",
                    'nilai' => 0
                ];
            }

            $grouped[$jenisId]['nilai'] += $sisa;
        }

        $result = [];
        $i = 1;

        foreach ($grouped as $jenisId => $row) {
            $result[] = [
                "kode"   => "I.3." . $i++,
                "nama"   => $row['nama'],
                "nilai"  => $row['nilai'],
                "format" => "Rp " . number_format($row['nilai'], 0, ',', '.'),
            ];
        }

        return $result;
    }
}
