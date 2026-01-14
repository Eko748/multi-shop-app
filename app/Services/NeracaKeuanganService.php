<?php

namespace App\Services;

use App\Repositories\Distribusi\PengirimanBarangRepo;
use App\Models\JenisBarang;
use App\Models\Kas;
use App\Models\KasSaldoHistory;
use App\Repositories\{
    HutangRepository,
    PiutangRepository,
    PengeluaranRepository,
    PemasukanRepository,
    ReturRepository,
    StokRepository,
    NeracaPenyesuaianRepository,
    StockProblemRepository
};
use Carbon\Carbon;

class NeracaKeuanganService
{
    public function __construct(
        protected KasService $kasService,
        protected LabaRugiService $labaRugiService,
        protected DompetSaldoService $dompetSaldoService,
        protected HutangRepository $hutangRepo,
        protected PiutangRepository $piutangRepo,
        protected PengeluaranRepository $pengeluaranRepo,
        protected PemasukanRepository $pemasukanRepo,
        protected ReturRepository $returRepo,
        protected StokRepository $stokRepo,
        protected NeracaPenyesuaianRepository $neracaRepo,
        protected StockProblemRepository $stokProblemRepo,
        protected PengirimanBarangRepo $pengirimanBarangRepo,
    ) {}

    public function generateNeraca(int $month, int $year, $tokoId)
    {
        // Ambil data dari masing-masing repository
        $pengeluaranAset = $this->pengeluaranRepo->getPengeluaranAset($month, $year);

        $hutangItems = $this->hutangRepo->getActiveHutang($month, $year, $tokoId);
        $ekuitasItems = $this->generateEkuitas($month, $year, $tokoId);
        $returData = $this->returRepo->getReturData($month, $year);
        $piutangData = $this->piutangRepo->getActivePiutang($month, $year, $tokoId);
        $penyesuaianNeraca = $this->neracaRepo->getTotalPenyesuaian();
        $stokProblem = $this->stokProblemRepo->getStockProblem();
        $pengirimanData = $this->pengirimanBarangRepo->getStokPengirimanBarang($month, $year, $tokoId);
        // Kas besar/kecil via service
        $kasData = $this->generateKas($tokoId, $month, $year);

        // Satukan hasil akhir
        return [
            'data' => $this->composeNeracaStructure(
                $pengeluaranAset,
                $hutangItems,
                $ekuitasItems,
                $returData,
                $piutangData,
                $penyesuaianNeraca,
                $kasData,
                $month,
                $year,
                $pengirimanData,
                $tokoId
            ),
            'note' => $stokProblem,
        ];
    }

    private function generateEkuitas(int $month, int $year, $tokoId)
    {
        Carbon::setLocale('id');

        $items = [];

        // ==========================
        // IV.1 MODAL
        // ==========================
        $modal = (float) $this->pemasukanRepo->getModal($month, $year, $tokoId);

        $items[] = [
            "kode"   => "IV.1",
            "nama"   => "Modal",
            "nilai"  => $modal,
            "format" => 'Rp ' . number_format($modal, 0, ',', '.'),
        ];

        // ==========================
        // IV.2 LABA DITAHAN TAHUN SEBELUMNYA
        // ==========================
        $labaTahunSebelumnya =
            $this->labaRugiService->hitungLabaRugiTahunSebelumnya($year, $tokoId);

        $items[] = [
            "kode"   => "IV.2",
            "nama"   => "Laba (Rugi) Tahun Sebelumnya",
            "nilai"  => $labaTahunSebelumnya,
            "format" => 'Rp ' . number_format($labaTahunSebelumnya, 0, ',', '.'),
        ];

        // ==========================
        // LABA RUGI TAHUN BERJALAN
        // ==========================
        $labaRugiPerBulan =
            $this->labaRugiService->hitungLabaRugiRange($month, $year, $tokoId);

        $counter = 3;

        for ($i = 1; $i <= $month; $i++) {

            $periode = Carbon::create($year, $i, 1);
            $namaPeriode = $periode->translatedFormat('F Y');
            $nilai = (float) ($labaRugiPerBulan[$i] ?? 0);

            $items[] = [
                "kode"   => "IV.$counter",
                "nama"   => $i == $month
                    ? "Laba (Rugi) Berjalan Periode $namaPeriode"
                    : "Laba (Rugi) Ditahan Periode $namaPeriode",
                "nilai"  => $nilai,
                "format" => 'Rp ' . number_format($nilai, 0, ',', '.'),
            ];

            $counter++;
        }

        return $items;
    }

