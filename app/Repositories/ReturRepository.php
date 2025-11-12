<?php

namespace App\Repositories;

use App\Models\ReturMemberDetail;
use App\Models\ReturSupplier;

class ReturRepository
{
    public function getReturData(int $month, int $year): array
    {
        $returMemberQuery = ReturMemberDetail::whereYear('created_at', $year)
            ->whereMonth('created_at', '<=', $month);

        $returMember = $returMemberQuery
            ->selectRaw('
                SUM((qty_request - IFNULL(qty_ke_supplier, 0)) * hpp) as total_hpp,
                SUM(qty_request - IFNULL(qty_ke_supplier, 0)) as total_qty
            ')
            ->first();

        $returSupplierQuery = ReturSupplier::where('status', 'proses')
            ->where('tipe_retur', 'pembelian')
            ->whereYear('tanggal', $year)
            ->whereMonth('tanggal', '<=', $month);

        $returSupplier = $returSupplierQuery
            ->selectRaw('
                SUM(total_hpp) as total_hpp,
                SUM(qty) as total_qty
            ')
            ->first();

        $totalReturMember   = (float) ($returMember->total_hpp ?? 0);
        $totalReturSupplier = (float) ($returSupplier->total_hpp ?? 0);
        $stockReturMember   = (float) ($returMember->total_qty ?? 0);
        $stockReturSupplier = (float) ($returSupplier->total_qty ?? 0);

        $penjualanRetur = $totalReturMember + $totalReturSupplier;
        $stockRetur     = $stockReturMember + $stockReturSupplier;

        return [
            'total_retur'    => $penjualanRetur,
            'stock_retur'    => $stockRetur,
            'retur_member'   => $totalReturMember,
            'retur_supplier' => $totalReturSupplier,
        ];
    }
}
