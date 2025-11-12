<?php

namespace App\Repositories;

use App\Models\ReturMemberDetail;
use App\Models\ReturSupplierDetail;
use Illuminate\Support\Facades\DB;
use App\Models\Toko;

class KasRepository
{
    public function getTotalPemasukanGrouped($isSingle, $kas, $jenisList)
    {
        return DB::table('pemasukan')
            ->select('kas_jenis_barang', DB::raw('SUM(nilai) as total'))
            ->whereNull('deleted_at')
            ->whereIn('kas_jenis_barang', $jenisList)
            ->groupBy('kas_jenis_barang')
            ->pluck('total', 'kas_jenis_barang')
            ->toArray();
    }

    public function getTotalPengeluaranGrouped($isSingle, $kas, $jenisList)
    {
        return DB::table('pengeluaran')
            ->select('kas_jenis_barang', DB::raw('SUM(nilai) as total'))
            ->whereNull('deleted_at')
            ->whereIn('kas_jenis_barang', $jenisList)
            ->groupBy('kas_jenis_barang')
            ->pluck('total', 'kas_jenis_barang')
            ->toArray();
    }

    public function getTotalHutangGrouped($isSingle, $kas, $jenisList)
    {
        return DB::table('hutang')
            ->select('kas_jenis_barang', DB::raw('SUM(nilai) as total'))
            ->whereNull('deleted_at')
            ->whereIn('kas_jenis_barang', $jenisList)
            ->groupBy('kas_jenis_barang')
            ->pluck('total', 'kas_jenis_barang')
            ->toArray();
    }

    public function getTotalPelunasanHutangGrouped($isSingle, $kas, $jenisList)
    {
        return DB::table('detail_hutang as dh')
            ->join('hutang as h', 'h.id', '=', 'dh.id_hutang')
            ->select('h.kas_jenis_barang', DB::raw('SUM(dh.nilai) as total'))
            ->whereNull('dh.deleted_at')
            ->whereNull('h.deleted_at')
            ->whereIn('h.kas_jenis_barang', $jenisList)
            ->groupBy('h.kas_jenis_barang')
            ->pluck('total', 'h.kas_jenis_barang')
            ->toArray();
    }

    public function getTotalPiutangGrouped($isSingle, $kas, $jenisList)
    {
        return DB::table('piutang')
            ->select('kas_jenis_barang', DB::raw('SUM(nilai) as total'))
            ->whereNull('deleted_at')
            ->whereIn('kas_jenis_barang', $jenisList)
            ->groupBy('kas_jenis_barang')
            ->pluck('total', 'kas_jenis_barang')
            ->toArray();
    }

    public function getTotalPelunasanPiutangGrouped($isSingle, $kas, $jenisList)
    {
        return DB::table('detail_piutang as dp')
            ->join('piutang as p', 'p.id', '=', 'dp.id_piutang')
            ->select('p.kas_jenis_barang', DB::raw('SUM(dp.nilai) as total'))
            ->whereNull('dp.deleted_at')
            ->whereNull('p.deleted_at')
            ->whereIn('p.kas_jenis_barang', $jenisList)
            ->groupBy('p.kas_jenis_barang')
            ->pluck('total', 'p.kas_jenis_barang')
            ->toArray();
    }

    public function getMutasiOutGrouped($kas, $jenisList)
    {
        return DB::table('mutasi')
            ->select('kas_jenis_barang_pengirim as jenis', DB::raw('SUM(nilai) as total'))
            ->whereNull('deleted_at')
            ->where('id_toko_pengirim', $kas)
            ->whereIn('kas_jenis_barang_pengirim', $jenisList)
            ->groupBy('kas_jenis_barang_pengirim')
            ->pluck('total', 'jenis')
            ->toArray();
    }

    public function getMutasiInGrouped($kas, $jenisList)
    {
        return DB::table('mutasi')
            ->select('kas_jenis_barang_penerima as jenis', DB::raw('SUM(nilai) as total'))
            ->whereNull('deleted_at')
            ->where('id_toko_penerima', $kas)
            ->whereIn('kas_jenis_barang_penerima', $jenisList)
            ->groupBy('kas_jenis_barang_penerima')
            ->pluck('total', 'jenis')
            ->toArray();
    }

    public function getTotalPembelianBarangGrouped($kas, $jenisList)
    {
        return DB::table('detail_pembelian_barang as dpb')
            ->join('pembelian_barang as pb', 'pb.id', '=', 'dpb.id_pembelian_barang')
            ->join('barang as b', 'b.id', '=', 'dpb.id_barang')
            ->select('b.id_jenis_barang', DB::raw('SUM(dpb.harga_barang * dpb.qty) as total'))
            ->where('pb.status', 'success')
            ->whereNull('pb.deleted_at')
            ->whereNull('dpb.deleted_at')
            ->whereNull('b.deleted_at')
            ->whereIn('b.id_jenis_barang', $jenisList)
            ->groupBy('b.id_jenis_barang')
            ->pluck('total', 'b.id_jenis_barang')
            ->toArray();
    }

