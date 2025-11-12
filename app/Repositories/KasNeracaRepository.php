<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\ReturMemberDetail;
use App\Models\ReturSupplier;
use App\Models\ReturSupplierDetail;
use App\Models\Toko;

class KasNeracaRepository
{
    private function applyTokoOrLabel($query, $tokoId, $labelValue = null)
    {
        if (!is_null($labelValue)) {
            $query->where('label', $labelValue);
        } elseif (!is_null($tokoId)) {
            $query->where('id_toko', trim($tokoId));
        }
    }
    // ==============================================================
    // =============== PEMASUKAN & PENGELUARAN =====================
    // ==============================================================

    public function getPendapatanNonFisik(?int $month = null, ?int $year = null)
    {
        $query = DB::table('td_penjualan_nonfisik_detail')
            ->selectRaw('SUM(harga_jual * qty) as total');

        if ($month && $year) {
            $query->whereYear('created_at', $year)->whereMonth('created_at', '<=', $month);
        }

        return (float) $query->value('total');
    }

    public function getTotalPemasukan($tokoId = null, $useLabel = false, $jenis = null, ?int $month = null, ?int $year = null)
    {
        $query = DB::table('pemasukan')->whereNull('deleted_at');
        $this->applyTokoOrLabel($query, $tokoId, $useLabel);

        if ($jenis !== null && $jenis !== '') {
            $query->where('kas_jenis_barang', $jenis);
        }

        if ($month && $year) {
            $query->whereYear('tanggal', $year)->whereMonth('tanggal', '<=', $month);
        }

        return (float) $query->sum('nilai');
    }

    public function getTotalPengeluaran($tokoId = null, $useLabel = false, $jenis = null, ?int $month = null, ?int $year = null)
    {
        $query = DB::table('pengeluaran')->whereNull('deleted_at');
        $this->applyTokoOrLabel($query, $tokoId, $useLabel);

        if ($jenis !== null && $jenis !== '') {
            $query->where('kas_jenis_barang', $jenis);
        }

        if ($month && $year) {
            $query->whereYear('tanggal', $year)->whereMonth('tanggal', '<=', $month);
        }

        return (float) $query->sum('nilai');
    }

    // ==============================================================
    // =================== MUTASI BARANG / SALDO ====================
    // ==============================================================

    public function getMutasiOut($kas = null, $jenis = null, ?int $month = null, ?int $year = null)
    {
        $query = DB::table('mutasi')->whereNull('deleted_at')
            ->when($kas !== null, function ($q) use ($kas) {
                $q->where('id_toko_pengirim', $kas);
            })
            ->when($jenis !== null, function ($q) use ($jenis) {
                if ($jenis == 0) {
                    // Dompet digital
                    $q->where(function ($sub) {
                        $sub->whereNull('kas_jenis_barang_pengirim')
                            ->orWhere('kas_jenis_barang_pengirim', 0);
                    });
                } else {
                    // Jenis barang fisik
                    $q->where('kas_jenis_barang_pengirim', $jenis);
                }
            });

        if ($month && $year) {
            $query->whereYear('created_at', $year)->whereMonth('created_at', '<=', $month);
        }

        return (float) $query->sum('nilai');
    }

    public function getMutasiIn($kas = null, $jenis = null, ?int $month = null, ?int $year = null)
    {
        $query = DB::table('mutasi')->whereNull('deleted_at')
            ->when($kas !== null, function ($q) use ($kas) {
                $q->where('id_toko_penerima', $kas);
            })
            ->when($jenis !== null, function ($q) use ($jenis) {
                if ($jenis == 0) {
                    $q->where(function ($sub) {
                        $sub->whereNull('kas_jenis_barang_penerima')
                            ->orWhere('kas_jenis_barang_penerima', 0);
                    });
                } else {
                    $q->where('kas_jenis_barang_penerima', $jenis);
                }
            });

        if ($month && $year) {
            $query->whereYear('created_at', $year)->whereMonth('created_at', '<=', $month);
        }

        return (float) $query->sum('nilai');
    }

    // ==============================================================
    // ========================= HUTANG =============================
    // ==============================================================

