<?php

namespace App\Http\Controllers\LaporanKeuangan;

use App\Http\Controllers\Controller;
use App\Models\DetailPembelianBarang;
use App\Models\DetailRetur;
use App\Models\Hutang;
use App\Models\NeracaPenyesuaian;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\ReturMemberDetail;
use App\Models\ReturSupplier;
use App\Models\ReturSupplierDetail;
use App\Models\StockBarangBermasalah;
use App\Models\Toko;
use App\Services\ArusKasService;
use App\Services\DompetSaldoService;
use App\Services\KasService;
use App\Services\LabaRugiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NeracaController extends Controller
{
    private array $menu = [];
    protected $labaRugiService;
    protected $kasService;
    protected $dompetSaldoService;

    public function __construct(LabaRugiService $labaRugiService, KasService $kasService, DompetSaldoService $dompetSaldoService)
    {
        $this->menu;
        $this->title = [
            'Neraca',
        ];

        $this->labaRugiService = $labaRugiService;
        $this->kasService = $kasService;
        $this->dompetSaldoService = $dompetSaldoService;
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[4]];

        return view('laporankeuangan.neraca.index', compact('menu'));
    }

    public function getNeraca(Request $request)
    {
        try {
            $month = $request->has('month') ? (int) $request->month : (int) now()->month;
            $year  = $request->has('year') ? (int) $request->year  : (int) now()->year;

            // =========================
            // ====== DATA DASAR ======
            // =========================
            $pengeluaran = Pengeluaran::select('is_asset', DB::raw('SUM(nilai) as total'))
                ->groupBy('is_asset')
                ->pluck('total', 'is_asset');

            $asetPeralatanBesar = $pengeluaran['Asset Peralatan Besar'] ?? 0;
            $asetPeralatanKecil = $pengeluaran['Asset Peralatan Kecil'] ?? 0;

            $piutang = $this->kasService->getPiutang();

            $hutang = Hutang::where('status', '1')
                ->withSum('detailhutang', 'nilai')
                ->get();

            $hutangItems = $hutang->map(function ($item, $index) {
                $sisaNilai = (float) $item->nilai - (float) $item->detailhutang_sum_nilai;
                if ($sisaNilai <= 0) return null;

                $jenis = ((int) $item->jangka === 1)
                    ? 'Hutang Jangka Pendek'
                    : 'Hutang Jangka Panjang';

                return [
                    "kode"   => "III." . ($index + 1),
                    "nama"   => $jenis . ' - ' . $item->keterangan,
                    "nilai"  => $sisaNilai,
                    "format" => number_format($sisaNilai, 0, ',', '.'),
                ];
            })->filter()->values()->toArray();

            // =========================
            // ====== EKUITAS =========
            // =========================
            $ekuitasItems = [];
            Carbon::setLocale('id');
            $labaRugiPerBulan = $this->labaRugiService->hitungLabaRugiRange($month, $year);

            for ($i = 1; $i <= $month; $i++) {
                $periode     = Carbon::create($year, $i, 1);
                $namaPeriode = $periode->translatedFormat('F Y');
                $nilaiLabaRugi = (float) ($labaRugiPerBulan[$i] ?? 0);
                $kode = "IV." . ($i + 1);

                $ekuitasItems[] = [
                    "kode"   => $kode,
                    "nama"   => $i == $month
                        ? "Laba (Rugi) Berjalan Periode $namaPeriode"
                        : "Laba (Rugi) Ditahan Periode $namaPeriode",
                    "nilai"  => $nilaiLabaRugi,
                    "format" => 'Rp ' . number_format($nilaiLabaRugi, 0, ',', '.'),
                ];
            }

            $modal = (float) Pemasukan::whereIn('id_jenis_pemasukan', [1, 2])->sum('nilai');
            array_unshift($ekuitasItems, [
                "kode"   => "IV.1",
                "nama"   => "Modal",
                "nilai"  => $modal,
                "format" => 'Rp ' . number_format($modal, 0, ',', '.'),
            ]);

            $penyesuaianNeraca = (float) (NeracaPenyesuaian::sum('nilai') ?? 0);

            // =========================
            // ====== RETUR ============
            // =========================
            $totalReturMember = ReturMemberDetail::selectRaw('SUM((qty_request - IFNULL(qty_ke_supplier, 0)) * hpp) as total')
                ->value('total');

            $totalReturSuplier = ReturSupplier::where('status', 'proses')
                ->where('tipe_retur', 'pembelian')
                ->sum('total_hpp');

            $penjualanReture = $totalReturMember + $totalReturSuplier;

            $stockReturMember = ReturMemberDetail::selectRaw('SUM(qty_request - IFNULL(qty_ke_supplier, 0)) as total')
                ->value('total');

            $stockReturSuplier = ReturSupplier::where('status', 'proses')
                ->where('tipe_retur', 'pembelian')
                ->sum('qty');

            $stockRetur = $stockReturSuplier + $stockReturMember;

            // =========================
            // ====== STOK =============
            // =========================
            $stokData = DB::table('detail_stock as ds')
                ->join('detail_pembelian_barang as dpb', 'ds.id_detail_pembelian', '=', 'dpb.id')
                ->join('pembelian_barang as pb', 'pb.id', '=', 'dpb.id_pembelian_barang')
                ->join('barang as b', 'ds.id_barang', '=', 'b.id')
                ->join('jenis_barang as jb', 'b.id_jenis_barang', '=', 'jb.id')
                ->join('stock_barang as sb', function ($join) {
                    $join->on('sb.id', '=', 'ds.id_stock')
                        ->on('sb.id_barang', '=', 'ds.id_barang');
                })
                ->whereNull('ds.deleted_at')
                ->whereNull('dpb.deleted_at')
                ->whereNull('pb.deleted_at')
                ->whereNull('b.deleted_at')
                ->whereNull('sb.deleted_at')
                ->select(
                    DB::raw('SUM(ds.qty_now) as total_qty'),
                    DB::raw('SUM(ds.qty_now * dpb.harga_barang) as total_harga')
                )
                ->first();

            $totalStokKeseluruhan = $stokData->total_qty ?? 0;
            $totalKasir = $stokData->total_harga ?? 0;

            $hppDompetSaldo = $this->dompetSaldoService->sumHPP();
            $sisaDompetSaldo = $this->dompetSaldoService->sumSisaSaldo();

            // =========================
            // ====== STOK PER JENIS ==
            // =========================
            $stokPerJenis = DB::table('detail_stock as ds')
                ->join('detail_pembelian_barang as dpb', 'ds.id_detail_pembelian', '=', 'dpb.id')
                ->join('barang as b', 'ds.id_barang', '=', 'b.id')
                ->join('jenis_barang as jb', 'b.id_jenis_barang', '=', 'jb.id')
                ->whereNull('ds.deleted_at')
                ->whereNull('dpb.deleted_at')
                ->whereNull('b.deleted_at')
                ->select(
                    'jb.id as id_jenis_barang',
                    'jb.nama_jenis_barang',
                    DB::raw('SUM(ds.qty_now) as total_qty'),
                    DB::raw('SUM(ds.qty_now * dpb.harga_barang) as total_harga')
                )
                ->groupBy('jb.id', 'jb.nama_jenis_barang')
                ->get();

            // ====== PERBAIKAN BAGIAN RETUR SUPPLIER ======
            $returSupplier = ReturSupplier::where('status', 'proses')
                ->where('tipe_retur', 'pembelian')
                ->with(['detail.barang.jenis'])
                ->get();

            $returPerJenis = [];

            foreach ($returSupplier as $retur) {
                // Distribusikan berdasarkan jenis di detail
                foreach ($retur->detail as $detail) {
                    $jenisNama = $detail->barang->jenis->nama_jenis_barang ?? 'Lainnya';
                    $jenisId   = $detail->barang->jenis->id ?? 0;

                    if (!isset($returPerJenis[$jenisId])) {
                        $returPerJenis[$jenisId] = [
                            'nama_jenis_barang' => $jenisNama,
                            'total_qty'         => 0,
                            'total_harga'       => 0,
                        ];
                    }

                    // Gunakan proporsi qty detail terhadap total qty retur utama
                    $returPerJenis[$jenisId]['total_qty']   += (float) $detail->qty;
                    $returPerJenis[$jenisId]['total_harga'] += (float) (($detail->qty / max(1, $retur->qty)) * $retur->total_hpp);
                }
            }

            // Kurangi stok sesuai jenis
            $stokPerJenis = $stokPerJenis->map(function ($item) use ($returPerJenis) {
                $idJenis = $item->id_jenis_barang;
                if (isset($returPerJenis[$idJenis])) {
                    $item->total_qty;
                    $item->total_harga;
                }
                $item->total_qty   = max(0, $item->total_qty);
                $item->total_harga = max(0, $item->total_harga);
                return $item;
            });

            // ====== BENTUK DATA ======
            $stokJenisItems = [];
            $totalStokJenis = 0;
            $totalQtyJenis  = 0;

            foreach ($stokPerJenis as $idx => $item) {
                $stokJenisItems[] = [
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

            // =========================
            // ====== KAS ==============
            // =========================
            $jenisBarangIdsWithZero = DB::table('jenis_barang')->whereNull('deleted_at')->pluck('id')->push(0);
            $toko   = Toko::where('id', $request->id_toko)->first();
            $isUmum = $toko && $toko->tipe_kas === 'umum';

            // Kas Besar (I.1)
            if ($isUmum) {
                $kasBesarData = $this->kasService->getKasBesar();
                $kasBesarParent = [
                    "kode" => "I.1",
                    "nama" => "Kas Besar",
                    "nilai" => (float) $kasBesarData['total'],
                    "format" => $kasBesarData['format'],
                ];
                $kasBesarItems = [];
            } else {
                $kasBesarItems = [];
                $totalKasBesar = 0;
                foreach ($jenisBarangIdsWithZero as $idx => $idJenis) {
                    $kasJenis = $this->kasService->getKasJenisBarang(0, $idJenis);
                    $nilaiKas = (float) ($kasJenis['total'] ?? 0);
                    $kasBesarItems[] = [
                        "kode"   => "I.1." . ($idx + 1),
                        "nama"   => "Kas " . ($kasJenis['jenis_barang'] ?? ('Jenis ' . ($idx + 1))),
                        "nilai"  => $nilaiKas,
                        "format" => $kasJenis['format'],
                        "sub"    => "I.1",
                    ];
                    $totalKasBesar += $nilaiKas;
                }
                $kasBesarParent = [
                    "kode"   => "I.1",
                    "nama"   => "Kas Besar",
                    "nilai"  => $totalKasBesar,
                    "format" => 'Rp ' . number_format($totalKasBesar, 0, ',', '.'),
                ];
            }

            // Kas Kecil (I.2)
            if ($isUmum) {
                $kasKecilData = $this->kasService->getKasKecil();
                $kasKecilParent = [
                    "kode" => "I.2",
                    "nama" => "Kas Kecil",
                    "nilai" => (float) $kasKecilData['total'],
                    "format" => $kasKecilData['format'],
                ];
                $kasKecilItems = [];
            } else {
                $kasKecilItems = [];
                $totalKasKecil = 0;
                foreach ($jenisBarangIdsWithZero as $idx => $idJenis) {
                    $kasJenis = $this->kasService->getKasJenisBarang(1, $idJenis);
                    $nilaiKas = (float) ($kasJenis['total'] ?? 0);
                    $kasKecilItems[] = [
                        "kode"   => "I.2." . ($idx + 1),
                        "nama"   => "Kas " . ($kasJenis['jenis_barang'] ?? ('Jenis ' . ($idx + 1))),
                        "nilai"  => $nilaiKas,
                        "format" => $kasJenis['format'],
                        "sub"    => "I.2",
                    ];
                    $totalKasKecil += $nilaiKas;
                }
                $kasKecilParent = [
                    "kode"   => "I.2",
                    "nama"   => "Kas Kecil",
                    "nilai"  => $totalKasKecil,
                    "format" => 'Rp ' . number_format($totalKasKecil, 0, ',', '.'),
                ];
            }

            // =========================
            // ====== AKTIVA ==========
            // =========================
            $asetLancarTotal =
                (float) $kasBesarParent['nilai'] +
                (float) $kasKecilParent['nilai'] +
                (float) $piutang['total'] +
                (float) $totalKasir +
                (float) $hppDompetSaldo['saldo'] +
                (float) $penjualanReture +
                (float) $penyesuaianNeraca;

            $asetTetapTotal =
                (float) $asetPeralatanBesar +
                (float) $asetPeralatanKecil;

            $totalAktiva = (float) ($asetLancarTotal + $asetTetapTotal);

            // =========================
            // ====== PASIVA ==========
            // =========================
            $totalHutang  = collect($hutangItems)->sum('nilai');
            $totalEkuitas = collect($ekuitasItems)->sum('nilai');
            $totalPasiva  = (float) ($totalHutang + $totalEkuitas);

            // =========================
            // ====== RINCIAN =========
            // =========================
            $asetLancarItems = array_merge(
                [$kasBesarParent],
                $kasBesarItems,
                [$kasKecilParent],
                $kasKecilItems,
                [
                    [
                        "kode"   => "I.3",
                        "nama"   => "Piutang (Kasbon)",
                        "nilai"  => (float) $piutang['total'],
                        "format" => 'Rp ' . number_format((float) $piutang['total'], 0, ',', '.'),
                    ],
                    $stokBarangParent,
                    ...$stokJenisItems,
                    [
                        "kode"   => "I.5",
                        "nama"   => "Pembelian Saldo Digital (Sisa saldo: {$sisaDompetSaldo['format']})",
                        "nilai"  => $hppDompetSaldo['saldo'],
                        "format" => $hppDompetSaldo['format'],
                    ],
                    [
                        "kode"   => "I.6",
                        "nama"   => "Stok Barang Retur ({$stockRetur})",
                        "nilai"  => (float) $penjualanReture,
                        "format" => 'Rp ' . number_format((float) $penjualanReture, 0, ',', '.'),
                    ],
                    [
                        "kode"   => "I.7",
                        "nama"   => "Penyesuaian",
                        "nilai"  => (float) $penyesuaianNeraca,
                        "format" => 'Rp ' . number_format((float) $penyesuaianNeraca, 0, ',', '.'),
                    ],
                ]
            );

            $data = [
                [
                    'kategori'    => 'AKTIVA',
                    'total'       => (float) $totalAktiva,
                    'format'      => 'Rp ' . number_format((float) $totalAktiva, 0, ',', '.'),
                    'subkategori' => [
                        [
                            'judul'  => 'I. ASET LANCAR',
                            'total'  => (float) $asetLancarTotal,
                            'item'   => $asetLancarItems,
                            'format' => 'Rp ' . number_format((float) $asetLancarTotal, 0, ',', '.'),
                        ],
                        [
                            'judul'  => 'II. ASET TETAP',
                            'total'  => (float) $asetTetapTotal,
                            'format' => 'Rp ' . number_format((float) $asetTetapTotal, 0, ',', '.'),
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
                        ],
                    ],
                ],
                [
                    'kategori'    => 'PASIVA',
                    'total'       => (float) $totalPasiva,
                    'format'      => 'Rp ' . number_format((float) $totalPasiva, 0, ',', '.'),
                    'subkategori' => [
                        [
                            'judul'  => 'III. HUTANG',
                            'total'  => (float) $totalHutang,
                            'item'   => $hutangItems,
                            'format' => 'Rp ' . number_format((float) $totalHutang, 0, ',', '.'),
                        ],
                        [
                            'judul'  => 'IV. EKUITAS',
                            'total'  => (float) $totalEkuitas,
                            'item'   => $ekuitasItems,
                            'format' => 'Rp ' . number_format((float) $totalEkuitas, 0, ',', '.'),
                        ],
                    ],
                ],
            ];

            // =========================
            // ====== CATATAN ==========
            // =========================
            $stokProblem = StockBarangBermasalah::select(
                'status',
                DB::raw('SUM(qty) as total_qty'),
                DB::raw('SUM(total_hpp) as total_hpp')
            )
                ->whereIn('status', ['hilang', 'mati'])
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            $note = [
                'stock_hilang' => [
                    'qty'       => $stokProblem['hilang']->total_qty ?? 0,
                    'total_hpp' => $stokProblem['hilang']->total_hpp ?? 0,
                ],
                'stock_mati' => [
                    'qty'       => $stokProblem['mati']->total_qty ?? 0,
                    'total_hpp' => $stokProblem['mati']->total_hpp ?? 0,
                ],
            ];

            return response()->json([
                'data'        => $data,
                'note'        => $note,
                'status_code' => 200,
                'errors'      => false,
                'message'     => 'Berhasil'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'       => 'error',
                'message'      => 'Data Tidak Ada',
                'message_back' => $th->getMessage(),
                'file'         => $th->getFile(),
                'line'         => $th->getLine(),
                'status_code'  => 500,
            ]);
        }
    }
}
