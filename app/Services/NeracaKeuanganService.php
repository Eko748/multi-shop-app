<?php

namespace App\Services;

use App\Helpers\RupiahGenerate;
use App\Models\JenisBarang;
use App\Models\Kas;
use App\Models\KasSaldoHistory;
use App\Repositories\Distribusi\PengirimanBarangRepo;
use App\Repositories\HutangRepository;
use App\Repositories\NeracaPenyesuaianRepository;
use App\Repositories\PemasukanRepository;
use App\Repositories\PengeluaranRepository;
use App\Repositories\PiutangRepository;
use App\Repositories\ReturRepository;
use App\Repositories\StockProblemRepository;
use App\Repositories\StokRepository;
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
        $returData = $this->returRepo->getReturData($month, $year, $tokoId);
        $piutangData = $this->piutangRepo->getActivePiutang($month, $year, $tokoId);
        $penyesuaianNeraca = $this->neracaRepo->getTotalPenyesuaian();
        $stokProblem = $this->stokProblemRepo->getStockProblem($month, $year, $tokoId);
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
        $modal = (int) $this->pemasukanRepo->getModal($month, $year, $tokoId);

        $items[] = [
            'kode' => 'IV.1',
            'nama' => 'Modal',
            'nilai' => $modal,
            'format' => RupiahGenerate::build($modal),
        ];

        // ==========================
        // IV.2 LABA DITAHAN TAHUN SEBELUMNYA
        // ==========================
        $labaTahunSebelumnya =
            $this->labaRugiService->hitungLabaRugiTahunSebelumnya($year, $tokoId);

        $items[] = [
            'kode' => 'IV.2',
            'nama' => 'Laba (Rugi) Tahun Sebelumnya',
            'nilai' => $labaTahunSebelumnya,
            'format' => RupiahGenerate::build($labaTahunSebelumnya),
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
            $nilai = (int) ($labaRugiPerBulan[$i] ?? 0);

            $items[] = [
                'kode' => "IV.$counter",
                'nama' => $i == $month
                    ? "Laba (Rugi) Berjalan Periode $namaPeriode"
                    : "Laba (Rugi) Ditahan Periode $namaPeriode",
                'nilai' => $nilai,
                'format' => RupiahGenerate::build($nilai),
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
                fn ($q) => $q->where('toko_id', $tokoId)
            )
            ->orderBy('jenis_barang_id')
            ->get();

        $jenisBarangMap = JenisBarang::pluck('nama_jenis_barang', 'id')->toArray();
        $jenisBarangMap[0] = 'Dompet Digital';

        $allJenisBarangIds = array_keys($jenisBarangMap);

        $kasBesarItems = [];
        $kasKecilItems = [];

        $totalKasBesar = 0;
        $totalKasKecil = 0;

        // Pisahkan counter agar penomoran kode (I.1.x) tetap urut dan rapi
        $counterBesar = 1;
        $counterKecil = 1;

        foreach ($allJenisBarangIds as $jenisBarangId) {
            $jenisNama = $jenisBarangMap[$jenisBarangId];
            $kasGroup = $kasList->where('jenis_barang_id', $jenisBarangId);

            // --- PROSES KAS BESAR ---
            $kasBesar = $kasGroup->where('tipe_kas', 'besar')->first();
            $saldoBesar = 0;

            if ($kasBesar) {
                $saldoBesar = $this->getSaldoAkhirKas($kasBesar->id, $month, $year);
            }

            // HANYA MASUKKAN JIKA SALDO LEBIH DARI 0
            if ($saldoBesar > 0) {
                $kasBesarItems[] = [
                    'kode' => 'I.1.'.$counterBesar,
                    'nama' => 'Kas Besar - '.$jenisNama,
                    'nilai' => (int) $saldoBesar,
                    'format' => RupiahGenerate::build($saldoBesar),
                    'sub' => 'I.1',
                ];
                $totalKasBesar += $saldoBesar;
                $counterBesar++; // Counter maju hanya jika data ditambahkan
            }

            // --- PROSES KAS KECIL ---
            $kasKecil = $kasGroup->where('tipe_kas', 'kecil')->first();
            $saldoKecil = 0;

            if ($kasKecil) {
                $saldoKecil = $this->getSaldoAkhirKas($kasKecil->id, $month, $year);
            }

            // HANYA MASUKKAN JIKA SALDO LEBIH DARI 0
            if ($saldoKecil > 0) {
                $kasKecilItems[] = [
                    'kode' => 'I.2.'.$counterKecil,
                    'nama' => 'Kas Kecil - '.$jenisNama,
                    'nilai' => (int) $saldoKecil,
                    'format' => RupiahGenerate::build($saldoKecil),
                    'sub' => 'I.2',
                ];
                $totalKasKecil += $saldoKecil;
                $counterKecil++; // Counter maju hanya jika data ditambahkan
            }
        }

        return [
            'kasBesar' => [
                'parent' => [
                    'kode' => 'I.1',
                    'nama' => 'Kas Besar',
                    'nilai' => (int) $totalKasBesar,
                    'format' => RupiahGenerate::build($totalKasBesar),
                ],
                'items' => $kasBesarItems,
            ],
            'kasKecil' => [
                'parent' => [
                    'kode' => 'I.2',
                    'nama' => 'Kas Kecil',
                    'nilai' => (int) $totalKasKecil,
                    'format' => RupiahGenerate::build($totalKasKecil),
                ],
                'items' => $kasKecilItems,
            ],
        ];
    }

    private function getSaldoAkhirKas($kasId, int $month, int $year): int
    {
        // coba bulan berjalan
        $current = KasSaldoHistory::where('kas_id', $kasId)
            ->where('tahun', $year)
            ->where('bulan', $month)
            ->orderByDesc('id')
            ->first();

        if ($current) {
            return (int) $current->saldo_akhir;
        }

        // hitung bulan sebelumnya
        $prevMonth = $month - 1;
        $prevYear = $year;

        if ($prevMonth === 0) {
            $prevMonth = 12;
            $prevYear--;
        }

        $prev = KasSaldoHistory::where('kas_id', $kasId)
            ->where('tahun', $prevYear)
            ->where('bulan', $prevMonth)
            ->orderByDesc('id')
            ->first();

        return $prev ? (int) $prev->saldo_akhir : 0;
    }

    private function composeNeracaStructure(
        $pengeluaranAset,
        array $hutangItems,
        array $ekuitasItems,
        array $returData,
        array $piutangData,
        int $penyesuaianNeraca,
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
            'kode' => 'II.1',
            'nama' => 'Peralatan Besar',
            'nilai' => $pengeluaranAset['besar'],
            'format' => RupiahGenerate::build($pengeluaranAset['besar']),
        ];

        $asetPeralatanKecil = [
            'kode' => 'II.2',
            'nama' => 'Peralatan Kecil',
            'nilai' => $pengeluaranAset['kecil'],
            'format' => RupiahGenerate::build($pengeluaranAset['kecil']),
        ];

        $stokPerJenisItems = [];
        $totalStokJenis = 0;
        $totalQtyJenis = 0;

        $stokPerJenis = $this->stokRepo->getStokPerJenis($tokoId, $month, $year);

        foreach ($stokPerJenis as $index => $item) {
            $qty = (int) $item['total_qty'];
            $nilai = (int) $item['total_harga'];

            $stokPerJenisItems[] = [
                'kode' => 'I.4.'.($index + 1),
                'nama' => $item['nama_jenis_barang'].' ('.number_format($qty, 0, ',', '.').')',
                'nilai' => $nilai,
                'format' => RupiahGenerate::build($nilai),
                'sub' => 'I.4',
            ];

            $totalQtyJenis += $qty;
            $totalStokJenis += $nilai;
        }

        $stokBarangParent = [
            'kode' => 'I.4',
            'nama' => 'Stok Barang Jualan ('.number_format($totalQtyJenis, 0, ',', '.').')',
            'nilai' => $totalStokJenis,
            'format' => RupiahGenerate::build($totalStokJenis),
        ];
        $piutangKasbon = 0; // Nilai kasbon Anda

        // Hitung total piutang jangka dari array (angka 0 tidak memengaruhi hasil)
        $piutangJangkaTotal = array_sum(array_column($piutangData, 'nilai'));

        $piutangTotal = (int) $piutangKasbon + (int) $piutangJangkaTotal;

        // 1. Parent Piutang (Tetap dibuat sebagai penampung utama)
        $piutangParent = [
            'kode' => 'I.3',
            'nama' => 'Piutang',
            'nilai' => $piutangTotal,
            'format' => RupiahGenerate::build($piutangTotal),
        ];

        $piutangItems = [];
        $idx = 1;

        // 2. Looping data piutang (Hanya ambil yang nilainya > 0)
        foreach ($piutangData as $item) {
            if ((int) $item['nilai'] > 0) {
                $piutangItems[] = [
                    'kode' => 'I.3.'.$idx++, // $idx hanya bertambah jika data dimasukkan
                    'nama' => $item['nama'],
                    'nilai' => (int) $item['nilai'],
                    'format' => $item['format'],
                    'sub' => 'I.3',
                ];
            }
        }

        // 3. Proses Kasbon Member (Hanya masukkan ke array jika nilainya > 0)
        if ((int) $piutangKasbon > 0) {
            $piutangItems[] = [
                'kode' => 'I.3.'.$idx++, // Melanjutkan nomor urut terakhir yang valid
                'nama' => 'Kasbon Member',
                'nilai' => (int) $piutangKasbon,
                'format' => RupiahGenerate::build($piutangKasbon),
                'sub' => 'I.3',
            ];
        }

        $sisaDompetSaldo = $this->dompetSaldoService->sumSisaSaldo($month, $year, $tokoId);
        $hppDompetSaldoNilai = (int) ($sisaDompetSaldo ?? 0);

        $returMemberTotal = (int) ($returData['retur_member'] ?? 0);
        $returSuplierTotal = (int) ($returData['retur_suplier'] ?? 0);
        $pengirimanTotal = (int) ($pengirimanData['total_harga'] ?? 0);
        $penyesuaian = (int) $penyesuaianNeraca;

        $totalKasBesarParent = (int) ($kasData['kasBesar']['parent']['nilai'] ?? 0);
        $totalKasKecilParent = (int) ($kasData['kasKecil']['parent']['nilai'] ?? 0);

        $asetLancarTotal =
            $totalKasBesarParent +
            $totalKasKecilParent +
            $piutangTotal +
            $totalStokJenis +
            $hppDompetSaldoNilai +
            $returMemberTotal +
            $returSuplierTotal +
            $pengirimanTotal +
            $penyesuaian;

        $asetTetapTotal = (int) $asetPeralatanBesar['nilai'] + (int) $asetPeralatanKecil['nilai'];
        $totalAktiva = $asetLancarTotal + $asetTetapTotal;

        $totalHutang = array_sum(array_column($hutangItems, 'nilai'));
        $totalEkuitas = array_sum(array_column($ekuitasItems, 'nilai'));
        $totalPasiva = $totalHutang + $totalEkuitas;

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
                    'kode' => 'I.5',
                    'nama' => 'Stok Saldo Dompet Digital',
                    'nilai' => $sisaDompetSaldo,
                    'format' => RupiahGenerate::build($sisaDompetSaldo),
                ],
                [
                    'kode' => 'I.6',
                    'nama' => "Stok Barang Retur ({$returData['stock_retur_member']})",
                    'nilai' => $returMemberTotal,
                    'format' => RupiahGenerate::build($returMemberTotal),
                ],
                [
                    'kode' => 'I.7',
                    'nama' => "Stok Pengiriman Barang Retur ({$returData['stock_retur_suplier']})",
                    'nilai' => $returSuplierTotal,
                    'format' => RupiahGenerate::build($returSuplierTotal),
                ],
                [
                    'kode' => 'I.8',
                    'nama' => "Stok Pengiriman Barang ({$pengirimanData['total_qty']})",
                    'nilai' => $pengirimanTotal,
                    'format' => RupiahGenerate::build($pengirimanTotal),
                ],
                [
                    'kode' => 'I.9',
                    'nama' => 'Penyesuaian',
                    'nilai' => $penyesuaian,
                    'format' => RupiahGenerate::build($penyesuaian),
                ],
            ]
        );

        $asetTetapItems = array_merge([$asetPeralatanBesar, $asetPeralatanKecil]);

        return [
            [
                'kategori' => 'AKTIVA',
                'total' => $totalAktiva,
                'format' => RupiahGenerate::build($totalAktiva),
                'subkategori' => [
                    [
                        'judul' => 'I. ASET LANCAR',
                        'total' => $asetLancarTotal,
                        'item' => $asetLancarItems,
                        'format' => RupiahGenerate::build($asetLancarTotal),
                    ],
                    [
                        'judul' => 'II. ASET TETAP',
                        'total' => $asetTetapTotal,
                        'item' => $asetTetapItems,
                        'format' => RupiahGenerate::build($asetTetapTotal),
                    ],
                ],
            ],
            [
                'kategori' => 'PASIVA',
                'total' => $totalPasiva,
                'format' => RupiahGenerate::build($totalPasiva),
                'subkategori' => [
                    [
                        'judul' => 'III. HUTANG',
                        'total' => $totalHutang,
                        'item' => $hutangItems,
                        'format' => RupiahGenerate::build($totalHutang),
                    ],
                    [
                        'judul' => 'IV. EKUITAS',
                        'total' => $totalEkuitas,
                        'item' => $ekuitasItems,
                        'format' => RupiahGenerate::build($totalEkuitas),
                    ],
                ],
            ],
        ];
    }
}