    public function getTotalHutang($tokoId = null, $useLabel = false, $jenis = null, ?int $month = null, ?int $year = null)
    {
        $query = DB::table('hutang')->whereNull('deleted_at')->where('id_jenis', '!=', 1);
        $this->applyTokoOrLabel($query, $tokoId, $useLabel);

        if ($jenis !== null && $jenis !== '') {
            $query->where('kas_jenis_barang', $jenis);
        }

        if ($month && $year) {
            $query->whereYear('tanggal', $year)->whereMonth('tanggal', '<=', $month);
        }

        return (float) $query->sum('nilai');
    }

    public function getTotalPelunasanHutang($tokoId = null, $useLabel = false, $jenis = null, ?int $month = null, ?int $year = null)
    {
        $hutangQuery = DB::table('hutang')->whereNull('deleted_at');
        $this->applyTokoOrLabel($hutangQuery, $tokoId, $useLabel);

        if ($jenis !== null && $jenis !== '') {
            $hutangQuery->where('kas_jenis_barang', $jenis);
        }

        $hutangIds = $hutangQuery->pluck('id');
        if ($hutangIds->isEmpty()) return 0;

        if ($month && $year) {
            $hutangQuery->whereYear('tanggal', $year)->whereMonth('tanggal', '<=', $month);
        }

        return (float) DB::table('detail_hutang')
            ->whereIn('id_hutang', $hutangIds)
            ->sum('nilai');
    }

    // ==============================================================
    // ========================= PIUTANG ============================
    // ==============================================================

    public function getTotalPiutang($tokoId = null, $useLabel = false, $jenis = null, ?int $month = null, ?int $year = null)
    {
        $query = DB::table('piutang')->whereNull('deleted_at');
        $this->applyTokoOrLabel($query, $tokoId, $useLabel);

        if ($jenis !== null && $jenis !== '') {
            $query->where('kas_jenis_barang', $jenis);
        }

        if ($month && $year) {
            $query->whereYear('tanggal', $year)->whereMonth('tanggal', '<=', $month);
        }

        return (float) $query->sum('nilai');
    }

    public function getTotalPelunasanPiutang($tokoId = null, $useLabel = false, $jenis = null, ?int $month = null, ?int $year = null)
    {
        $piutangQuery = DB::table('piutang')->whereNull('deleted_at');
        $this->applyTokoOrLabel($piutangQuery, $tokoId, $useLabel);

        $piutangIds = $piutangQuery->pluck('id');
        if ($piutangIds->isEmpty()) return 0;

        if ($month && $year) {
            $piutangQuery->whereYear('tanggal', $year)->whereMonth('tanggal', '<=', $month);
        }

        return (float) DB::table('detail_piutang')
            ->whereIn('id_piutang', $piutangIds)
            ->sum('nilai');
    }

    // ==============================================================
    // ======================= PEMBELIAN ============================
    // ==============================================================

    public function getTotalPembelianBarang($useLabel = false, $jenis = null, ?int $month = null, ?int $year = null)
    {
        $query = DB::table('detail_pembelian_barang as dpb')
            ->join('pembelian_barang as pb', 'pb.id', '=', 'dpb.id_pembelian_barang')
            ->join('barang as b', 'b.id', '=', 'dpb.id_barang')
            ->join('jenis_barang as jb', 'jb.id', '=', 'b.id_jenis_barang')
            ->where('pb.status', 'success')
            ->whereNull('pb.deleted_at')   // pembelian_barang
            ->whereNull('dpb.deleted_at')  // detail_pembelian_barang
            ->whereNull('b.deleted_at')    // barang
            ->whereNull('jb.deleted_at');  // jenis_barang

        if (!is_null($useLabel)) {
            $query->where('pb.label', $useLabel);
        }

        if (!is_null($jenis)) {
            $query->where('b.id_jenis_barang', $jenis);
        }

        if ($month && $year) {
            $query->whereYear('dpb.created_at', $year)->whereMonth('dpb.created_at', '<=', $month);
        }

        return (float) $query->sum(DB::raw('dpb.harga_barang * dpb.qty'));
    }

