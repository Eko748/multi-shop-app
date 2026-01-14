<?php
namespace App\Repositories;

use App\Models\Piutang;
use Carbon\Carbon;

class PiutangRepository
{
    public function getActivePiutang($month, $year, $tokoId)
    {
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $data = Piutang::where('status', '0')
            ->whereDate('tanggal', '<=', $endDate)
            ->where('toko_id', $tokoId)
            ->withSum('piutangDetail', 'nominal')
            ->get();

        $grouped = [
            'pendek' => [
                'nama'  => 'Piutang Jangka Pendek',
                'nilai' => 0,
            ],
            'panjang' => [
                'nama'  => 'Piutang Jangka Panjang',
                'nilai' => 0,
            ],
        ];

        foreach ($data as $item) {

            $sisaNilai = (float) $item->nominal
                - (float) ($item->piutang_detail_sum_nominal ?? 0);

            if ($sisaNilai <= 0) {
                continue;
            }

            $jangka = $item->jangka === 'pendek' ? 'pendek' : 'panjang';

            $grouped[$jangka]['nilai'] += $sisaNilai;
        }

        return [
            [
                'kode'   => 'III.1',
                'nama'   => $grouped['pendek']['nama'],
                'nilai'  => $grouped['pendek']['nilai'],
                'format' => 'Rp ' . number_format($grouped['pendek']['nilai'], 0, ',', '.'),
            ],
            [
                'kode'   => 'III.2',
                'nama'   => $grouped['panjang']['nama'],
                'nilai'  => $grouped['panjang']['nilai'],
                'format' => 'Rp ' . number_format($grouped['panjang']['nilai'], 0, ',', '.'),
            ],
        ];
    }
}
