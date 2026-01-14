<?php

namespace App\Repositories\Distribusi;

use App\Models\PengirimanBarangDetail;

class PengirimanBarangRepo
{
    public function getStokPengirimanBarang(int $month, int $year, int $idToko): array
    {
        $details = PengirimanBarangDetail::where('qty_send', '>', 0)
            ->whereHas('pengirimanBarang', function ($q) use ($idToko, $month, $year) {
                $q->where('status', 'progress')
                    ->where('toko_asal_id', $idToko)
                    ->whereMonth('send_at', $month)
                    ->whereYear('send_at', $year);
            })
            ->with('batch:id,harga_beli')
            ->get();

        $totalQty = $details->sum('qty_send');

        $totalHarga = $details->sum(function ($item) {
            return ($item->batch->harga_beli ?? 0) * $item->qty_send;
        });

        return [
            'total_qty'   => $totalQty,
            'total_harga' => $totalHarga,
        ];
    }
}