    public function getTotalPembelianSaldoDigital($useLabel = false, $jenis = null, ?int $month = null, ?int $year = null)
    {
        $query = DB::table('td_dompet_saldo')->whereNull('deleted_at');

        if ($useLabel && $jenis !== null) {
            $query->where('kas_jenis_barang', $jenis);
        }

        if ($month && $year) {
            $query->whereYear('created_at', $year)->whereMonth('created_at', '<=', $month);
        }

        return (float) $query->sum('harga_beli');
    }

    // ==============================================================
    // ========================== RETUR =============================
    // ==============================================================

    public function getTotalBiayaRetur($idTokoLogin = null, $idJenisBarang = null, ?int $month = null, ?int $year = null)
    {
        $query = ReturMemberDetail::where('qty_refund', '>', 0)
            ->whereHas('barang', function ($q) use ($idJenisBarang) {
                if ($idJenisBarang) {
                    $q->where('id_jenis_barang', $idJenisBarang);
                }
            });

        if ($idTokoLogin) {
            $query->whereHas('retur', function ($r) use ($idTokoLogin) {
                $r->where('toko_id', $idTokoLogin);
            });
        }

        if ($month && $year) {
            $query->whereYear('created_at', $year)->whereMonth('created_at', '<=', $month);
        }

        return (float) $query->sum('total_refund');
    }

    public function getTotalRefundSuplier($idTokoLogin = null, $idJenisBarang = null, ?int $month = null, ?int $year = null)
    {
        $query = ReturSupplierDetail::where('qty_refund', '>', 0)
            ->whereHas('barang', function ($q) use ($idJenisBarang) {
                if ($idJenisBarang) {
                    $q->where('id_jenis_barang', $idJenisBarang);
                }
            });

        if ($idTokoLogin) {
            $query->whereHas('returSupplier', function ($r) use ($idTokoLogin) {
                $r->where('toko_id', $idTokoLogin);
            });
        }

        if ($month && $year) {
            $query->whereYear('created_at', $year)->whereMonth('created_at', '<=', $month);
        }

        return $query->sum('total_refund_real');
    }

    // ==============================================================
    // ==================== OMSET & KASBON ==========================
    // ==============================================================

    public function getTotalOmset($tokoId = null, $idJenisBarang = null, ?int $month = null, ?int $year = null)
    {
        $query = Toko::whereNull('toko.deleted_at')
            ->leftJoin('kasir', function ($join) {
                $join->on('toko.id', '=', 'kasir.id_toko')
                    ->where('kasir.total_item', '>', 0)
                    ->whereNull('kasir.deleted_at');
            })
            ->leftJoin('detail_kasir as dk', 'kasir.id', '=', 'dk.id_kasir')
            ->leftJoin('barang as b', 'b.id', '=', 'dk.id_barang')
            ->whereNull('dk.deleted_at')
            ->whereNull('b.deleted_at');

        if ($tokoId) {
            $query->where('toko.id', trim($tokoId));
        }

        if ($idJenisBarang) {
            $query->where('b.id_jenis_barang', $idJenisBarang);
        }

        if ($month && $year) {
            $query->whereYear('dk.created_at', $year)->whereMonth('dk.created_at', '<=', $month);
        }

        return (float) optional(
            $query->selectRaw('SUM(COALESCE(dk.total_harga, 0) - COALESCE(dk.diskon, 0)) as total_nilai')->first()
        )->total_nilai;
    }

    public function getTotalKasbon($tokoId = null, ?int $month = null, ?int $year = null)
    {
        $query = DB::table('kasbon')->whereNull('kasbon.deleted_at')
            ->join('kasir', 'kasbon.id_kasir', '=', 'kasir.id')
            ->where('kasbon.utang_sisa', '>', 0)
            ->when($tokoId, fn($q) => $q->where('kasir.id_toko', trim($tokoId)));

        if ($month && $year) {
            $query->whereYear('kasbon.created_at', $year)->whereMonth('kasbon.created_at', '<=', $month);
        }

        return (float) $query->sum('kasbon.utang_sisa');
    }
}
