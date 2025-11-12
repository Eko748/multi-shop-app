<?php

namespace App\Services;

use App\Models\JenisBarang;
use App\Repositories\{
    HutangRepository,
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
        protected PengeluaranRepository $pengeluaranRepo,
        protected PemasukanRepository $pemasukanRepo,
        protected ReturRepository $returRepo,
        protected StokRepository $stokRepo,
        protected NeracaPenyesuaianRepository $neracaRepo,
        protected StockProblemRepository $stokProblemRepo,
    ) {}

    public function generateNeraca(int $month, int $year, ?int $tokoId)
    {
        // Ambil data dari masing-masing repository
        $pengeluaran = $this->pengeluaranRepo->getPengeluaranByMonthYear($month, $year);
        $hutangItems = $this->hutangRepo->getActiveHutang();
        $ekuitasItems = $this->generateEkuitas($month, $year);
        $returData = $this->returRepo->getReturData($month, $year);
        $stokData = $this->stokRepo->getStokData($month, $year);
        $penyesuaianNeraca = $this->neracaRepo->getTotalPenyesuaian();
        $stokProblem = $this->stokProblemRepo->getStockProblem();

        // Kas besar/kecil via service
        $kasData = $this->generateKas($tokoId, $month, $year);

        // Satukan hasil akhir
        return [
            'data' => $this->composeNeracaStructure(
                $pengeluaran,
                $hutangItems,
                $ekuitasItems,
                $returData,
                $stokData,
                $penyesuaianNeraca,
                $kasData,
                $month,
                $year
            ),
            'note' => $stokProblem,
        ];
    }

    private function generateEkuitas(int $month, int $year)
    {
        Carbon::setLocale('id');
        $labaRugiPerBulan = $this->labaRugiService->hitungLabaRugiRange($month, $year);

        $items = [];
        for ($i = 1; $i <= $month; $i++) {
            $periode = Carbon::create($year, $i, 1);
            $namaPeriode = $periode->translatedFormat('F Y');
            $nilai = (float) ($labaRugiPerBulan[$i] ?? 0);
            $kode = "IV." . ($i + 1);

            $items[] = [
                "kode"   => $kode,
                "nama"   => $i == $month
                    ? "Laba (Rugi) Berjalan Periode $namaPeriode"
                    : "Laba (Rugi) Ditahan Periode $namaPeriode",
                "nilai"  => $nilai,
                "format" => 'Rp ' . number_format($nilai, 0, ',', '.'),
            ];
        }

        // Tambahkan modal di awal
        $modal = (float) $this->pemasukanRepo->getModal($month, $year);
        array_unshift($items, [
            "kode"   => "IV.1",
            "nama"   => "Modal",
            "nilai"  => $modal,
            "format" => 'Rp ' . number_format($modal, 0, ',', '.'),
        ]);

        return $items;
    }

    private function generateKas(?int $tokoId, int $month, int $year)
    {
        // Ambil semua jenis barang (fisik + dompet digital terakhir)
        $jenisBarangList = JenisBarang::pluck('id')->toArray();
        $jenisBarangList[] = 0; // pastikan jenis 0 di akhir

        $kasBesarItems = [];
        $kasKecilItems = [];

        $totalKasBesar = 0;
        $totalKasKecil = 0;

        $counter = 1;

        foreach ($jenisBarangList as $jenis) {
            // === Kas Besar ===
            $kasBesar = $this->kasService->getKasNeracaJenisBarang(0, $jenis, $month, $year);
            $kasBesarItems[] = [
                'kode'   => 'I.1.' . $counter,
                'nama'   => 'Kas Besar - ' . $kasBesar['jenis_barang'],
                'nilai'  => (float) $kasBesar['total'],
                'format' => $kasBesar['format'],
                'sub'    => 'I.1',
            ];
            $totalKasBesar += (float) $kasBesar['total'];

            // === Kas Kecil ===
            $kasKecil = $this->kasService->getKasNeracaJenisBarang(1, $jenis, $month, $year);
            $kasKecilItems[] = [
                'kode'   => 'I.2.' . $counter,
                'nama'   => 'Kas Kecil - ' . $kasKecil['jenis_barang'],
                'nilai'  => (float) $kasKecil['total'],
                'format' => $kasKecil['format'],
                'sub'    => 'I.2',
            ];
            $totalKasKecil += (float) $kasKecil['total'];

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

    private function composeNeracaStructure(
        $pengeluaran,
        array $hutangItems,
        array $ekuitasItems,
        array $returData,
        $stokData,
        float $penyesuaianNeraca,
        array $kasData,
        int $month,
        int $year
    ): array {
        // ===============================
        // ======== DATA DASAR ===========
        // ===============================
        $asetPeralatanBesar = $pengeluaran['Asset Peralatan Besar'] ?? 0;
        $asetPeralatanKecil = $pengeluaran['Asset Peralatan Kecil'] ?? 0;

        // ===============================
        // ===== STOK PER JENIS =========
        // ===============================
        $stokPerJenisItems = [];
        $totalStokJenis = 0;
        $totalQtyJenis = 0;

        $stokPerJenis = $this->stokRepo->getStokPerJenis($month, $year);
        foreach ($stokPerJenis as $idx => $item) {
            $stokPerJenisItems[] = [
                "kode"   => "I.4." . ($idx + 1),
                "nama"   => $item->nama_jenis_barang . " (" . number_format($item->total_qty, 0, ',', '.') . ")",
                "nilai"  => (float) $item->total_harga,
                "format" => 'Rp ' . number_format((float) $item->total_harga, 0, ',', '.'),
                "sub"    => "I.4",
            ];
            $totalStokJenis += (float) $item->total_harga;
            $totalQtyJenis  += (float) $item->total_qty;
        }

        $stokBarangParent = [
            "kode"   => "I.4",
            "nama"   => "Stok Barang Jualan (" . number_format($totalQtyJenis, 0, ',', '.') . ")",
            "nilai"  => $totalStokJenis,
            "format" => 'Rp ' . number_format($totalStokJenis, 0, ',', '.'),
        ];

        // ===============================
        // ======= DATA TAMBAHAN =========
        // ===============================
        $totalKasBesarParent = (float) ($kasData['kasBesar']['parent']['nilai'] ?? 0);
        $totalKasKecilParent = (float) ($kasData['kasKecil']['parent']['nilai'] ?? 0);
        $piutangTotal        = (float) ($this->kasService->getPiutang()['total'] ?? 0);

        $hppDompetSaldo = $this->dompetSaldoService->sumHPP($month, $year);
        $sisaDompetSaldo = $this->dompetSaldoService->sumSisaSaldo($month, $year);
        $hppDompetSaldoNilai = (float) ($hppDompetSaldo['saldo'] ?? 0);

        $returTotal = (float) ($returData['total_retur'] ?? 0);
        $penyesuaian = (float) $penyesuaianNeraca;

        // ===============================
        // ====== PERHITUNGAN ASET =======
        // ===============================
        $asetLancarTotal =
            $totalKasBesarParent +
            $totalKasKecilParent +
            $piutangTotal +
            $totalStokJenis +       // hanya dari stok per jenis
            $hppDompetSaldoNilai +
            $returTotal +
            $penyesuaian;

        $asetTetapTotal =
            (float) $asetPeralatanBesar +
            (float) $asetPeralatanKecil;

        $totalAktiva = $asetLancarTotal + $asetTetapTotal;

        // ===============================
        // ======= PASIVA (HUTANG + EKUITAS)
        // ===============================
        $totalHutang  = array_sum(array_column($hutangItems, 'nilai'));
        $totalEkuitas = array_sum(array_column($ekuitasItems, 'nilai'));
        $totalPasiva  = $totalHutang + $totalEkuitas;

        // ===============================
        // ===== SUSUN ASET LANCAR =======
        // ===============================
        $asetLancarItems = array_merge(
            [$kasData['kasBesar']['parent']],
            $kasData['kasBesar']['items'],
            [$kasData['kasKecil']['parent']],
            $kasData['kasKecil']['items'],
            [
                [
                    "kode"   => "I.3",
                    "nama"   => "Piutang (Kasbon)",
                    "nilai"  => $piutangTotal,
                    "format" => 'Rp ' . number_format($piutangTotal, 0, ',', '.'),
                ],
                $stokBarangParent,
                ...$stokPerJenisItems,
                [
                    "kode"   => "I.5",
                    "nama"   => "Pembelian Saldo Digital (Sisa saldo: {$sisaDompetSaldo['format']})",
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
                    "nama"   => "Penyesuaian",
                    "nilai"  => $penyesuaian,
                    "format" => 'Rp ' . number_format($penyesuaian, 0, ',', '.'),
                ],
            ]
        );

        // ===============================
        // ========= RETURN DATA ==========
        // ===============================
        return [
            [
                'kategori'    => 'AKTIVA',
                'total'       => $totalAktiva,
                'format'      => 'Rp ' . number_format($totalAktiva, 0, ',', '.'),
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
                        'item'   => [
                            [
                                "kode"   => "II.1",
                                "nama"   => "Peralatan Besar",
                                "nilai"  => (float) $asetPeralatanBesar,
                                "format" => 'Rp ' . number_format((float) $asetPeralatanBesar, 0, ',', '.'),
                            ],
                            [
                                "kode"   => "II.2",
                                "nama"   => "Peralatan Kecil",
                                "nilai"  => (float) $asetPeralatanKecil,
                                "format" => 'Rp ' . number_format((float) $asetPeralatanKecil, 0, ',', '.'),
                            ],
                        ],
                        'format' => 'Rp ' . number_format($asetTetapTotal, 0, ',', '.'),
                    ],
                ],
            ],
            [
                'kategori'    => 'PASIVA',
                'total'       => $totalPasiva,
                'format'      => 'Rp ' . number_format($totalPasiva, 0, ',', '.'),
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