    private function generateKas($tokoId, int $month, int $year)
    {
        $kasList = Kas::query()
            ->when(
                $tokoId !== null && $tokoId !== 'all',
                fn($q) => $q->where('toko_id', $tokoId)
            )
            ->orderBy('jenis_barang_id')
            ->get();

        $jenisBarangMap = JenisBarang::pluck('nama_jenis_barang', 'id')->toArray();

        $jenisBarangMap[0] = "Dompet Digital";

        $allJenisBarangIds = array_keys($jenisBarangMap);

        $kasBesarItems = [];
        $kasKecilItems = [];

        $totalKasBesar = 0;
        $totalKasKecil = 0;

        $counter = 1;

        foreach ($allJenisBarangIds as $jenisBarangId) {

            $jenisNama = $jenisBarangMap[$jenisBarangId];
            $kasGroup = $kasList->where('jenis_barang_id', $jenisBarangId);

            $kasBesar = $kasGroup->where('tipe_kas', 'besar')->first();
            $saldoBesar = 0;

            if ($kasBesar) {
                $saldoBesar = $this->getSaldoAkhirKas($kasBesar->id, $month, $year);
            }

            $kasBesarItems[] = [
                'kode'   => 'I.1.' . $counter,
                'nama'   => 'Kas Besar - ' . $jenisNama,
                'nilai'  => (float) $saldoBesar,
                'format' => 'Rp ' . number_format($saldoBesar, 0, ',', '.'),
                'sub'    => 'I.1',
            ];
            $totalKasBesar += $saldoBesar;

            $kasKecil = $kasGroup->where('tipe_kas', 'kecil')->first();
            $saldoKecil = 0;

            if ($kasKecil) {
                $saldoKecil = $this->getSaldoAkhirKas($kasKecil->id, $month, $year);
            }

            $kasKecilItems[] = [
                'kode'   => 'I.2.' . $counter,
                'nama'   => 'Kas Kecil - ' . $jenisNama,
                'nilai'  => (float) $saldoKecil,
                'format' => 'Rp ' . number_format($saldoKecil, 0, ',', '.'),
                'sub'    => 'I.2',
            ];
            $totalKasKecil += $saldoKecil;

            $counter++;
        }

        return [
            'kasBesar' => [
                'parent' => [
                    'kode'   => 'I.1',
                    'nama'   => 'Kas Besar',
                    'nilai'  => (float) $totalKasBesar,
                    'format' => 'Rp ' . number_format($totalKasBesar, 0, ',', '.'),
                ],
                'items' => $kasBesarItems,
            ],
            'kasKecil' => [
                'parent' => [
                    'kode'   => 'I.2',
                    'nama'   => 'Kas Kecil',
                    'nilai'  => (float) $totalKasKecil,
                    'format' => 'Rp ' . number_format($totalKasKecil, 0, ',', '.'),
                ],
                'items' => $kasKecilItems,
            ],
        ];
    }

    private function getSaldoAkhirKas($kasId, int $month, int $year): float
    {
        // coba bulan berjalan
        $current = KasSaldoHistory::where('kas_id', $kasId)
            ->where('tahun', $year)
            ->where('bulan', $month)
            ->orderByDesc('id')
            ->first();

        if ($current) {
            return (float) $current->saldo_akhir;
        }

        // hitung bulan sebelumnya
        $prevMonth = $month - 1;
        $prevYear  = $year;

        if ($prevMonth === 0) {
            $prevMonth = 12;
            $prevYear--;
        }

        $prev = KasSaldoHistory::where('kas_id', $kasId)
            ->where('tahun', $prevYear)
            ->where('bulan', $prevMonth)
            ->orderByDesc('id')
            ->first();

        return $prev ? (float) $prev->saldo_akhir : 0;
    }

