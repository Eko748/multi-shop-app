<?php

namespace App\Services;

use App\Models\Hutang;
use App\Models\Kas;
use App\Models\KasTransaksi;
use App\Models\KasSaldoHistory;
use App\Models\LabaRugi;
use App\Models\LabaRugiTahunan;
use App\Models\Piutang;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KasService
{
    /*===============================
    * PUBLIC WRAPPER
    ===============================*/

    public static function in($toko_id, $jenis_barang_id, $tipe_kas, $total_nominal, $item, $kategori = null, $keterangan = null, $sumber = null, $tanggal = null, $laba = true)
    {
        return self::handleKas(['in'], $toko_id, $jenis_barang_id, $tipe_kas, $total_nominal, $item, $kategori, $keterangan, $sumber, $tanggal, $laba);
    }

    public static function out($toko_id, $jenis_barang_id, $tipe_kas, $total_nominal, $item, $kategori = null, $keterangan = null, $sumber = null, $tanggal = null, $laba = true)
    {
        return self::handleKas(['out'], $toko_id, $jenis_barang_id, $tipe_kas, $total_nominal, $item, $kategori, $keterangan, $sumber, $tanggal, $laba);
    }

    public static function neutralIN($toko_id, $jenis_barang_id, $tipe_kas, $total_nominal, $item, $kategori = null, $keterangan = null, $sumber = null, $tanggal = null, $laba = true)
    {
        return self::handleKas(['neutral', 'in'], $toko_id, $jenis_barang_id, $tipe_kas, $total_nominal, $item, $kategori, $keterangan, $sumber, $tanggal, $laba);
    }

    public static function neutralOUT($toko_id, $jenis_barang_id, $tipe_kas, $total_nominal, $item, $kategori = null, $keterangan = null, $sumber = null, $tanggal = null, $laba = true)
    {
        return self::handleKas(['neutral', 'out'], $toko_id, $jenis_barang_id, $tipe_kas, $total_nominal, $item, $kategori, $keterangan, $sumber, $tanggal, $laba);
    }

    /*===============================
    * FITUR MUTASI KAS (TRANSFER)
    ===============================*/

    public static function mutasi(
        $fromKas,  // kas asal (object Kas)
        $toKas,    // kas tujuan (object Kas)
        $nominal,
        $sumber = null,
        $tanggal = null
    ) {
        return DB::transaction(function () use ($fromKas, $toKas, $nominal, $sumber, $tanggal) {

            // 1) KAS KELUAR
            self::coreUpdateKas(
                tipe: ['out'],
                kas: $fromKas,
                total_nominal: $nominal,
                item: $fromKas->tipe_kas,
                kategori: 'Mutasi Kas',
                keterangan: 'Mutasi Kas Keluar',
                sumber: $sumber,
                tanggal: $tanggal
            );

            // 2) KAS MASUK
            self::coreUpdateKas(
                tipe: ['in'],
                kas: $toKas,
                total_nominal: $nominal,
                item: $toKas->tipe_kas,
                kategori: 'Mutasi Kas',
                keterangan: 'Mutasi Kas Masuk',
                sumber: $sumber,
                tanggal: $tanggal
            );

            return true;
        });
    }

    /*========================================
    * MAIN CORE — dipakai IN, OUT, MUTASI
    ========================================*/
    private static function handleKas($tipe, $toko_id, $jenis_barang_id, $tipe_kas, $total_nominal, $item, $kategori, $keterangan, $sumber, $tanggal, $laba)
    {
        $kas = Kas::firstOrCreate(
            ['toko_id' => $toko_id, 'jenis_barang_id' => $jenis_barang_id, 'tipe_kas' => $tipe_kas],
            ['saldo_awal' => 0, 'saldo' => 0, 'tanggal' => $tanggal]
        );

        return DB::transaction(function () use ($tipe, $kas, $total_nominal, $item, $kategori, $keterangan, $sumber, $tanggal, $laba) {
            return self::coreUpdateKas($tipe, $kas, $total_nominal, $item, $kategori, $keterangan, $sumber, $tanggal, $laba);
        });
    }

    private static function coreUpdateKas(
        $tipe,
        Kas $kas,
        $total_nominal,
        $item,
        $kategori,
        $keterangan,
        $sumber,
        $tanggal,
        $laba
    ) {
        $fTanggal = Carbon::now();
        $tahunNow = $fTanggal->year;
        $bulanNow = $fTanggal->month;

        /*------------------------------------------
        | 1. CEK BULAN TERAKHIR UNTUK CLOSING
        -------------------------------------------*/
        $lastHistory = KasSaldoHistory::where('kas_id', $kas->id)
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->first();

        if ($lastHistory && ($lastHistory->bulan != $bulanNow || $lastHistory->tahun != $tahunNow)) {

            // Tutup bulan terakhir
            $lastHistory->update(['saldo_akhir' => $kas->saldo]);

            // Set saldo awal bulan ini = saldo akhir sebelumnya
            $kas->saldo_awal = $kas->saldo;
            $kas->save();
        }

        /*------------------------------------------
        | 2. BUAT HISTORY BULAN INI SETELAH SALDO_AWAL DIUPDATE
        -------------------------------------------*/
        $historyNow = KasSaldoHistory::firstOrCreate(
            ['kas_id' => $kas->id, 'tahun' => $tahunNow, 'bulan' => $bulanNow],
            ['saldo_awal' => $kas->saldo_awal, 'saldo_akhir' => $kas->saldo]
        );

        /*------------------------------------------
        | 3. UPDATE SALDO KAS
        -------------------------------------------*/
        if ($tipe[0] === 'in') {
            $kas->saldo += $total_nominal;
        } elseif ($tipe[0] === 'out') {
            $kas->saldo -= $total_nominal;
        }

        $kas->save();

        /*------------------------------------------
        | 4. UPDATE SALDO AKHIR HISTORY BULAN INI
        -------------------------------------------*/
        KasSaldoHistory::where('kas_id', $kas->id)
            ->where('tahun', $tahunNow)
            ->where('bulan', $bulanNow)
            ->update(['saldo_akhir' => $kas->saldo]);

        $labaNominal = $total_nominal;
        $tokoId = $kas->toko_id;

        if ($laba == true) {
            /* ============================
            | LABA RUGI BULANAN
            ===============================*/
            $labaRugi = LabaRugi::firstOrCreate(
                [
                    'toko_id' => $tokoId,
                    'tahun'   => $tahunNow,
                    'bulan'   => $bulanNow,
                ],
                [
                    'pendapatan'  => 0,
                    'beban'       => 0,
                    'laba_bersih' => 0,
                ]
            );

            if ($tipe[0] === 'in') {
                $labaRugi->pendapatan += $labaNominal;
            } elseif ($tipe[0] === 'out') {
                $labaRugi->beban += $labaNominal;
            }

            $labaRugi->laba_bersih =
                $labaRugi->pendapatan - $labaRugi->beban;

            $labaRugi->save();

            /* ============================
            | LABA RUGI TAHUNAN
            ===============================*/
            $labaRugiTahunan = LabaRugiTahunan::firstOrCreate(
                [
                    'toko_id' => $tokoId,
                    'tahun'   => $tahunNow,
                ],
                [
                    'pendapatan'  => 0,
                    'beban'       => 0,
                    'laba_bersih' => 0,
                ]
            );

            if ($tipe[0] === 'in') {
                $labaRugiTahunan->pendapatan += $labaNominal;
            } elseif ($tipe[0] === 'out') {
                $labaRugiTahunan->beban += $labaNominal;
            }

            $labaRugiTahunan->laba_bersih =
                $labaRugiTahunan->pendapatan - $labaRugiTahunan->beban;

            $labaRugiTahunan->save();
        }

        /*------------------------------------------
        | 5. SIMPAN TRANSAKSI
        -------------------------------------------*/
        return KasTransaksi::create([
            'kas_id' => $kas->id,
            'tipe' => $tipe[1] ?? $tipe[0],
            'kode_transaksi' => 'KS-' . time() . rand(100, 999),
            'total_nominal' => $total_nominal,
            'kategori' => $kategori,
            'keterangan' => $keterangan,
            'item' => $item,
            'sumber_type' => $sumber ? get_class($sumber) : null,
            'sumber_id' => $sumber?->id,
            'tanggal' => $tanggal ?? now(),
        ]);
    }

    public static function pengiriman($tokoPusatId, $tokoCabangId, $jenis_barang_id, $total_nominal, $sumber = null, $tanggal = null)
    {
        return DB::transaction(function () use ($tokoPusatId, $tokoCabangId, $jenis_barang_id, $total_nominal, $sumber, $tanggal) {

            /* ===============================
         * 1. PASTIKAN KAS ADA, TAPI TIDAK DIUBAH SALDONYA
         * =============================== */

            $kasPusat = Kas::firstOrCreate(
                ['toko_id' => $tokoPusatId, 'jenis_barang_id' => $jenis_barang_id, 'tipe_kas' => 'kecil'],
                ['saldo_awal' => 0, 'saldo' => 0, 'tanggal' => $tanggal]
            );

            $kasCabang = Kas::firstOrCreate(
                ['toko_id' => $tokoCabangId, 'jenis_barang_id' => $jenis_barang_id, 'tipe_kas' => 'besar'],
                ['saldo_awal' => 0, 'saldo' => 0, 'tanggal' => $tanggal]
            );

            /* ===============================
         * 2. TIDAK UPDATE SALDO
         * Jadi tidak memanggil handleKas()
         * =============================== */


            /* ===============================
         * 3. BUAT PIUTANG DI PUSAT
         * =============================== */
            Piutang::create([
                'toko_id' => $tokoPusatId,
                'toko_lawan_id' => $tokoCabangId,
                'nominal' => $total_nominal,
                'status' => 'belum bayar',
                'sumber_type' => $sumber ? get_class($sumber) : null,
                'sumber_id' => $sumber?->id,
                'tanggal' => $tanggal
            ]);

            /* ===============================
         * 4. BUAT HUTANG DI CABANG
         * =============================== */
            Hutang::create([
                'toko_id' => $tokoCabangId,
                'toko_lawan_id' => $tokoPusatId,
                'nominal' => $total_nominal,
                'status' => 'belum bayar',
                'sumber_type' => $sumber ? get_class($sumber) : null,
                'sumber_id' => $sumber?->id,
                'tanggal' => $tanggal
            ]);

            return true;
        });
    }

    public static function delete($kasId, $id, $sumber, $tanggal)
    {
        return DB::transaction(function () use ($kasId, $id, $sumber, $tanggal) {

            $trx = KasTransaksi::where('kas_id', $kasId)
                ->where('sumber_type', $sumber)
                ->where('sumber_id', $id)
                ->firstOrFail();

            $kas = Kas::findOrFail($trx->kas_id);

            $tanggalRef = $tanggal
                ? Carbon::parse($tanggal)
                : Carbon::parse($trx->tanggal);

            $year   = $tanggalRef->year;
            $month  = $tanggalRef->month;
            $tokoId = $kas->toko_id;

            $nominal = (float) $trx->total_nominal;

            // =========================
            // HITUNG DELTA
            // =========================
            if (str_contains($trx->tipe, 'in')) {
                $deltaKas  = -$nominal;
                $deltaIn   = -$nominal;
                $deltaOut  = 0;
            } else {
                $deltaKas  = +$nominal;
                $deltaIn   = 0;
                $deltaOut  = -$nominal;
            }

            // =========================
            // 1. UPDATE SALDO KAS
            // =========================
            $kas->saldo += $deltaKas;
            $kas->save();

            // =========================
            // 2. UPDATE HISTORY BULAN
            // =========================
            $history = KasSaldoHistory::firstOrCreate(
                ['kas_id' => $kas->id, 'tahun' => $year, 'bulan' => $month],
                ['saldo_awal' => 0, 'saldo_akhir' => 0]
            );

            $history->saldo_akhir += $deltaKas;
            $history->save();

            // =========================
            // 3. REBUILD BULAN SETELAHNYA
            // =========================
            $current = $history;

            while ($next = KasSaldoHistory::where('kas_id', $kas->id)
                ->where(function ($q) use ($current) {
                    $q->where('tahun', '>', $current->tahun)
                        ->orWhere(
                            fn($q2) =>
                            $q2->where('tahun', $current->tahun)
                                ->where('bulan', '>', $current->bulan)
                        );
                })
                ->orderBy('tahun')
                ->orderBy('bulan')
                ->first()
            ) {
                $next->saldo_awal  = $current->saldo_akhir;
                $next->saldo_akhir += $deltaKas;
                $next->save();
                $current = $next;
            }

            // =========================
            // 4. UPDATE LABA RUGI BULANAN
            // =========================
            $lr = LabaRugi::firstOrCreate(
                ['toko_id' => $tokoId, 'tahun' => $year, 'bulan' => $month],
                ['pendapatan' => 0, 'beban' => 0, 'laba_bersih' => 0]
            );

            $lr->pendapatan += $deltaIn;
            $lr->beban      += abs($deltaOut);
            $lr->laba_bersih = $lr->pendapatan - $lr->beban;
            $lr->save();

            // =========================
            // 5. UPDATE LABA RUGI TAHUNAN
            // =========================
            $lrt = LabaRugiTahunan::firstOrCreate(
                ['toko_id' => $tokoId, 'tahun' => $year],
                ['pendapatan' => 0, 'beban' => 0, 'laba_bersih' => 0]
            );

            $lrt->pendapatan += $deltaIn;
            $lrt->beban      += abs($deltaOut);
            $lrt->laba_bersih = $lrt->pendapatan - $lrt->beban;
            $lrt->save();

            // =========================
            // 6. DELETE TRANSAKSI
            // =========================
            $trx->delete();

            return true;
        });
    }

    public static function update($kasId, $id, $sumber, $tanggal, $totalNominal)
    {
        return DB::transaction(function () use ($kasId, $id, $sumber, $tanggal, $totalNominal) {

            $trx = KasTransaksi::where('kas_id', $kasId)
                ->where('sumber_type', $sumber)
                ->where('sumber_id', $id)
                ->firstOrFail();

            $kas = Kas::findOrFail($trx->kas_id);

            $tanggalRef = $tanggal
                ? Carbon::parse($tanggal)
                : Carbon::parse($trx->tanggal);

            $year   = $tanggalRef->year;
            $month  = $tanggalRef->month;
            $tokoId = $kas->toko_id;

            $old = (float) $trx->total_nominal;
            $new = (float) $totalNominal;
            $diff = $new - $old;

            if ($diff == 0) return true;

            if (str_contains($trx->tipe, 'in')) {
                $deltaKas = -$diff;
                $deltaIn  = -$diff;
                $deltaOut = 0;
            } else {
                $deltaKas = +$diff;
                $deltaIn  = 0;
                $deltaOut = -$diff;
            }

            // =========================
            // UPDATE SALDO KAS
            // =========================
            $kas->saldo += $deltaKas;
            $kas->save();

            // =========================
            // UPDATE HISTORY
            // =========================
            $history = KasSaldoHistory::firstOrCreate(
                ['kas_id' => $kas->id, 'tahun' => $year, 'bulan' => $month],
                ['saldo_awal' => 0, 'saldo_akhir' => 0]
            );

            $history->saldo_akhir += $deltaKas;
            $history->save();

            // =========================
            // LABA RUGI BULANAN
            // =========================
            $lr = LabaRugi::firstOrCreate(
                ['toko_id' => $tokoId, 'tahun' => $year, 'bulan' => $month],
                ['pendapatan' => 0, 'beban' => 0, 'laba_bersih' => 0]
            );

            $lr->pendapatan += $deltaIn;
            $lr->beban      += abs($deltaOut);
            $lr->laba_bersih = $lr->pendapatan - $lr->beban;
            $lr->save();

            // =========================
            // LABA RUGI TAHUNAN
            // =========================
            $lrt = LabaRugiTahunan::firstOrCreate(
                ['toko_id' => $tokoId, 'tahun' => $year],
                ['pendapatan' => 0, 'beban' => 0, 'laba_bersih' => 0]
            );

            $lrt->pendapatan += $deltaIn;
            $lrt->beban      += abs($deltaOut);
            $lrt->laba_bersih = $lrt->pendapatan - $lrt->beban;
            $lrt->save();

            // =========================
            // UPDATE TRANSAKSI
            // =========================
            $trx->update(['total_nominal' => $new]);

            return true;
        });
    }

    public static function deleteMutasi($mutasi): bool
    {
        return DB::transaction(function () use ($mutasi) {

            $tanggalRef = Carbon::parse($mutasi->tanggal);
            $year  = (int) $tanggalRef->year;
            $month = (int) $tanggalRef->month;

            $nominal = (float) $mutasi->nominal;

            // =====================
            // 1. KAS ASAL (OUT) → DIKEMBALIKAN
            // =====================
            if ($mutasi->kas_asal_id) {
                self::rollbackKas(
                    kasId: $mutasi->kas_asal_id,
                    year: $year,
                    month: $month,
                    delta: +$nominal // dikembalikan
                );
            }

            // =====================
            // 2. KAS TUJUAN (IN) → DIKURANGI
            // =====================
            if ($mutasi->kas_tujuan_id) {
                self::rollbackKas(
                    kasId: $mutasi->kas_tujuan_id,
                    year: $year,
                    month: $month,
                    delta: -$nominal // dikurangi
                );
            }

            return true;
        });
    }


    private static function rollbackKas(int $kasId, int $year, int $month, float $delta): void
    {
        $kas = Kas::findOrFail($kasId);

        // 1. Update saldo kas utama
        $kas->saldo += $delta;
        $kas->save();

        // 2. Ambil / buat history bulan transaksi
        $history = KasSaldoHistory::firstOrCreate(
            ['kas_id' => $kas->id, 'tahun' => $year, 'bulan' => $month],
            ['saldo_awal' => 0, 'saldo_akhir' => 0]
        );

        // 3. Geser saldo akhir bulan transaksi
        $history->saldo_akhir += $delta;
        $history->save();

        // 4. Rebuild history bulan setelahnya
        $current = $history;

        while (true) {
            $next = KasSaldoHistory::where('kas_id', $kas->id)
                ->where(function ($q) use ($current) {
                    $q->where('tahun', '>', $current->tahun)
                        ->orWhere(function ($q2) use ($current) {
                            $q2->where('tahun', $current->tahun)
                                ->where('bulan', '>', $current->bulan);
                        });
                })
                ->orderBy('tahun')
                ->orderBy('bulan')
                ->first();

            if (!$next) {
                break;
            }

            $next->saldo_awal = $current->saldo_akhir;
            $next->saldo_akhir += $delta;
            $next->save();

            $current = $next;
        }

        // 5. Sinkron saldo_awal kas
        $latestHistory = KasSaldoHistory::where('kas_id', $kas->id)
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->first();

        $kas->saldo_awal = $latestHistory
            ? $latestHistory->saldo_awal
            : $kas->saldo;

        $kas->save();
    }

    public static function tipeKasName($kas)
    {
        return match ((string) $kas->tipe_kas) {
            'besar' => 'Kas Besar',
            'kecil' => 'Kas Kecil',
            default => 'Kas Tidak Dikenal'
        };
    }
}
