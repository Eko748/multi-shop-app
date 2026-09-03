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
        $returMemberQuery = ReturMemberDetail::whereYear('created_at', $year)
            ->whereMonth('created_at', '<=', $month)
            // Filter retur member berdasarkan toko HANYA JIKA $tokoId tidak kosong
            ->when(! empty($tokoId), function ($query) use ($tokoId) {
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

        $returSupplierQuery = ReturSupplier::query()
            ->join('retur_supplier_detail', 'retur_supplier.id', '=', 'retur_supplier_detail.retur_supplier_id')
            ->join('pembelian_barang_detail', 'retur_supplier_detail.pembelian_barang_detail_id', '=', 'pembelian_barang_detail.id')
            ->where('retur_supplier.status', 'proses')
            ->whereYear('retur_supplier.tanggal', $year)
            ->whereMonth('retur_supplier.tanggal', '<=', $month)
            // Filter retur supplier berdasarkan toko HANYA JIKA $tokoId tidak kosong
            ->when(! empty($tokoId), function ($query) use ($tokoId) {
                $query->where('retur_supplier.toko_id', $tokoId);
            });

        $returSupplier = $returSupplierQuery
            ->selectRaw('
                SUM(retur_supplier_detail.qty * pembelian_barang_detail.harga_beli) as total_hpp,
                SUM(retur_supplier_detail.qty) as total_qty
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