    private function composeNeracaStructure(
        $pengeluaranAset,
        array $hutangItems,
        array $ekuitasItems,
        array $returData,
        array $piutangData,
        float $penyesuaianNeraca,
        array $kasData,
        int $month,
        int $year,
        array $pengirimanData,
        $tokoId
    ): array {

        /* ===============================
     * DATA DASAR
     * =============================== */
        $asetPeralatanBesar = [
            "kode"   => "II.1",
            "nama"   => "Peralatan Besar",
            "nilai"  => $pengeluaranAset['besar'],
            "format" => "Rp " . number_format($pengeluaranAset['besar'], 0, ',', '.'),
        ];

        $asetPeralatanKecil = [
            "kode"   => "II.2",
            "nama"   => "Peralatan Kecil",
            "nilai"  => $pengeluaranAset['kecil'],
            "format" => "Rp " . number_format($pengeluaranAset['kecil'], 0, ',', '.'),
        ];
        /* ===============================
     * STOK PER JENIS
     * =============================== */
        $stokPerJenisItems = [];
        $totalStokJenis = 0;
        $totalQtyJenis  = 0;

        $stokPerJenis = $this->stokRepo->getStokPerJenis($tokoId, $month, $year);

        foreach ($stokPerJenis as $index => $item) {
            $qty   = (float) $item['total_qty'];
            $nilai = (float) $item['total_harga'];

            $stokPerJenisItems[] = [
                "kode"   => "I.4." . ($index + 1),
                "nama"   => $item['nama_jenis_barang'] . " (" . number_format($qty, 0, ',', '.') . ")",
                "nilai"  => $nilai,
                "format" => 'Rp ' . number_format($nilai, 0, ',', '.'),
                "sub"    => "I.4",
            ];

            $totalQtyJenis  += $qty;
            $totalStokJenis += $nilai;
        }

        $stokBarangParent = [
            "kode"   => "I.4",
            "nama"   => "Stok Barang Jualan (" . number_format($totalQtyJenis, 0, ',', '.') . ")",
            "nilai"  => $totalStokJenis,
            "format" => 'Rp ' . number_format($totalStokJenis, 0, ',', '.'),
        ];

        /* ===============================
     * PIUTANG
     * =============================== */

        $piutangKasbon = 0;

        // total piutang jangka pendek + panjang
        $piutangJangkaTotal = array_sum(array_column($piutangData, 'nilai'));

        // total piutang pengiriman

        // TOTAL PIUTANG (INI YANG TADI SALAH)
        $piutangTotal =
            (float) $piutangKasbon +
            (float) $piutangJangkaTotal;

        $piutangParent = [
            "kode"   => "I.3",
            "nama"   => "Piutang",
            "nilai"  => $piutangTotal,
            "format" => "Rp " . number_format($piutangTotal, 0, ',', '.'),
        ];

        $piutangKasbonItem = [
            "kode"   => "I.3.3",
            "nama"   => "Kasbon Member",
            "nilai"  => $piutangKasbon,
            "format" => "Rp " . number_format($piutangKasbon, 0, ',', '.'),
            "sub"    => "I.3",
        ];

        /* ===============================
     * SUSUN ITEM PIUTANG (URUTAN FIX)
     * =============================== */
        $piutangItems = [];
        $idx = 1;

        // 1️⃣ Piutang Jangka Pendek & Panjang (lebih dulu)
        foreach ($piutangData as $item) {
            $piutangItems[] = [
                "kode"   => "I.3." . $idx++,
                "nama"   => $item['nama'],
                "nilai"  => $item['nilai'],
                "format" => $item['format'],
                "sub"    => "I.3",
            ];
        }

        // 2️⃣ Kasbon (PALING AKHIR)
        $piutangKasbonItem['kode'] = "I.3." . $idx++;
        $piutangItems[] = $piutangKasbonItem;


        /* ===============================
     * DATA TAMBAHAN
     * =============================== */
        $hppDompetSaldo = $this->dompetSaldoService->sumHPP($month, $year);
        $sisaDompetSaldo = $this->dompetSaldoService->sumSisaSaldo($month, $year);
        $hppDompetSaldoNilai = (float) ($hppDompetSaldo['saldo'] ?? 0);

        $returTotal = (float) ($returData['total_retur'] ?? 0);
        $pengirimanTotal = (float) ($pengirimanData['total_harga'] ?? 0);
        $penyesuaian = (float) $penyesuaianNeraca;

        $totalKasBesarParent = (float) ($kasData['kasBesar']['parent']['nilai'] ?? 0);
        $totalKasKecilParent = (float) ($kasData['kasKecil']['parent']['nilai'] ?? 0);

        /* ===============================
     * TOTAL ASET
     * =============================== */
        $asetLancarTotal =
            $totalKasBesarParent +
            $totalKasKecilParent +
            $piutangTotal +
            $totalStokJenis +
            $hppDompetSaldoNilai +
            $returTotal +
            $pengirimanTotal +
            $penyesuaian;

        $asetTetapTotal = (float) $asetPeralatanBesar['nilai'] + (float) $asetPeralatanKecil['nilai'];
        $totalAktiva = $asetLancarTotal + $asetTetapTotal;

        /* ===============================
     * PASIVA
     * =============================== */
        $totalHutang  = array_sum(array_column($hutangItems, 'nilai'));
        $totalEkuitas = array_sum(array_column($ekuitasItems, 'nilai'));
        $totalPasiva  = $totalHutang + $totalEkuitas;

        /* ===============================
     * ASET LANCAR
     * =============================== */
        $asetLancarItems = array_merge(
            [$kasData['kasBesar']['parent']],
            $kasData['kasBesar']['items'],
            [$kasData['kasKecil']['parent']],
            $kasData['kasKecil']['items'],
            [
                $piutangParent,
                ...$piutangItems,
                $stokBarangParent,
                ...$stokPerJenisItems,
                [
                    "kode"   => "I.5",
                    "nama"   => "Stok Dompet Digital (Sisa saldo: {$sisaDompetSaldo['format']})",
                    "nilai"  => $hppDompetSaldoNilai,
                    "format" => $hppDompetSaldo['format'],
                ],
                [
                    "kode"   => "I.6",
                    "nama"   => "Stok Barang Retur ({$returData['stock_retur']})",
                    "nilai"  => $returTotal,
                    "format" => 'Rp ' . number_format($returTotal, 0, ',', '.'),
                ],
                [
                    "kode"   => "I.7",
                    "nama"   => "Stok Pengiriman Barang ({$pengirimanData['total_qty']})",
                    "nilai"  => $pengirimanTotal,
                    "format" => 'Rp ' . number_format($pengirimanTotal, 0, ',', '.'),
                ],
                [
                    "kode"   => "I.8",
                    "nama"   => "Penyesuaian",
                    "nilai"  => $penyesuaian,
                    "format" => 'Rp ' . number_format($penyesuaian, 0, ',', '.'),
                ],
            ]
        );

        $asetTetapItems = array_merge([$asetPeralatanBesar, $asetPeralatanKecil]);
        /* ===============================
     * RETURN
     * =============================== */
        return [
            [
                'kategori' => 'AKTIVA',
                'total'    => $totalAktiva,
                'format'   => 'Rp ' . number_format($totalAktiva, 0, ',', '.'),
                'subkategori' => [
                    [
                        'judul'  => 'I. ASET LANCAR',
                        'total'  => $asetLancarTotal,
                        'item'   => $asetLancarItems,
                        'format' => 'Rp ' . number_format($asetLancarTotal, 0, ',', '.'),
                    ],
                    [
                        'judul'  => 'II. ASET TETAP',
                        'total'  => $asetTetapTotal,
                        'item'   => $asetTetapItems,
                        'format' => 'Rp ' . number_format($asetTetapTotal, 0, ',', '.'),
                    ],
                ],
            ],
            [
                'kategori' => 'PASIVA',
                'total'    => $totalPasiva,
                'format'   => 'Rp ' . number_format($totalPasiva, 0, ',', '.'),
                'subkategori' => [
                    [
                        'judul'  => 'III. HUTANG',
                        'total'  => $totalHutang,
                        'item'   => $hutangItems,
                        'format' => 'Rp ' . number_format($totalHutang, 0, ',', '.'),
                    ],
                    [
                        'judul'  => 'IV. EKUITAS',
                        'total'  => $totalEkuitas,
                        'item'   => $ekuitasItems,
                        'format' => 'Rp ' . number_format($totalEkuitas, 0, ',', '.'),
                    ],
                ],
            ],
        ];
    }
}
