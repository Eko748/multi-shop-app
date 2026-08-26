<?php

namespace App\Repositories;

use App\Models\ReturMemberDetail;
use App\Models\ReturSupplier;

class ReturRepository
{
    /**
     * Mengambil data retur member dan supplier
     *
     * @param int $month
     * @param int $year
     * @param mixed $tokoId ID Toko (jika null/kosong, data dari semua toko akan ditarik)
     * @return array
     */
    public function getReturData(int $month, int $year, $tokoId = null): array
    {
        $returMemberQuery = ReturMemberDetail::whereYear('created_at', $year)
            ->whereMonth('created_at', '<=', $month)
            // Filter retur member berdasarkan toko HANYA JIKA $tokoId tidak kosong
            ->when(!empty($tokoId), function ($query) use ($tokoId) {
                $query->whereHas('retur', function ($q) use ($tokoId) {
                    $q->where('toko_id', $tokoId);
                });
            });

        $returMember = $returMemberQuery
            ->selectRaw('
                SUM((qty_request - IFNULL(qty_ke_supplier, 0)) * hpp) as total_hpp,
                SUM(qty_request - IFNULL(qty_ke_supplier, 0)) as total_qty
            ')
            ->first();

        $returSupplierQuery = ReturSupplier::where('status', 'proses')
            ->whereYear('tanggal', $year)
            ->whereMonth('tanggal', '<=', $month)
            // Filter retur supplier berdasarkan toko HANYA JIKA $tokoId tidak kosong
            ->when(!empty($tokoId), function ($query) use ($tokoId) {
                $query->where('toko_id', $tokoId);
            });

        $returSupplier = $returSupplierQuery
            ->selectRaw('
                SUM(total_hpp) as total_hpp,
                SUM(qty) as total_qty
            ')
            ->first();

        $totalReturMember = (float) ($returMember->total_hpp ?? 0);
        $totalReturSupplier = (float) ($returSupplier->total_hpp ?? 0);
        $stockReturMember = (float) ($returMember->total_qty ?? 0);
        $stockReturSupplier = (float) ($returSupplier->total_qty ?? 0);

        $penjualanRetur = $totalReturMember + $totalReturSupplier;
        $stockRetur = $stockReturMember + $stockReturSupplier;

        return [
            'total_retur' => $penjualanRetur,
            'stock_retur_member' => $stockReturMember,
            'stock_retur_suplier' => $stockReturSupplier,
            'retur_member' => $totalReturMember,
            'retur_suplier' => $totalReturSupplier,
        ];
    }
}
