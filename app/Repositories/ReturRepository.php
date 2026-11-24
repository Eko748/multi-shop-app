<?php

namespace App\Repositories;

use App\Models\ReturMemberDetail;
use App\Models\ReturSupplier;

class ReturRepository
{
    /**
     * Mengambil data retur member dan supplier
     *
     * @param  mixed  $tokoId  ID Toko (jika null/kosong, data dari semua toko akan ditarik)
     */
    public function getReturData(int $month, int $year, $tokoId = null): array
    {
        $isFilteredByToko = !empty($tokoId) && strtolower((string) $tokoId) !== 'all';

        // 1. Stok Retur Member (Belum dikirim ke supplier)
        $returMemberQuery = ReturMemberDetail::whereYear('created_at', $year)
            ->whereMonth('created_at', '<=', $month)
            ->when($isFilteredByToko, function ($query) use ($tokoId) {
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

        // 2. Stok Proses Retur Supplier (Hanya yang status 'proses' dan belum diverifikasi / verify_date NULL)
        $returSupplierQuery = ReturSupplier::query()
            ->where('status', 'proses')
            ->whereNull('verify_date')
            ->whereYear('tanggal', $year)
            ->whereMonth('tanggal', '<=', $month)
            ->when($isFilteredByToko, function ($query) use ($tokoId) {
                $query->where('toko_id', $tokoId);
            });

        $returSupplier = $returSupplierQuery
            ->selectRaw('
                SUM(total_hpp) as total_hpp,
                SUM(qty) as total_qty
            ')
            ->first();

        $totalReturMember = (int) ($returMember->total_hpp ?? 0);
        $totalReturSupplier = (int) ($returSupplier->total_hpp ?? 0);
        $stockReturMember = (int) ($returMember->total_qty ?? 0);
        $stockReturSupplier = (int) ($returSupplier->total_qty ?? 0);

        return [
            'total_retur' => $totalReturMember + $totalReturSupplier,
            'total_stock' => $stockReturMember + $stockReturSupplier,
            'stock_retur_member' => $stockReturMember,
            'stock_retur_suplier' => $stockReturSupplier,
            'retur_member' => $totalReturMember,
            'retur_suplier' => $totalReturSupplier,
        ];
    }
}
