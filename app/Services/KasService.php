<?php

namespace App\Services;

use App\Helpers\LogAktivitasGenerate;
use App\Helpers\LogHelper;
use App\Helpers\RupiahGenerate;
use App\Helpers\TextGenerate;
use App\Models\Hutang;
use App\Models\Kas;
use App\Models\KasTransaksi;
use App\Models\KasSaldoHistory;
use App\Models\LabaRugi;
use App\Models\LabaRugiTahunan;
use App\Models\PembelianBarang;
use App\Models\Piutang;
use App\Models\TransaksiKasirHarian;
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

    public static function topup(
        $toko_id,
        $jenis_barang_id,
        $tipe_kas,
        $saldo,
        $hargaBeli,
        $item,
        $kategori = 'Saldo Digital',
        $keterangan = 'Top-up Saldo',
        $sumber,
        $tanggal = null
    ) {
        $kas = Kas::firstOrCreate(
            ['toko_id' => $toko_id, 'jenis_barang_id' => $jenis_barang_id, 'tipe_kas' => $tipe_kas],
            ['saldo_awal' => 0, 'saldo' => 0, 'tanggal' => $tanggal ?? now()]
        );

        return self::coreTopupKas($kas, $saldo, $hargaBeli, $item, $kategori, $keterangan, $sumber, $tanggal);
    }


    /*===============================
    * FITUR MUTASI KAS (TRANSFER)
    ===============================*/

    public static function mutasi(
        $fromKas,  // kas asal (object Kas)
        $toKas,    // kas tujuan (object Kas)
        $nominal,
        $sumber = null,
        $tanggal = null,
        $laba = false
    ) {
        return DB::transaction(function () use ($fromKas, $toKas, $nominal, $sumber, $tanggal, $laba) {

            // 1) KAS KELUAR
            self::coreUpdateKas(
                tipe: ['out'],
                kas: $fromKas,
                total_nominal: $nominal,
                item: $fromKas->tipe_kas,
                kategori: 'Mutasi Kas',
                keterangan: 'Mutasi Kas Keluar',
                sumber: $sumber,
                tanggal: $tanggal,
                laba: $laba
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
                tanggal: $tanggal,
                laba: $laba
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

    private static function coreTopupKas(
        Kas $kas,
        float $saldo,
        float $hargaBeli,
        $item,
        $kategori,
        $keterangan,
        $sumber,
        $tanggal
    ) {
        $fTanggal = Carbon::now();
        $tahunNow = $fTanggal->year;
        $bulanNow = $fTanggal->month;

        // 1️⃣ tutup bulan terakhir jika perlu
        $lastHistory = KasSaldoHistory::where('kas_id', $kas->id)
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->first();

        if ($lastHistory && ($lastHistory->bulan != $bulanNow || $lastHistory->tahun != $tahunNow)) {
            $lastHistory->update(['saldo_akhir' => $kas->saldo]);
            $kas->saldo_awal = $kas->saldo;
            $kas->save();
        }

        // 2️⃣ history bulan ini
        $historyNow = KasSaldoHistory::firstOrCreate(
            ['kas_id' => $kas->id, 'tahun' => $tahunNow, 'bulan' => $bulanNow],
            ['saldo_awal' => $kas->saldo_awal, 'saldo_akhir' => $kas->saldo]
        );

        /*------------------------------------------
    | 3️⃣ HITUNG TRANSAKSI
    -------------------------------------------*/
        $transaksiKas = [];

        // Nominal utama = min(saldo, harga_beli)
        $nominalUtama = min($saldo, $hargaBeli);

        // 1. Kurangi saldo utama (out)
        $kas->saldo -= $nominalUtama;
        $kas->save();

        $transaksiKas[] = KasTransaksi::create([
            'kas_id' => $kas->id,
            'tipe' => 'out',
            'kode_transaksi' => 'KS-' . time() . rand(100, 999),
            'total_nominal' => $nominalUtama,
            'kategori' => $kategori,
            'keterangan' => $keterangan,
            'item' => $item,
            'sumber_type' => $sumber ? get_class($sumber) : null,
            'sumber_id' => $sumber?->id,
            'tanggal' => $tanggal ?? now(),
        ]);

        // 2. Hitung selisih
        $selisih = round($hargaBeli - $saldo, 2);

        if ($selisih != 0) {
            $labaRugi = LabaRugi::firstOrCreate(
                ['toko_id' => $kas->toko_id, 'tahun' => $tahunNow, 'bulan' => $bulanNow],
                ['pendapatan' => 0, 'beban' => 0, 'laba_bersih' => 0]
            );

            $labaRugiTahunan = LabaRugiTahunan::firstOrCreate(
                ['toko_id' => $kas->toko_id, 'tahun' => $tahunNow],
                ['pendapatan' => 0, 'beban' => 0, 'laba_bersih' => 0]
            );

            if ($selisih > 0) {
                // harga_beli > saldo → selisih sebagai beban (out)
                $tipeSelisih = 'out';
                $labaNominal = $selisih;

                $kas->saldo -= $labaNominal; // kurangi saldo kas
                $kas->save();

                $labaRugi->beban += $labaNominal;
                $labaRugiTahunan->beban += $labaNominal;
            } else {
                // harga_beli < saldo → selisih sebagai pendapatan (in)
                $tipeSelisih = 'in';
                $labaNominal = abs($selisih);

                // kas tidak berubah
                $labaRugi->pendapatan += $labaNominal;
                $labaRugiTahunan->pendapatan += $labaNominal;
            }

            $labaRugi->laba_bersih = $labaRugi->pendapatan - $labaRugi->beban;
            $labaRugi->save();

            $labaRugiTahunan->laba_bersih = $labaRugiTahunan->pendapatan - $labaRugiTahunan->beban;
            $labaRugiTahunan->save();

            // Simpan transaksi selisih hanya jika selisih > 0
            if ($selisih > 0) {
                $transaksiKas[] = KasTransaksi::create([
                    'kas_id' => $kas->id,
                    'tipe' => $tipeSelisih,
                    'kode_transaksi' => 'KS-' . time() . rand(100, 999),
                    'total_nominal' => $labaNominal,
                    'kategori' => $kategori,
                    'keterangan' => 'Selisih Top-up',
                    'item' => $item,
                    'sumber_type' => $sumber ? get_class($sumber) : null,
                    'sumber_id' => $sumber?->id,
                    'tanggal' => $tanggal ?? now(),
                ]);
            }
        }


        // 4️⃣ update history saldo akhir
        KasSaldoHistory::where('kas_id', $kas->id)
            ->where('tahun', $tahunNow)
            ->where('bulan', $bulanNow)
            ->update(['saldo_akhir' => $kas->saldo]);

        return $transaksiKas;
    }

    public static function updatePembelianBarang(
        PembelianBarang $pembelian,
        float $deltaNominal,
        int $userId,
        $edit = true
    ) {
        if ($deltaNominal == 0) return;

        DB::transaction(function () use ($pembelian, $deltaNominal, $userId, $edit) {

            $kas = Kas::where('id', $pembelian->kas_id)
                ->lockForUpdate()
                ->firstOrFail();

            /**
             * ======================================
             * OLD SALDO
             * ======================================
             */
            $oldSaldo = (float) $kas->saldo;

            $tahun = now()->year;
            $bulan = now()->month;

            $history = KasSaldoHistory::where('kas_id', $kas->id)
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->lockForUpdate()
                ->first();

            /**
             * ======================================
             * VALIDASI SALDO
             * ======================================
             *
             * deltaNominal > 0 :
             * pembelian naik -> kas keluar lagi
             *
             * deltaNominal < 0 :
             * pembelian turun -> kas masuk kembali
             */
            if ($deltaNominal > 0) {

                if ($kas->saldo < $deltaNominal) {
                    throw new \Exception(
                        "Saldo kas tidak mencukupi. Saldo tersedia: " .
                            RupiahGenerate::build($kas->saldo)
                    );
                }

                if ($history && $history->saldo_akhir < $deltaNominal) {
                    throw new \Exception(
                        "Saldo akhir kas bulan berjalan tidak mencukupi. Saldo akhir: " .
                            RupiahGenerate::build($history->saldo_akhir)
                    );
                }
            }

            /**
             * ======================================
             * UPDATE SALDO KAS
             * ======================================
             */
            if ($deltaNominal > 0) {
                $kas->saldo -= abs($deltaNominal);
            } else {
                $kas->saldo += abs($deltaNominal);
            }

            $kas->save();

            /**
             * ======================================
             * UPDATE HISTORY BULANAN
             * ======================================
             */
            if ($history) {

                $history->saldo_akhir = $kas->saldo;
                $history->save();
            } else {

                KasSaldoHistory::create([
                    'kas_id'      => $kas->id,
                    'tahun'       => $tahun,
                    'bulan'       => $bulan,
                    'saldo_awal'  => $kas->saldo_awal,
                    'saldo_akhir' => $kas->saldo,
                ]);
            }

            /**
             * ======================================
             * KAS TRANSAKSI (PENTING)
             * ======================================
             *
             * deltaNominal > 0
             * harga beli naik / qty naik
             * => kas keluar tambahan
             *
             * deltaNominal < 0
             * harga beli turun / qty turun
             * => kas masuk pengembalian
             */
            $isNaik = $deltaNominal > 0;

            $tipe = $isNaik ? 'out' : 'in';

            $kategori = 'Pembelian Barang';

            /**
             * KETERANGAN LEBIH AKURAT
             */
            $keterangan = $isNaik
                ? "Koreksi Kas OUT"
                : "Koreksi Kas IN";
            /**
             * KODE TRANSAKSI UNIK
             */
            $kode = 'KS-ADJ-' . now()->format('YmdHis') . '-' . $pembelian->id;

            KasTransaksi::create([
                'kas_id'         => $kas->id,
                'tipe'           => $tipe,
                'kode_transaksi' => $kode,
                'total_nominal'  => abs($deltaNominal),
                'kategori'       => $kategori,
                'keterangan'     => $keterangan,
                'item'           => 'kecil',
                'sumber_type'    => PembelianBarang::class,
                'sumber_id'      => $pembelian->id,
                'tanggal'        => now(),
            ]);

            /**
             * ======================================
             * LOG AKTIVITAS
             * ======================================
             */
            $arahSaldo = $isNaik ? 'berkurang' : 'bertambah';

            $nominal = number_format(abs($deltaNominal), 0, ',', '.');

            $newSaldo = $kas->saldo;

            LogAktivitasGenerate::store(
                logName: 'Kas',
                subjectType: Kas::class,
                subjectId: $kas->id,
                event: $edit ? 'Edit Data' : 'Hapus Data',
                properties: LogHelper::buildChanges(
                    old: [
                        'saldo' => TextGenerate::formatNumber($oldSaldo),
                    ],
                    new: [
                        'saldo' => TextGenerate::formatNumber($newSaldo),
                        'delta_nominal' => TextGenerate::formatNumber($deltaNominal),
                    ]
                ),
                description: $edit
                    ? "Penyesuaian pembelian nota {$pembelian->nota}, saldo {$arahSaldo} Rp {$nominal}"
                    : "Pembatalan pembelian nota {$pembelian->nota}, saldo {$arahSaldo} Rp {$nominal}",
                userId: $userId,
                message: "(Sistem) Saldo kas {$arahSaldo}"
            );
        });
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

    public static function updateLabaRugi(
        int $tokoId,
        int $tahun,
        int $bulan,
        string $tipe,
        float|int $nominal
    ): void {

        $labaRugi = LabaRugi::firstOrCreate(
            [
                'toko_id' => $tokoId,
                'tahun'   => $tahun,
                'bulan'   => $bulan,
            ],
            [
                'pendapatan'  => 0,
                'beban'       => 0,
                'laba_bersih' => 0,
            ]
        );

        if ($tipe === 'in') {
            $labaRugi->pendapatan += $nominal;
        } else {
            $labaRugi->beban += $nominal;
        }

        $labaRugi->laba_bersih =
            $labaRugi->pendapatan - $labaRugi->beban;

        $labaRugi->save();

        $labaRugiTahunan = LabaRugiTahunan::firstOrCreate(
            [
                'toko_id' => $tokoId,
                'tahun'   => $tahun,
            ],
            [
                'pendapatan'  => 0,
                'beban'       => 0,
                'laba_bersih' => 0,
            ]
        );

        if ($tipe === 'in') {
            $labaRugiTahunan->pendapatan += $nominal;
        } else {
            $labaRugiTahunan->beban += $nominal;
        }

        $labaRugiTahunan->laba_bersih =
            $labaRugiTahunan->pendapatan - $labaRugiTahunan->beban;

        $labaRugiTahunan->save();
    }

    // public static function delete($kasId, $id, $sumber, $tanggal, $laba = true, $saldo = true)
    // {
    //     return DB::transaction(function () use ($kasId, $id, $sumber, $tanggal, $laba, $saldo) {

    //         $trx = KasTransaksi::where('kas_id', $kasId)
    //             ->where('sumber_type', $sumber)
    //             ->where('sumber_id', $id)
    //             ->firstOrFail();

    //         if ($saldo) {
    //             $kas = Kas::findOrFail($trx->kas_id);

    //             $tanggalRef = $tanggal
    //                 ? Carbon::parse($tanggal)
    //                 : Carbon::parse($trx->tanggal);

    //             $year   = $tanggalRef->year;
    //             $month  = $tanggalRef->month;
    //             $tokoId = $kas->toko_id;

    //             $nominal = (float) $trx->total_nominal;

    //             // =========================
    //             // HITUNG DELTA
    //             // =========================
    //             if (str_contains($trx->tipe, 'in')) {
    //                 $deltaKas  = -$nominal;
    //                 $deltaIn   = -$nominal;
    //                 $deltaOut  = 0;
    //             } else {
    //                 $deltaKas  = +$nominal;
    //                 $deltaIn   = 0;
    //                 $deltaOut  = -$nominal;
    //             }

    //             // =========================
    //             // 1. UPDATE SALDO KAS
    //             // =========================
    //             $kas->saldo += $deltaKas;
    //             $kas->save();

    //             // =========================
    //             // 2. UPDATE HISTORY BULAN
    //             // =========================
    //             $history = KasSaldoHistory::firstOrCreate(
    //                 ['kas_id' => $kas->id, 'tahun' => $year, 'bulan' => $month],
    //                 ['saldo_awal' => 0, 'saldo_akhir' => 0]
    //             );

    //             $history->saldo_akhir += $deltaKas;
    //             $history->save();

    //             // =========================
    //             // 3. REBUILD BULAN SETELAHNYA
    //             // =========================
    //             $current = $history;

    //             while ($next = KasSaldoHistory::where('kas_id', $kas->id)
    //                 ->where(function ($q) use ($current) {
    //                     $q->where('tahun', '>', $current->tahun)
    //                         ->orWhere(
    //                             fn($q2) =>
    //                             $q2->where('tahun', $current->tahun)
    //                                 ->where('bulan', '>', $current->bulan)
    //                         );
    //                 })
    //                 ->orderBy('tahun')
    //                 ->orderBy('bulan')
    //                 ->first()
    //             ) {
    //                 $next->saldo_awal  = $current->saldo_akhir;
    //                 $next->saldo_akhir += $deltaKas;
    //                 $next->save();
    //                 $current = $next;
    //             }
    //         }
    //         // =========================
    //         // 4 & 5. UPDATE LABA RUGI (OPTIONAL)
    //         // =========================
    //         if ($laba) {

    //             // BULANAN
    //             $lr = LabaRugi::firstOrCreate(
    //                 ['toko_id' => $tokoId, 'tahun' => $year, 'bulan' => $month],
    //                 ['pendapatan' => 0, 'beban' => 0, 'laba_bersih' => 0]
    //             );

    //             if (str_contains($trx->tipe, 'in')) {
    //                 $lr->pendapatan -= $nominal; // kebalikan dari insert
    //             } else {
    //                 $lr->beban -= $nominal; // kebalikan dari insert
    //             }

    //             $lr->laba_bersih = $lr->pendapatan - $lr->beban;
    //             $lr->save();

    //             // TAHUNAN
    //             $lrt = LabaRugiTahunan::firstOrCreate(
    //                 ['toko_id' => $tokoId, 'tahun' => $year],
    //                 ['pendapatan' => 0, 'beban' => 0, 'laba_bersih' => 0]
    //             );

    //             if (str_contains($trx->tipe, 'in')) {
    //                 $lrt->pendapatan -= $nominal;
    //             } else {
    //                 $lrt->beban -= $nominal;
    //             }

    //             $lrt->laba_bersih = $lrt->pendapatan - $lrt->beban;
    //             $lrt->save();
    //         }

    //         // =========================
    //         // 6. DELETE TRANSAKSI
    //         // =========================
    //         $trx->delete();

    //         $exists = KasTransaksi::where('kas_id', $kasId)
    //             ->where('sumber_type', $sumber)
    //             ->where('sumber_id', $id)
    //             ->exists();

    //         if ($exists) {
    //             KasTransaksi::where('kas_id', $kasId)
    //                 ->where('sumber_type', $sumber)
    //                 ->where('sumber_id', $id)
    //                 ->delete();
    //         }
    //         return true;
    //     });
    // }

    public static function delete($kasId, $id, $sumber, $tanggal, $laba = true, $saldo = true)
    {
        return DB::transaction(function () use (
            $kasId,
            $id,
            $sumber,
            $tanggal,
            $laba,
            $saldo
        ) {

            /**
             * 🔥 FIX UTAMA:
             * Jangan ambil firstOrFail()
             * karena bisa ada banyak transaksi kas
             * dan deleteDetail sebelumnya sudah menghapus sebagian nominal.
             *
             * Sekarang ambil semua transaksi yg masih aktif.
             */
            $trxList = KasTransaksi::where('kas_id', $kasId)
                ->where('sumber_type', $sumber)
                ->where('sumber_id', $id)
                ->lockForUpdate()
                ->get();

            if ($trxList->isEmpty()) {
                return true;
            }

            foreach ($trxList as $trx) {

                $nominal = (float) $trx->total_nominal;

                // =========================
                // HANDLE SALDO KAS
                // =========================
                if ($saldo) {

                    $kas = Kas::lockForUpdate()->findOrFail($trx->kas_id);

                    $tanggalRef = $tanggal
                        ? Carbon::parse($tanggal)
                        : Carbon::parse($trx->tanggal);

                    $year   = $tanggalRef->year;
                    $month  = $tanggalRef->month;
                    $tokoId = $kas->toko_id;

                    if (str_contains($trx->tipe, 'in')) {
                        $deltaKas = -$nominal;
                    } else {
                        $deltaKas = +$nominal;
                    }

                    // 1. saldo kas
                    $kas->saldo += $deltaKas;
                    $kas->save();

                    // 2. history bulan berjalan
                    $history = KasSaldoHistory::firstOrCreate(
                        [
                            'kas_id' => $kas->id,
                            'tahun'  => $year,
                            'bulan'  => $month
                        ],
                        [
                            'saldo_awal'  => 0,
                            'saldo_akhir' => 0
                        ]
                    );

                    $history->saldo_akhir += $deltaKas;
                    $history->save();

                    // 3. bulan berikutnya
                    $current = $history;

                    while ($next = KasSaldoHistory::where('kas_id', $kas->id)
                        ->where(function ($q) use ($current) {
                            $q->where('tahun', '>', $current->tahun)
                                ->orWhere(function ($q2) use ($current) {
                                    $q2->where('tahun', $current->tahun)
                                        ->where('bulan', '>', $current->bulan);
                                });
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
                } else {
                    $tanggalRef = $tanggal
                        ? Carbon::parse($tanggal)
                        : Carbon::parse($trx->tanggal);

                    $year  = $tanggalRef->year;
                    $month = $tanggalRef->month;

                    $kas = Kas::find($trx->kas_id);
                    $tokoId = $kas?->toko_id;
                }

                // =========================
                // HANDLE LABA RUGI
                // =========================
                if ($laba && $tokoId) {

                    $lr = LabaRugi::firstOrCreate(
                        [
                            'toko_id' => $tokoId,
                            'tahun'   => $year,
                            'bulan'   => $month
                        ],
                        [
                            'pendapatan' => 0,
                            'beban'      => 0,
                            'laba_bersih' => 0
                        ]
                    );

                    if (str_contains($trx->tipe, 'in')) {
                        $lr->pendapatan -= $nominal;
                    } else {
                        $lr->beban -= $nominal;
                    }

                    $lr->laba_bersih = $lr->pendapatan - $lr->beban;
                    $lr->save();

                    $lrt = LabaRugiTahunan::firstOrCreate(
                        [
                            'toko_id' => $tokoId,
                            'tahun'   => $year
                        ],
                        [
                            'pendapatan' => 0,
                            'beban'      => 0,
                            'laba_bersih' => 0
                        ]
                    );

                    if (str_contains($trx->tipe, 'in')) {
                        $lrt->pendapatan -= $nominal;
                    } else {
                        $lrt->beban -= $nominal;
                    }

                    $lrt->laba_bersih = $lrt->pendapatan - $lrt->beban;
                    $lrt->save();
                }

                // hapus transaksi satu per satu
                $trx->delete();
            }

            return true;
        });
    }

    public static function deleteTopup($kasId, $id, $sumber, $tanggal)
    {
        return DB::transaction(function () use ($kasId, $id, $sumber, $tanggal) {

            $kas = Kas::findOrFail($kasId);

            $fTanggal = Carbon::parse($tanggal);
            $tahun = $fTanggal->year;
            $bulan = $fTanggal->month;

            // Ambil semua transaksi terkait
            $transaksiList = KasTransaksi::where('kas_id', $kasId)
                ->where('sumber_type', $sumber)
                ->where('sumber_id', $id)
                ->get();

            foreach ($transaksiList as $trx) {

                // 🔁 BALIK SALDO KAS
                if ($trx->tipe === 'out') {
                    $kas->saldo += $trx->total_nominal;
                } else {
                    $kas->saldo -= $trx->total_nominal;
                }

                // 🔁 ROLLBACK LABA RUGI (hanya untuk selisih)
                if ($trx->keterangan === 'Selisih Top-up') {

                    $labaRugi = LabaRugi::where('toko_id', $kas->toko_id)
                        ->where('tahun', $tahun)
                        ->where('bulan', $bulan)
                        ->first();

                    $labaRugiTahunan = LabaRugiTahunan::where('toko_id', $kas->toko_id)
                        ->where('tahun', $tahun)
                        ->first();

                    if ($trx->tipe === 'out') {
                        // sebelumnya beban → rollback
                        if ($labaRugi) {
                            $labaRugi->beban -= $trx->total_nominal;
                            $labaRugi->laba_bersih = $labaRugi->pendapatan - $labaRugi->beban;
                            $labaRugi->save();
                        }

                        if ($labaRugiTahunan) {
                            $labaRugiTahunan->beban -= $trx->total_nominal;
                            $labaRugiTahunan->laba_bersih = $labaRugiTahunan->pendapatan - $labaRugiTahunan->beban;
                            $labaRugiTahunan->save();
                        }
                    } else {
                        // sebelumnya pendapatan → rollback
                        if ($labaRugi) {
                            $labaRugi->pendapatan -= $trx->total_nominal;
                            $labaRugi->laba_bersih = $labaRugi->pendapatan - $labaRugi->beban;
                            $labaRugi->save();
                        }

                        if ($labaRugiTahunan) {
                            $labaRugiTahunan->pendapatan -= $trx->total_nominal;
                            $labaRugiTahunan->laba_bersih = $labaRugiTahunan->pendapatan - $labaRugiTahunan->beban;
                            $labaRugiTahunan->save();
                        }
                    }
                }

                // ======================================
                // 🔁 ROLLBACK UNTUNG (tidak ada transaksi selisih)
                // ======================================
                if (
                    $trx->keterangan === 'Top-up Saldo' &&
                    $trx->sumber_type === \App\Models\DompetSaldo::class
                ) {
                    $dompet = \App\Models\DompetSaldo::find($trx->sumber_id);

                    if ($dompet && $dompet->saldo > $dompet->harga_beli) {

                        $keuntungan = round($dompet->saldo - $dompet->harga_beli, 2);

                        $tanggalTrx = Carbon::parse($trx->tanggal ?? $trx->created_at);
                        $tahunTrx = $tanggalTrx->year;
                        $bulanTrx = $tanggalTrx->month;

                        $labaRugi = LabaRugi::where('toko_id', $kas->toko_id)
                            ->where('tahun', $tahunTrx)
                            ->where('bulan', $bulanTrx)
                            ->first();

                        $labaRugiTahunan = LabaRugiTahunan::where('toko_id', $kas->toko_id)
                            ->where('tahun', $tahunTrx)
                            ->first();

                        if ($labaRugi) {
                            $labaRugi->pendapatan -= $keuntungan;
                            $labaRugi->laba_bersih = $labaRugi->pendapatan - $labaRugi->beban;
                            $labaRugi->save();
                        }

                        if ($labaRugiTahunan) {
                            $labaRugiTahunan->pendapatan -= $keuntungan;
                            $labaRugiTahunan->laba_bersih = $labaRugiTahunan->pendapatan - $labaRugiTahunan->beban;
                            $labaRugiTahunan->save();
                        }
                    }
                }

                // 🗑️ hapus transaksi
                $trx->delete();
            }

            // 💾 simpan saldo kas
            $kas->save();

            // 🔄 update history
            KasSaldoHistory::where('kas_id', $kas->id)
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->update(['saldo_akhir' => $kas->saldo]);

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

    public static function kasir(
        $toko_id,
        $jenis_barang_id,
        $tipe_kas,
        $total_nominal,
        $item,
        $kategori = null,
        $keterangan = null,
        $sumber = null,
        $laba = true,
        $beban = 0
    ) {
        return DB::transaction(function () use (
            $toko_id,
            $jenis_barang_id,
            $tipe_kas,
            $total_nominal,
            $item,
            $kategori,
            $keterangan,
            $sumber,
            $laba,
            $beban
        ) {

            $today    = Carbon::today();
            $tahunNow = $today->year;
            $bulanNow = $today->month;

            // ===== KAS =====
            $kas = Kas::firstOrCreate(
                [
                    'toko_id' => $toko_id,
                    'jenis_barang_id' => $jenis_barang_id,
                    'tipe_kas' => $tipe_kas
                ],
                [
                    'saldo_awal' => 0,
                    'saldo' => 0,
                    'tanggal' => $today
                ]
            );

            // ===== HISTORY =====
            $historyNow = KasSaldoHistory::firstOrCreate(
                [
                    'kas_id' => $kas->id,
                    'tahun' => $tahunNow,
                    'bulan' => $bulanNow
                ],
                [
                    'saldo_awal' => $kas->saldo,
                    'saldo_akhir' => $kas->saldo
                ]
            );

            // ===== TRANSAKSI HARI INI =====
            $transaksi = KasTransaksi::where('kas_id', $kas->id)
                ->whereDate('tanggal', $today)
                ->where('sumber_type', get_class($sumber))
                ->where('sumber_id', $sumber->id)
                ->first();

            $isNewTransaksi = false;

            if (!$transaksi) {
                $isNewTransaksi = true;

                $transaksi = KasTransaksi::create([
                    'kas_id' => $kas->id,
                    'tipe' => 'in',
                    'kode_transaksi' => 'KS-' . time() . rand(100, 999),
                    'total_nominal' => $total_nominal,
                    'kategori' => $kategori,
                    'keterangan' => $keterangan,
                    'item' => $item,
                    'sumber_type' => get_class($sumber),
                    'sumber_id' => $sumber->id,
                    'tanggal' => now(),
                ]);

                $selisihPendapatan = $total_nominal;
                $selisihBeban = $beban;
            } else {
                $selisihPendapatan = $total_nominal - $transaksi->total_nominal;
                $selisihBeban = 0;

                $transaksi->update([
                    'total_nominal' => $total_nominal,
                    'tanggal' => now(),
                ]);
            }


            // ===== SALDO KAS =====
            if ($selisihPendapatan != 0) {
                $kas->increment('saldo', $selisihPendapatan);
            }

            // ===== HISTORY =====
            $historyNow->update([
                'saldo_akhir' => $kas->saldo
            ]);

            // ===== LABA RUGI =====
            if ($laba && $isNewTransaksi) {

                $labaRugi = LabaRugi::firstOrCreate(
                    [
                        'toko_id' => $toko_id,
                        'tahun' => $tahunNow,
                        'bulan' => $bulanNow
                    ],
                    [
                        'pendapatan' => 0,
                        'beban' => 0,
                        'laba_bersih' => 0
                    ]
                );

                $labaRugi->increment('pendapatan', $selisihPendapatan);
                $labaRugi->increment('beban', $selisihBeban);

                $labaRugi->refresh();

                $labaRugi->update([
                    'laba_bersih' => $labaRugi->pendapatan - $labaRugi->beban
                ]);

                // ===== TAHUNAN =====
                $labaRugiTahunan = LabaRugiTahunan::firstOrCreate(
                    [
                        'toko_id' => $toko_id,
                        'tahun' => $tahunNow
                    ],
                    [
                        'pendapatan' => 0,
                        'beban' => 0,
                        'laba_bersih' => 0
                    ]
                );

                $labaRugiTahunan->increment('pendapatan', $selisihPendapatan);
                $labaRugiTahunan->increment('beban', $selisihBeban);

                $labaRugiTahunan->refresh();

                $labaRugiTahunan->update([
                    'laba_bersih' =>
                    $labaRugiTahunan->pendapatan - $labaRugiTahunan->beban
                ]);
            }


            return $transaksi;
        });
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
