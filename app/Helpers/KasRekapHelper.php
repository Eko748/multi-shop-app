<?php

namespace App\Helpers;

use App\Models\Kas;
use App\Models\Toko;
use App\Models\JenisBarang;
use App\Models\KasSaldoHistory;
use App\Models\KasTransaksi;
use App\Models\LabaRugi;
use App\Models\LabaRugiTahunan;
use App\Models\TransaksiKasir;
use App\Models\TransaksiKasirHarian;
use App\Services\KasService;
use Carbon\Carbon;

class KasRekapHelper
{
    // ----------------------------
    // Bagian syncKasFromKasir()
    // ----------------------------
    public static function syncKasFromKasir(
        int $toko_id,
        int $jenis_barang_id,
        int $kas_id,
        string $tanggal,
        float $pendapatan,
        float $beban,
        TransaksiKasirHarian $sumber
    ): void {

        $kas = Kas::find($kas_id);
        if (!$kas) throw new \Exception("Kas tidak ditemukan.");

        $today    = Carbon::parse($tanggal);
        $tahunNow = $today->year;
        $bulanNow = $today->month;

        // Nama jenis barang
        $jenisNama = $jenis_barang_id == 0
            ? 'Dompet Digital'
            : (JenisBarang::find($jenis_barang_id)?->nama_jenis_barang ?? '-');

        // Ambil KasTransaksi per hari & per TransaksiKasirHarian
        $transaksi = KasTransaksi::firstOrNew([
            'kas_id'      => $kas->id,
            'sumber_type' => TransaksiKasirHarian::class,
            'sumber_id'   => $sumber->id,
        ]);

        // Hitung selisih lama vs baru
        $oldNominal = $transaksi->exists ? $transaksi->total_nominal : 0;

        // Update total_nominal sesuai sumber
        $transaksi->tipe           = 'in';
        $transaksi->kode_transaksi = $transaksi->kode_transaksi ?? 'KS-' . time() . rand(100, 999);
        $transaksi->total_nominal  = $sumber->total_nominal; // <-- pakai total_nominal dari TransaksiKasirHarian
        $transaksi->kategori       = 'Pendapatan Umum';
        $transaksi->keterangan     = "Kas {$jenisNama}";
        $transaksi->item           = 'kecil';
        $transaksi->tanggal        = $tanggal;
        $transaksi->save();

        // Update saldo kas pakai selisih
        $selisih = $sumber->total_nominal - $oldNominal;
        $kas->saldo += $selisih;
        $kas->save();


        // History Bulanan
        $historyNow = KasSaldoHistory::firstOrCreate([
            'kas_id' => $kas->id,
            'tahun'  => $tahunNow,
            'bulan'  => $bulanNow,
        ], [
            'saldo_awal'  => $kas->saldo,
            'saldo_akhir' => $kas->saldo,
        ]);
        $historyNow->saldo_akhir = $kas->saldo;
        $historyNow->save();

        // Laba Rugi tetap aman karena pendapatan & beban dari TransaksiKasirHarian
        $labaRugi = LabaRugi::firstOrCreate([
            'toko_id' => $toko_id,
            'tahun'   => $tahunNow,
            'bulan'   => $bulanNow,
        ]);
        $labaRugi->increment('pendapatan', $selisih);
        $labaRugi->increment('beban', $beban);
        $labaRugi->update(['laba_bersih' => $labaRugi->pendapatan - $labaRugi->beban]);

        $labaRugiTahunan = LabaRugiTahunan::firstOrCreate([
            'toko_id' => $toko_id,
            'tahun'   => $tahunNow,
        ]);
        $labaRugiTahunan->increment('pendapatan', $selisih);
        $labaRugiTahunan->increment('beban', $beban);
        $labaRugiTahunan->update(['laba_bersih' => $labaRugiTahunan->pendapatan - $labaRugiTahunan->beban]);
    }

    public static function syncKasForRekap(
        TransaksiKasirHarian $rekap,
        ?string $tanggal = null
    ): void {
        $toko = Toko::find($rekap->toko_id);

        if (!$toko) {
            throw new \Exception("Toko tidak ditemukan.");
        }

        $kas = Kas::where('toko_id', $toko->id)
            ->where('tipe_kas', 'kecil')
            ->where('jenis_barang_id', $rekap->jenis_barang_id)
            ->first();


        if (!$kas) {
            throw new \Exception(
                "Kas kecil untuk jenis_barang_id {$rekap->jenis_barang_id} belum dibuat."
            );
        }

        // Untuk kasus jenis_barang_id = 0 (Dompet Digital)
        $jenisNama = $rekap->jenis_barang_id == 0
            ? 'Dompet Digital'
            : (JenisBarang::find($rekap->jenis_barang_id)?->nama_jenis_barang ?? '-');

        KasService::kasir(
            toko_id: $toko->id,
            jenis_barang_id: $rekap->jenis_barang_id,
            tipe_kas: $kas->tipe_kas,
            total_nominal: $rekap->total_bayar,
            item: 'kecil',
            kategori: 'Pendapatan Umum',
            keterangan: "Kas {$jenisNama}",
            sumber: $rekap,
            laba: true,
            beban: $rekap->total_hpp // ⬅️ FIX INTI
        );
    }
}