    public function getTotalPembelianSaldoDigitalGrouped($kas, $jenisList)
    {
        return DB::table('td_dompet_saldo')
            ->select('kas_jenis_barang', DB::raw('SUM(harga_beli) as total'))
            ->whereNull('deleted_at')
            ->whereIn('kas_jenis_barang', $jenisList)
            ->groupBy('kas_jenis_barang')
            ->pluck('total', 'kas_jenis_barang')
            ->toArray();
    }

    public function getTotalPembelianBarang($labelValue = null, $jenis = null)
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

        if (!is_null($labelValue)) {
            $query->where('pb.label', $labelValue);
        }

        if (!is_null($jenis)) {
            $query->where('b.id_jenis_barang', $jenis);
        }

        return (float) $query->sum(DB::raw('dpb.harga_barang * dpb.qty'));
    }

    public function getTotalPembelianSaldoDigital($labelValue = null, $jenis = null)
    {
        $query = DB::table('td_dompet_saldo')
            ->whereNull('deleted_at');

        if (!is_null($labelValue)) {
            $query->where('kas', $labelValue);
        }
        if (!is_null($jenis)) {
            $query->where('kas_jenis_barang', $jenis);
        }

        return (float) $query->sum('harga_beli');
    }

    public function getPendapatanNonFisik()
    {
        return (float) DB::table('td_penjualan_nonfisik_detail')
            ->selectRaw('SUM(harga_jual * qty) as total')
            ->value('total');
    }

    public function getTotalKasJenisBarang($jenis = null)
    {
        $query = DB::table('detail_kasir as dk')
            ->join('barang as b', 'b.id', '=', 'dk.id_barang')
            ->join('jenis_barang as jb', 'jb.id', '=', 'b.id_jenis_barang')
            ->whereNull('dk.deleted_at')
            ->whereNull('b.deleted_at')
            ->whereNull('jb.deleted_at');

        if (!is_null($jenis)) {
            $query->where('b.id_jenis_barang', $jenis);
        }

        return (float) $query->selectRaw('SUM(COALESCE(dk.total_harga, 0) - COALESCE(dk.diskon, 0)) as total_nilai')
            ->value('total_nilai');
    }

    public function getTotalOmset($idTokoLogin = null, $idJenisBarang = null)
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

        if ($idTokoLogin) {
            $query->where('toko.id', trim($idTokoLogin));
        }

        if ($idJenisBarang) {
            $query->where('b.id_jenis_barang', $idJenisBarang);
        }

        return (float) optional(
            $query->selectRaw('SUM(COALESCE(dk.total_harga, 0) - COALESCE(dk.diskon, 0)) as total_nilai')->first()
        )->total_nilai;
    }

    public function getTotalBiayaRetur($idTokoLogin = null, $idJenisBarang = null)
    {
        $query = ReturMemberDetail::where('qty_refund', '>', 0)
            ->whereHas('barang', function ($q) use ($idJenisBarang) {
                if ($idJenisBarang) {
                    $q->where('id_jenis_barang', $idJenisBarang);
                }
            });

        // Jika ingin filter berdasarkan toko login juga
        if ($idTokoLogin) {
            $query->whereHas('retur', function ($r) use ($idTokoLogin) {
                $r->where('toko_id', $idTokoLogin);
            });
        }

        return $query->sum('total_refund');
    }

    public function getTotalRefundSuplier($idTokoLogin = null, $idJenisBarang = null)
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

        return $query->sum('total_refund_real');
    }

    public function getUntungRefundSuplier($idTokoLogin = null, $idJenisBarang = null)
    {
        $query = ReturSupplierDetail::where('qty_refund', '>', 0)->where('keterangan', 'untung')
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

        return $query->sum('selisih');
    }

    public function getRugiRefundSuplier($idTokoLogin = null, $idJenisBarang = null)
    {
        $query = ReturSupplierDetail::where('qty_refund', '>', 0)->where('keterangan', 'rugi')
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

        return $query->sum('selisih');
    }

    public function getTotalKasbon($idTokoLogin = null)
    {
        return (float) DB::table('kasbon')->whereNull('kasbon.deleted_at')
            ->join('kasir', 'kasbon.id_kasir', '=', 'kasir.id')
            ->where('kasbon.utang_sisa', '>', 0)
            ->when($idTokoLogin, fn($q) => $q->where('kasir.id_toko', trim($idTokoLogin)))
            ->sum('kasbon.utang_sisa');
    }

    private function applyTokoOrLabel($query, $tokoId, $labelValue = null)
    {
        if (!is_null($labelValue)) {
            $query->where('label', $labelValue);
        } elseif (!is_null($tokoId)) {
            $query->where('id_toko', trim($tokoId));
        }
    }

    // public function getTotalPemasukan($tokoId = null, $useLabel = false, $jenis = null)
    // {
    //     $query = DB::table('pemasukan')->whereNull('deleted_at');
    //     $this->applyTokoOrLabel($query, $tokoId, $useLabel);
    //     if (!is_null($jenis)) {
    //         $query->where('kas_jenis_barang', $jenis);
    //     }
    //     return (float) $query->sum('nilai');
    // }

    // public function getTotalPengeluaran($tokoId = null, $useLabel = false, $jenis = null)
    // {
    //     $query = DB::table('pengeluaran')->whereNull('deleted_at');
    //     $this->applyTokoOrLabel($query, $tokoId, $useLabel);
    //     if (!is_null($jenis)) {
    //         $query->where('kas_jenis_barang', $jenis);
    //     }
    //     return (float) $query->sum('nilai');
    // }

    public function getFirstTwoToko()
    {
        return Toko::limit(2)->get();
    }

    public function getTotalPemasukan($tokoId = null, $useLabel = false, $jenis = null)
    {
        $query = DB::table('pemasukan')->whereNull('deleted_at');
        $this->applyTokoOrLabel($query, $tokoId, $useLabel);

        if ($jenis !== null && $jenis !== '') {
            $query->where('kas_jenis_barang', $jenis);
        }

        return (float) $query->sum('nilai');
    }

    public function getTotalPengeluaran($tokoId = null, $useLabel = false, $jenis = null)
    {
        $query = DB::table('pengeluaran')->whereNull('deleted_at');
        $this->applyTokoOrLabel($query, $tokoId, $useLabel);

        if ($jenis !== null && $jenis !== '') {
            $query->where('kas_jenis_barang', $jenis);
        }

        return (float) $query->sum('nilai');
    }

    public function getTotalHutang($tokoId = null, $useLabel = false, $jenis = null)
    {
        $query = DB::table('hutang')->whereNull('deleted_at')->where('id_jenis', '!=', 1);
        $this->applyTokoOrLabel($query, $tokoId, $useLabel);

        if ($jenis !== null && $jenis !== '') {
            $query->where('kas_jenis_barang', $jenis);
        }

        return (float) $query->sum('nilai');
    }

    public function getTotalPelunasanHutang($tokoId = null, $useLabel = false, $jenis = null)
    {
        $hutangQuery = DB::table('hutang')->whereNull('deleted_at');
        $this->applyTokoOrLabel($hutangQuery, $tokoId, $useLabel);

        if ($jenis !== null && $jenis !== '') {
            $hutangQuery->where('kas_jenis_barang', $jenis);
        }

        $hutangIds = $hutangQuery->pluck('id');
        if ($hutangIds->isEmpty()) return 0;

        return (float) DB::table('detail_hutang')
            ->whereIn('id_hutang', $hutangIds)
            ->sum('nilai');
    }

    public function getTotalPiutang($tokoId = null, $useLabel = false, $jenis = null)
    {
        $query = DB::table('piutang')->whereNull('deleted_at');
        $this->applyTokoOrLabel($query, $tokoId, $useLabel);

        if ($jenis !== null && $jenis !== '') {
            $query->where('kas_jenis_barang', $jenis);
        }

        return (float) $query->sum('nilai');
    }

    public function getTotalPelunasanPiutang($tokoId = null, $useLabel = false)
    {
        $piutangQuery = DB::table('piutang')->whereNull('deleted_at');
        $this->applyTokoOrLabel($piutangQuery, $tokoId, $useLabel);

        $piutangIds = $piutangQuery->pluck('id');
        if ($piutangIds->isEmpty()) return 0;

        return (float) DB::table('detail_piutang')
            ->whereIn('id_piutang', $piutangIds)
            ->sum('nilai');
    }

    public function getMutasiOut($kas = null, $jenis = null)
    {
        return (float) DB::table('mutasi')->whereNull('deleted_at')
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
            })
            ->sum('nilai');
    }

    public function getMutasiIn($kas = null, $jenis = null)
    {
        return (float) DB::table('mutasi')->whereNull('deleted_at')
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
            })
            ->sum('nilai');
    }

    public function getMutasiKeluar(bool $isSingleToko)
    {
        $value = $isSingleToko ? '0' : '1';
        return (float) DB::table('mutasi')->whereNull('deleted_at')->where('id_toko_pengirim', $value)->sum('nilai');
    }

    public function getMutasiMasuk(bool $isSingleToko)
    {
        $value = $isSingleToko ? '0' : '1';
        return (float) DB::table('mutasi')->whereNull('deleted_at')->where('id_toko_penerima', $value)->sum('nilai');
    }

    public function getMutasiMasukKasKecilSingle()
    {
        return (float) DB::table('mutasi')->whereNull('deleted_at')->where('id_toko_penerima', '1')->sum('nilai');
    }

    public function getMutasiKeluarKasKecilSingle()
    {
        return (float) DB::table('mutasi')->whereNull('deleted_at')->where('id_toko_pengirim', '1')->sum('nilai');
    }

    public function getMutasiMasukKasKecilMulti()
    {
        return (float) DB::table('mutasi')->whereNull('deleted_at')->where('id_toko_penerima', '>=', '2')->sum('nilai');
    }

    public function getMutasiKeluarKasKecilMulti()
    {
        return (float) DB::table('mutasi')->whereNull('deleted_at')->where('id_toko_pengirim', '>=', '2')->sum('nilai');
    }
}
