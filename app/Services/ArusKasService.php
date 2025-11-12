<?php

namespace App\Services;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Pengeluaran;
use App\Models\Kasir;
use App\Models\PembelianBarang;
use App\Models\Pemasukan;
use App\Models\DetailPemasukan;
use App\Models\DetailRetur;
use App\Models\Kasbon;
use App\Models\Mutasi;
use App\Models\Toko;
use App\Models\Hutang;
use App\Models\Piutang;
use App\Models\ReturMember;
use App\Models\ReturMemberDetail;
use App\Models\ReturSupplier;
use App\Repositories\DompetSaldoRepository;
use App\Repositories\PenjualanNonFisikRepository;

class ArusKasService
{
    protected $repoPenjualanNF;
    protected $repoDompetSaldo;

    public function __construct(PenjualanNonFisikRepository $repoPenjualanNF, DompetSaldoRepository $repoDompetSaldo)
    {
        $this->repoPenjualanNF = $repoPenjualanNF;
        $this->repoDompetSaldo = $repoDompetSaldo;
    }

    public function getArusKasData(Request $request)
    {
        // dd($request->all());
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        // Get month and year from request if provided, otherwise use current month and year
        $month = $request->has('month') ? $request->month : Carbon::now()->month;
        $year = $request->has('year') ? $request->year : Carbon::now()->year;
        $filter = [
            'month' => $month,
            'year' => $year
        ];
        // Get data from Pengeluaran model with its details
        $pengeluaranQuery = Pengeluaran::with(['toko', 'jenis_pengeluaran', 'detail_pengeluaran'])
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year);

        // Filter by id_toko if provided
        if ($request->has('id_toko') && is_array($request->id_toko)) {
            $pengeluaranQuery->whereIn('id_toko', $request->id_toko);
        }

        $pengeluaranQuery->orderBy('id', $meta['orderBy']);

        // Get data from Kasir model
        $kasirQuery = Kasir::with('toko', 'users', 'detail_kasir.barang')
            ->where('total_item', '>', 0)
            ->whereMonth('tgl_transaksi', $month)
            ->whereYear('tgl_transaksi', $year);

        if ($request->has('id_toko') && is_array($request->id_toko)) {
            $kasirQuery->whereIn('id_toko', $request->id_toko);
        }

        $kasirQuery->orderBy('id', $meta['orderBy']);

        // Get data from Hutang model
        $hutangQuery = Hutang::with(['toko', 'jenis_hutang', 'detailhutang'])
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        if ($request->has('id_toko') && is_array($request->id_toko)) {
            $hutangQuery->whereIn('id_toko', $request->id_toko);
        }

        $hutangQuery->orderBy('id', $meta['orderBy']);

        // Get data from Piutang model
        $piutangQuery = Piutang::with(['toko', 'jenis_piutang', 'detailpiutang'])
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        if ($request->has('id_toko') && is_array($request->id_toko)) {
            $piutangQuery->whereIn('id_toko', $request->id_toko);
        }

        $piutangQuery->orderBy('id', $meta['orderBy']);

        // Get data from PembelianBarang model
        $pembelianQuery = PembelianBarang::with('supplier')
            ->whereMonth('tgl_nota', $month)
            ->whereYear('tgl_nota', $year)
            ->orderBy('id', $meta['orderBy']);

        // Get data from Pemasukan model
        $pemasukanQuery = Pemasukan::with('jenis_pemasukan')
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year);

        if ($request->has('id_toko') && is_array($request->id_toko)) {
            $pemasukanQuery->whereIn('id_toko', $request->id_toko);
        }

        $pemasukanQuery->orderBy('id', $meta['orderBy']);

        // Get data from Mutasi model
        $mutasiQuery = Mutasi::with(['toko', 'tokoPengirim'])
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        if ($request->has('id_toko') && is_array($request->id_toko)) {
            $mutasiQuery->where(function ($query) use ($request) {
                $query->whereIn('id_toko_penerima', $request->id_toko);
            });
        }

        // Get data from DetailRetur model for cash returns
        $returMemberRefundQuery = ReturMember::with('detail', 'toko:id,nama_toko,singkatan')
            ->whereHas('detail', function ($query) {
                $query->where('qty_refund', '>', 0);
            })->where('status', 'selesai')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        if ($request->has('id_toko') && is_array($request->id_toko)) {
            $returMemberRefundQuery->whereIn('toko_id', $request->id_toko);
        }

        $returMemberRefundQuery->orderBy('id', $meta['orderBy']);

        $returMemberBarangQuery = ReturMember::with('detail', 'toko:id,nama_toko,singkatan')
            ->whereHas('detail', function ($query) {
                $query->where('qty_barang', '>', 0);
            })->where('status', 'selesai')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        if ($request->has('id_toko') && is_array($request->id_toko)) {
            $returMemberBarangQuery->whereIn('toko_id', $request->id_toko);
        }

        $returMemberBarangQuery->orderBy('id', $meta['orderBy']);

        $returSuplierUntungQuery = ReturSupplier::with('detail', 'toko:id,nama_toko,singkatan')
            ->whereHas('detail', function ($query) {
                $query->where('qty_refund', '>', 0);
            })->where('status', 'selesai')->where('keterangan', 'untung')
            ->whereMonth('verify_date', $month)
            ->whereYear('verify_date', $year);

        if ($request->has('id_toko') && is_array($request->id_toko)) {
            $returSuplierUntungQuery->whereIn('toko_id', $request->id_toko);
        }

        $returSuplierUntungQuery->orderBy('id', $meta['orderBy']);

        $returSuplierRugiQuery = ReturSupplier::with('detail', 'toko:id,nama_toko,singkatan')
            ->whereHas('detail', function ($query) {
                $query->where('qty_refund', '>', 0);
            })->where('status', 'selesai')->where('keterangan', 'rugi')
            ->whereMonth('verify_date', $month)
            ->whereYear('verify_date', $year);

        if ($request->has('id_toko') && is_array($request->id_toko)) {
            $returSuplierRugiQuery->whereIn('toko_id', $request->id_toko);
        }

        $returSuplierRugiQuery->orderBy('id', $meta['orderBy']);

        // Get data from Kasbon model
        $kasbonQuery = Kasbon::with('detailKasbon', 'kasir.toko')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        if ($request->has('id_toko') && is_array($request->id_toko)) {
            $kasbonQuery->whereHas('kasir', function ($query) use ($request) {
                $query->whereIn('id_toko', $request->id_toko);
            });
        }

        // Get data from retur model
        $returQuery = DetailRetur::with(['retur.toko', 'barang'])
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);


        $kasbonQuery->orderBy('id', $meta['orderBy']);

        // Get filtered data
        $pengeluaranList = $pengeluaranQuery->get();
        $kasirList = $kasirQuery->get();
        $pembelianList = $pembelianQuery->get();
        $pemasukanList = $pemasukanQuery->get();
        $mutasiList = $mutasiQuery->get();
        $kasbonList = $kasbonQuery->get();
        $hutangList = $hutangQuery->get();
        $piutangList = $piutangQuery->get();
        $returMemberRefundList = $returMemberRefundQuery->get();
        $returMemberBarangList = $returMemberBarangQuery->get();
        $returSuplierUntungList = $returSuplierUntungQuery->get();
        $returSuplierRugiList = $returSuplierRugiQuery->get();

        $penjualanNFList = $this->repoPenjualanNF->getAll((object) $filter);
        $dompetSaldoList = $this->repoDompetSaldo->getAll((object) $filter);

        if (
            $pengeluaranList->isEmpty()
            && $kasirList->isEmpty()
            && $pembelianList->isEmpty()
            && $pemasukanList->isEmpty()
            && $mutasiList->isEmpty()
            && $kasbonList->isEmpty()
            && $hutangList->isEmpty()
            && $piutangList->isEmpty()
            && $returMemberRefundList->isEmpty()
            && $returMemberBarangList->isEmpty()
            && $returSuplierUntungList->isEmpty()
            && $returSuplierRugiList->isEmpty()
            && $penjualanNFList->isEmpty()
            && $dompetSaldoList->isEmpty()
        ) {
            return response()->json([
                'status_code' => 404,
                'errors' => true,
                'message' => 'Data tidak ditemukan',
                'data' => [],
                'data_total' => null,
            ], 404);
        }

        // Format pengeluaran data without grouping
        $pengeluaranData = $pengeluaranList->map(function ($pengeluaran) {
            $kasKecilOut = 0;
            $kasBesarOut = 0;

            if (!is_null($pengeluaran->label)) {
                if ($pengeluaran->label == '0') {
                    $kasBesarOut = (int)$pengeluaran->nilai;
                } elseif ($pengeluaran->label == '1') {
                    $kasKecilOut = (int)$pengeluaran->nilai;
                }
            } else {
                if ($pengeluaran->id_toko == 1) {
                    $kasBesarOut = (int)$pengeluaran->nilai;
                } else {
                    $kasKecilOut = (int)$pengeluaran->nilai;
                }
            }

            $rows = [];

            // Baris utama
            $mainRow = [
                'id' => $pengeluaran->id,
                'tgl' => Carbon::parse($pengeluaran->tanggal)->format('d-m-Y H:i:s'),
                'subjek' => "Toko {$pengeluaran->toko->singkatan}",
                'kategori' => 'Pengeluaran ' . ($pengeluaran->jenis_pengeluaran ? $pengeluaran->jenis_pengeluaran->nama_jenis : ($pengeluaran->ket_hutang ?? 'Tidak Terkategori')),
                'item' => $pengeluaran->nama_pengeluaran,
                'jml' => 1,
                'sat' => "Ls",
                'hst' => (int)$pengeluaran->nilai,
                'nilai_transaksi' => (int)$pengeluaran->nilai,
                'kas_kecil_in' => 0,
                'kas_kecil_out' => $kasKecilOut,
                'kas_besar_in' => 0,
                'kas_besar_out' => $kasBesarOut,
                'piutang_out' => 0,
                'piutang_in' => 0,
                'hutang_in' => 0,
                'hutang_out' => 0,
                'urutan' => 1,
            ];
            $rows[] = $mainRow;

            return collect($rows)->sortBy('urutan')->values();
        })->flatten(1)->values();

        // Format pembelian data
        $pembeliansup = Toko::where('id', 1)->first();
        $idTokoRequest = request()->input('id_toko');

        $pembelianData = $pembelianList->map(function ($pembelian) use ($pembeliansup, $idTokoRequest) {
            if (is_array($idTokoRequest) && !in_array(1, $idTokoRequest)) {
                return null;
            }

            $kasKecilOut = 0;
            $kasBesarOut = 0;

            if (!is_null($pembelian->label)) {
                if ($pembelian->label == '0') {
                    $kasBesarOut = (int)$pembelian->total_nilai;
                } elseif ($pembelian->label == '1') {
                    $kasKecilOut = (int)$pembelian->total_nilai;
                }
            } else {
                if ($pembelian->id_toko == 1) {
                    $kasBesarOut = (int)$pembelian->total_nilai;
                } else {
                    $kasKecilOut = (int)$pembelian->total_nilai;
                }
            }

            return [
                'id' => $pembelian->id,
                'tgl' => \Carbon\Carbon::parse($pembelian->tgl_nota)->format('d-m-Y H:i:s'),
                'subjek' => 'Toko ' . ($pembeliansup ? $pembeliansup->nama_toko : 'Tidak Diketahui'),
                'kategori' => 'Transaksi Supplier',
                'item' => 'Pembelian Barang di ' . ($pembelian->supplier ? $pembelian->supplier->nama_supplier : 'Supplier Tidak Diketahui'),
                'jml' => 1,
                'sat' => 'Ls',
                'hst' => (int)$pembelian->total_nilai,
                'nilai_transaksi' => (int)$pembelian->total_nilai,
                'kas_kecil_in' => 0,
                'kas_kecil_out' => $kasKecilOut,
                'kas_besar_in' => 0,
                'kas_besar_out' => $kasBesarOut,
                'piutang_in' => 0,
                'piutang_out' => 0,
                'hutang_in' => 0,
                'hutang_out' => 0,
            ];
        })->filter();

        // Ambil total utang kasbon berdasarkan id_kasir
        $kasbonUtangByKasirId = $kasbonList->groupBy('id_kasir')->map(function ($group) {
            return $group->sum('utang');
        });

        // Step 1: Proses kasir satu per satu
        $kasirRows = $kasirList->flatMap(function ($kasir) use ($kasbonUtangByKasirId) {
            $kasirId = $kasir->id;
            $utangKasbon = $kasbonUtangByKasirId[$kasirId] ?? 0;
            $tanggal = Carbon::parse($kasir->created_at)->format('d-m-Y');
            $tokoSingkatan = $kasir->toko?->singkatan ?? 'N/A';

            // Ambil semua detail_kasir
            return $kasir->detail_kasir->map(function ($detail) use ($kasir, $kasirId, $utangKasbon, $tanggal, $tokoSingkatan) {
                $jenis = $detail->barang->jenis ?? null;
                $idJenis = $jenis?->id ?? 0;
                $namaJenis = $jenis?->nama_jenis_barang ?? 'Lainnya';

                $harga = (int) $detail->harga;
                $total = (int) $detail->total_harga;
                $kasKecilIn = max(0, $total - $utangKasbon);

                return [
                    'key' => $tokoSingkatan . '_' . $tanggal . '_' . $idJenis,
                    'id' => $kasirId,
                    'tgl' => Carbon::parse($kasir->created_at)->format('d-m-Y H:i:s'),
                    'subjek' => 'Toko ' . $tokoSingkatan,
                    'kategori' => "Pendapatan Umum",
                    'item' => "Pendapatan Harian Kas " . $namaJenis,
                    'jml' => 1,
                    'sat' => $detail->satuan ?? "Ls",
                    'hst' => $harga,
                    'nilai_transaksi' => $total,
                    'kas_kecil_in' => $kasKecilIn,
                    'kas_kecil_out' => 0,
                    'kas_besar_in' => 0,
                    'kas_besar_out' => 0,
                    'piutang_in' => 0,
                    'piutang_out' => 0,
                    'hutang_in' => 0,
                    'hutang_out' => 0,
                ];
            });
        });

        // Step 2: Grouping per toko + tanggal + jenis barang
        $kasirData = $kasirRows->groupBy('key')->map(function ($rows) {
            $first = $rows->first();

            return [
                'id' => $first['id'],
                'tgl' => $first['tgl'],
                'subjek' => $first['subjek'],
                'kategori' => $first['kategori'],
                'item' => $first['item'],
                'jml' => $rows->sum('jml'),
                'sat' => $first['sat'],
                'hst' => $rows->sum('hst'),
                'nilai_transaksi' => $rows->sum('nilai_transaksi'),
                'kas_kecil_in' => $rows->sum('kas_kecil_in'),
                'kas_kecil_out' => 0,
                'kas_besar_in' => 0,
                'kas_besar_out' => 0,
                'piutang_in' => 0,
                'piutang_out' => $rows->sum('piutang_out'),
                'hutang_in' => 0,
                'hutang_out' => 0,
            ];
        })->values();

        $kasbonData = $kasbonList->flatMap(function ($kasbon) {
            // Entry utama dari kasbon
            $kasbonEntry = [
                'id' => $kasbon->id,
                'tgl' => Carbon::parse($kasbon->created_at)->format('d-m-Y H:i:s'),
                'subjek' => isset($kasbon->kasir)
                    ? (isset($kasbon->kasir->toko) ? 'Toko ' . $kasbon->kasir->toko->nama_toko : 'Toko N/A')
                    : 'Toko N/A',
                'kategori' => "Piutang Member",
                'item' => "Kasbon " . ($kasbon->member ? $kasbon->member->nama_member : 'Guest'),
                'jml' => 1,
                'sat' => "Ls",
                'hst' => (int)$kasbon->utang,
                'nilai_transaksi' => (int)$kasbon->utang,
                'kas_kecil_in' => 0,
                'kas_kecil_out' => 0,
                'kas_besar_in' => 0,
                'kas_besar_out' => 0,
                'piutang_in' => (int)$kasbon->utang,
                'piutang_out' => 0,
                'hutang_in' => 0,
                'hutang_out' => 0,
            ];

            // Detail kasbon sebagai array tambahan
            $detailEntries = $kasbon->detailKasbon->map(function ($detail) use ($kasbon) {
                return [
                    'id' => $detail->id,
                    'tgl' => Carbon::parse($detail->created_at)->format('d-m-Y H:i:s'),
                    'subjek' => isset($kasbon->kasir)
                        ? (isset($kasbon->kasir->toko) ? 'Toko ' . $kasbon->kasir->toko->nama_toko : 'Toko N/A')
                        : 'Toko N/A',
                    'kategori' => "Piutang Member",
                    'item' => "Kasbon " . ($kasbon->member ? $kasbon->member->nama_member : 'Guest'),
                    'jml' => 1,
                    'sat' => "Ls",
                    'hst' => (int)$detail->bayar,
                    'nilai_transaksi' => (int)$detail->bayar,
                    'kas_kecil_in' => (int)$detail->bayar,
                    'kas_kecil_out' => 0,
                    'kas_besar_in' => 0,
                    'kas_besar_out' => 0,
                    'piutang_in' => 0,
                    'piutang_out' => (int)$detail->bayar,
                    'hutang_in' => 0,
                    'hutang_out' => 0,
                ];
            });

            // Gabungkan kasbon + detail menjadi satu array
            return collect([$kasbonEntry])->merge($detailEntries);
        })->values();

        // Format pemasukan data
        $pemasukanData = $pemasukanList->map(function ($pemasukan) {
            $kasKecilIn = 0;
            $kasBesarIn = 0;

            if (!is_null($pemasukan->label)) {
                if ($pemasukan->label == '0') {
                    $kasBesarIn = (int)$pemasukan->nilai;
                } elseif ($pemasukan->label == '1') {
                    $kasKecilIn = (int)$pemasukan->nilai;
                }
            } else {
                if ($pemasukan->id_toko == 1) {
                    $kasBesarIn = (int)$pemasukan->nilai;
                } else {
                    $kasKecilIn = (int)$pemasukan->nilai;
                }
            }

            return [
                'id' => $pemasukan->id,
                'tgl' => \Carbon\Carbon::parse($pemasukan->tanggal)->format('d-m-Y H:i:s'),
                'subjek' => "Toko " . ($pemasukan->toko ? $pemasukan->toko->singkatan : 'N/A'),
                'kategori' => 'Pemasukan',
                'item' => $pemasukan->nama_pemasukan,
                'jml' => 1,
                'sat' => 'Ls',
                'hst' => (int)$pemasukan->nilai,
                'nilai_transaksi' => (int)$pemasukan->nilai,
                'kas_kecil_in' => $kasKecilIn,
                'kas_kecil_out' => 0,
                'kas_besar_in' => $kasBesarIn,
                'kas_besar_out' => 0,
                'piutang_in' => 0,
                'piutang_out' => 0,
                'hutang_in' => 0,
                'hutang_out' => 0,
            ];
        });

        // Format mutasi data
        $mutasiData = $mutasiList->flatMap(function ($mutasi) {
            $date = \Carbon\Carbon::parse($mutasi->created_at)->format('d-m-Y H:i:s');
            $rows = [];

            $penerimaName = $mutasi->tokoPenerima ? "Toko {$mutasi->tokoPenerima->singkatan}" : 'Toko Tidak Diketahui';
            $pengirimName = $mutasi->tokoPengirim ? "Toko {$mutasi->tokoPengirim->singkatan}" : 'Toko Tidak Diketahui';

            $kasKecilIn = 0;
            $kasBesarIn = 0;
            $kasKecilOut = 0;
            $kasBesarOut = 0;

            $nilai = (int)$mutasi->nilai;
            $totalToko = Toko::count(); // Ambil jumlah toko dari variabel yang sudah di-pass

            // Penentuan Kas Masuk
            if ($totalToko <= 1) {
                // Jika hanya 1 toko, pakai id_toko_penerima == 0 => kas besar
                if ($mutasi->id_toko_penerima == 0) {
                    $kasBesarIn = $nilai;
                } elseif ($mutasi->id_toko_penerima == 1) {
                    $kasKecilIn = $nilai;
                }
            } else {
                if ($mutasi->id_toko_penerima == 1) {
                    $kasBesarIn = $nilai;
                } else {
                    $kasKecilIn = $nilai;
                }
            }

            // Penentuan Kas Keluar
            if ($totalToko <= 1) {
                if ($mutasi->id_toko_pengirim == 0) {
                    $kasBesarOut = $nilai;
                } elseif ($mutasi->id_toko_pengirim == 1) {
                    $kasKecilOut = $nilai;
                }
            } else {
                if ($mutasi->id_toko_pengirim == 1) {
                    $kasBesarOut = $nilai;
                } else {
                    $kasKecilOut = $nilai;
                }
            }

            // Kas Masuk (Penerima)
            $rows[] = [
                'id' => $mutasi->id,
                'tgl' => $date,
                'subjek' => $penerimaName,
                'kategori' => 'Mutasi Masuk',
                'item' => 'Mutasi Kas Masuk',
                'jml' => 1,
                'sat' => 'Ls',
                'hst' => $nilai,
                'nilai_transaksi' => $nilai,
                'kas_kecil_in' => $kasKecilIn,
                'kas_kecil_out' => 0,
                'kas_besar_in' => $kasBesarIn,
                'kas_besar_out' => 0,
                'piutang_in' => 0,
                'piutang_out' => 0,
                'hutang_in' => 0,
                'hutang_out' => 0,
            ];

            // Kas Keluar (Pengirim)
            $rows[] = [
                'id' => $mutasi->id,
                'tgl' => $date,
                'subjek' => $pengirimName,
                'kategori' => 'Mutasi Keluar',
                'item' => 'Mutasi Kas Keluar',
                'jml' => 1,
                'sat' => 'Ls',
                'hst' => $nilai,
                'nilai_transaksi' => $nilai,
                'kas_kecil_in' => 0,
                'kas_kecil_out' => $kasKecilOut,
                'kas_besar_in' => 0,
                'kas_besar_out' => $kasBesarOut,
                'piutang_in' => 0,
                'piutang_out' => 0,
                'hutang_in' => 0,
                'hutang_out' => 0,
            ];

            return $rows;
        });



        // Format hutang data
        $hutangData = $hutangList->flatMap(function ($hutang) {
            $rows = [];

            $kasKecilIn = 0;
            $kasBesarIn = 0;

            if (!is_null($hutang->label)) {
                if ($hutang->label == '0') {
                    $kasBesarIn = (int)$hutang->nilai;
                } elseif ($hutang->label == '1') {
                    $kasKecilIn = (int)$hutang->nilai;
                }
            } else {
                if ($hutang->id_toko == 1) {
                    $kasBesarIn = (int)$hutang->nilai;
                } else {
                    $kasKecilIn = (int)$hutang->nilai;
                }
            }

            // Main hutang entry (pencatatan hutang)
            $rows[] = [
                'id' => $hutang->id,
                'tgl' => \Carbon\Carbon::parse($hutang->tanggal)->format('d-m-Y H:i:s'),
                'subjek' => "Toko " . ($hutang->toko ? $hutang->toko->singkatan : 'N/A'),
                'kategori' => 'Hutang ' . ($hutang->jenis_hutang ? $hutang->jenis_hutang->nama_jenis : 'Tidak Terkategori'),
                'item' => $hutang->keterangan,
                'jml' => 1,
                'sat' => "Ls",
                'hst' => (int)$hutang->nilai,
                'nilai_transaksi' => (int)$hutang->nilai,
                'kas_kecil_in' => $kasKecilIn,
                'kas_kecil_out' => 0,
                'kas_besar_in' => $kasBesarIn,
                'kas_besar_out' => 0,
                'piutang_in' => 0,
                'piutang_out' => 0,
                'hutang_in' => (int)$hutang->nilai,
                'hutang_out' => 0,
                'urutan' => 1
            ];

            // Detail pembayaran hutang
            foreach ($hutang->detailhutang as $detail) {
                $kasKecilOut = 0;
                $kasBesarOut = 0;

                if (!is_null($hutang->label)) {
                    if ($hutang->label == '0') {
                        $kasBesarOut = (int)$detail->nilai;
                    } elseif ($hutang->label == '1') {
                        $kasKecilOut = (int)$detail->nilai;
                    }
                } else {
                    if ($hutang->id_toko == 1) {
                        $kasBesarOut = (int)$detail->nilai;
                    } else {
                        $kasKecilOut = (int)$detail->nilai;
                    }
                }

                $rows[] = [
                    'id' => $detail->id,
                    'tgl' => \Carbon\Carbon::parse($detail->created_at)->format('d-m-Y H:i:s'),
                    'subjek' => "Toko " . ($hutang->toko ? $hutang->toko->singkatan : 'N/A'),
                    'kategori' => 'Bayar Hutang',
                    'item' => "Pembayaran {$hutang->keterangan}",
                    'jml' => 1,
                    'sat' => "Ls",
                    'hst' => (int)$detail->nilai,
                    'nilai_transaksi' => (int)$detail->nilai,
                    'kas_kecil_in' => 0,
                    'kas_kecil_out' => $kasKecilOut,
                    'kas_besar_in' => 0,
                    'kas_besar_out' => $kasBesarOut,
                    'piutang_in' => 0,
                    'piutang_out' => 0,
                    'hutang_in' => 0,
                    'hutang_out' => (int)$detail->nilai,
                    'urutan' => 2
                ];
            }

            return $rows;
        });

        // Format hutang data
        $piutangData = $piutangList->flatMap(function ($piutang) {
            $rows = [];

            $label = $piutang->label;
            $nilai = (int)$piutang->nilai;

            // Penentuan kas out berdasarkan label
            $kas_kecil_out = 0;
            $kas_besar_out = 0;

            if (is_null($label)) {
                // Gunakan logic lama jika label null
                $kas_kecil_out = $piutang->id_toko != 1 ? $nilai : 0;
                $kas_besar_out = $piutang->id_toko == 1 ? $nilai : 0;
            } elseif ($label == 1) {
                $kas_kecil_out = $nilai;
            } elseif ($label == 0) {
                $kas_besar_out = $nilai;
            }

            // Main piutang entry
            $rows[] = [
                'id' => $piutang->id,
                'tgl' => Carbon::parse($piutang->tanggal)->format('d-m-Y H:i:s'),
                'subjek' => "Toko " . ($piutang->toko ? $piutang->toko->singkatan : 'N/A'),
                'kategori' => 'Piutang ' . ($piutang->jenis_piutang ? $piutang->jenis_piutang->nama_jenis : 'Tidak Terkategori'),
                'item' => $piutang->keterangan,
                'jml' => 1,
                'sat' => "Ls",
                'hst' => $nilai,
                'nilai_transaksi' => $nilai,
                'kas_kecil_out' => $kas_kecil_out,
                'kas_kecil_in' => 0,
                'kas_besar_out' => $kas_besar_out,
                'kas_besar_in' => 0,
                'hutang_in' => 0,
                'hutang_out' => 0,
                'piutang_in' => $nilai,
                'piutang_out' => 0,
                'urutan' => 1
            ];

            // Add detail piutang payments
            foreach ($piutang->detailpiutang as $detail) {
                $detailNilai = (int)$detail->nilai;

                $kas_kecil_in = 0;
                $kas_besar_in = 0;

                if (is_null($label)) {
                    // Gunakan logic lama jika label null
                    $kas_kecil_in = $piutang->id_toko != 1 ? $detailNilai : 0;
                    $kas_besar_in = $piutang->id_toko == 1 ? $detailNilai : 0;
                } elseif ($label == 1) {
                    $kas_kecil_in = $detailNilai;
                } elseif ($label == 0) {
                    $kas_besar_in = $detailNilai;
                }

                $rows[] = [
                    'id' => $detail->id,
                    'tgl' => Carbon::parse($detail->created_at)->format('d-m-Y H:i:s'),
                    'subjek' => "Toko " . ($piutang->toko ? $piutang->toko->singkatan : 'N/A'),
                    'kategori' => 'Bayar Piutang',
                    'item' => "Pembayaran {$piutang->keterangan}",
                    'jml' => 1,
                    'sat' => "Ls",
                    'hst' => $detailNilai,
                    'nilai_transaksi' => $detailNilai,
                    'kas_kecil_out' => 0,
                    'kas_kecil_in' => $kas_kecil_in,
                    'kas_besar_out' => 0,
                    'kas_besar_in' => $kas_besar_in,
                    'hutang_in' => 0,
                    'hutang_out' => 0,
                    'piutang_in' => 0,
                    'piutang_out' => $detailNilai,
                    'urutan' => 2
                ];
            }

            return $rows;
        });

        // Format retur data
        // $returData = $returList
        //     ->groupBy(function ($retur) {
        //         return $retur->retur->toko->singkatan . '_' . Carbon::parse($retur->created_at)->format('d-m-Y');
        //     })
        //     ->map(function ($groupedRetur) {
        //         $firstRetur = $groupedRetur->first();
        //         return [
        //             'id' => $firstRetur->id,
        //             'tgl' => Carbon::parse($firstRetur->created_at)->format('d-m-Y H:i:s'),
        //             'subjek' => "Toko " . ($firstRetur->retur?->toko?->singkatan ?? 'N/A'),
        //             'kategori' => "Data retur",
        //             'item' => "Pengembalian retur",
        //             'jml' => 1,
        //             'sat' => "Ls",
        //             'hst' => (int)$firstRetur->harga,
        //             'nilai_transaksi' => (int)$firstRetur->harga,
        //             'kas_kecil_in' => 0,
        //             'kas_kecil_out' => (int)$firstRetur->harga,
        //             'kas_besar_in' => 0,
        //             'kas_besar_out' => 0,
        //             'piutang_in' => 0,
        //             'piutang_out' => 0,
        //             'hutang_in' => 0,
        //             'hutang_out' => 0,
        //         ];
        //     })->values();

        $returMemberRefundData = $returMemberRefundList
            ->groupBy(function ($retur) {
                return $retur->toko->singkatan . '_' . Carbon::parse($retur->tanggal)->format('d-m-Y');
            })
            ->map(function ($groupedReturs) {
                $firstRetur = $groupedReturs->first();
                $totalRefund = 0;
                $totalQty = 0;

                foreach ($groupedReturs as $retur) {
                    foreach ($retur->detail as $detail) {
                        $totalRefund += (float) $detail->total_refund;
                        $totalQty += (float) $detail->qty_refund;
                    }
                }

                return [
                    'id' => $firstRetur->id,
                    'tgl' => Carbon::parse($firstRetur->tanggal)->format('d-m-Y H:i:s'),
                    'subjek' => "Toko " . ($firstRetur->toko?->singkatan ?? 'N/A'),
                    'kategori' => "Retur dari Member",
                    'item' => "Retur By Refund",
                    'jml' => $totalQty,
                    'sat' => "Ls",
                    'hst' => $totalRefund,
                    'nilai_transaksi' => $totalRefund,
                    'kas_kecil_in' => 0,
                    'kas_kecil_out' => $totalRefund,
                    'kas_besar_in' => 0,
                    'kas_besar_out' => 0,
                    'piutang_in' => 0,
                    'piutang_out' => 0,
                    'hutang_in' => 0,
                    'hutang_out' => 0,
                ];
            })
            ->values();

        $returSuplierUntungData = $returSuplierUntungList
            ->groupBy(function ($retur) {
                return $retur->toko->singkatan . '_' . Carbon::parse($retur->tanggal)->format('d-m-Y');
            })
            ->map(function ($groupedReturs) {
                $firstRetur = $groupedReturs->first();
                $totalRefund = 0;
                $totalQty = 0;

                foreach ($groupedReturs as $retur) {
                    foreach ($retur->detail as $detail) {
                        $totalRefund += (float) $detail->selisih;
                        $totalQty += (float) $detail->qty_refund;
                    }
                }

                return [
                    'id' => $firstRetur->id,
                    'tgl' => Carbon::parse($firstRetur->tanggal)->format('d-m-Y H:i:s'),
                    'subjek' => "Toko " . ($firstRetur->toko?->singkatan ?? 'N/A'),
                    'kategori' => "Retur ke Suplier",
                    'item' => "Refund Untung",
                    'jml' => $totalQty,
                    'sat' => "Ls",
                    'hst' => $totalRefund,
                    'nilai_transaksi' => $totalRefund,
                    'kas_kecil_in' => $totalRefund,
                    'kas_kecil_out' => 0,
                    'kas_besar_in' => 0,
                    'kas_besar_out' => 0,
                    'piutang_in' => 0,
                    'piutang_out' => 0,
                    'hutang_in' => 0,
                    'hutang_out' => 0,
                ];
            })
            ->values();

        $returSuplierRugiData = $returSuplierRugiList
            ->groupBy(function ($retur) {
                return $retur->toko->singkatan . '_' . Carbon::parse($retur->tanggal)->format('d-m-Y');
            })
            ->map(function ($groupedReturs) {
                $firstRetur = $groupedReturs->first();
                $totalRefund = 0;
                $totalQty = 0;

                foreach ($groupedReturs as $retur) {
                    foreach ($retur->detail as $detail) {
                        $totalRefund += (float) $detail->selisih;
                        $totalQty += (float) $detail->qty_refund;
                    }
                }

                return [
                    'id' => $firstRetur->id,
                    'tgl' => Carbon::parse($firstRetur->tanggal)->format('d-m-Y H:i:s'),
                    'subjek' => "Toko " . ($firstRetur->toko?->singkatan ?? 'N/A'),
                    'kategori' => "Retur ke Suplier",
                    'item' => "Refund Rugi",
                    'jml' => $totalQty,
                    'sat' => "Ls",
                    'hst' => $totalRefund,
                    'nilai_transaksi' => $totalRefund,
                    'kas_kecil_in' => 0,
                    'kas_kecil_out' => $totalRefund,
                    'kas_besar_in' => 0,
                    'kas_besar_out' => 0,
                    'piutang_in' => 0,
                    'piutang_out' => 0,
                    'hutang_in' => 0,
                    'hutang_out' => 0,
                ];
            })
            ->values();

        // $returMemberBarangData = $returMemberBarangList
        //     ->groupBy(function ($retur) {
        //         return $retur->toko->singkatan . '_' . Carbon::parse($retur->tanggal)->format('d-m-Y');
        //     })
        //     ->map(function ($groupedReturs) {
        //         $firstRetur = $groupedReturs->first();
        //         $totalRefund = 0;
        //         $totalQty = 0;

        //         foreach ($groupedReturs as $retur) {
        //             foreach ($retur->detail as $detail) {
        //                 $totalRefund += (float) $detail->total_hpp_barang;
        //                 $totalQty += (float) $detail->qty_barang;
        //             }
        //         }

        //         return [
        //             'id' => $firstRetur->id,
        //             'tgl' => Carbon::parse($firstRetur->tanggal)->format('d-m-Y H:i:s'),
        //             'subjek' => "Toko " . ($firstRetur->toko?->singkatan ?? 'N/A'),
        //             'kategori' => "Retur dari Member",
        //             'item' => "Retur by Barang Sejenis",
        //             'jml' => $totalQty,
        //             'sat' => "Ls",
        //             'hst' => $totalRefund,
        //             'nilai_transaksi' => $totalRefund,
        //             'kas_kecil_in' => 0,
        //             'kas_kecil_out' => $totalRefund,
        //             'kas_besar_in' => 0,
        //             'kas_besar_out' => 0,
        //             'piutang_in' => 0,
        //             'piutang_out' => 0,
        //             'hutang_in' => 0,
        //             'hutang_out' => 0,
        //         ];
        //     })
        //     ->values();

        $penjualanNFData = $penjualanNFList
            ->groupBy(function ($item) {
                $tgl    = Carbon::parse($item->created_at)->format('d-m-Y');
                $tokoId = $item->createdBy?->toko?->id ?? 'tanpa-toko';
                return $tgl . '-' . $tokoId;
            })
            ->map(function ($group, $key) {
                $tgl  = Carbon::parse($group->first()->created_at)->format('d-m-Y H:i:s');
                $toko = $group->first()->createdBy?->toko;

                return [
                    'id'              => 'penjualan-non-fisik-' . $tgl . '-' . ($toko?->id ?? 'tanpa-toko'),
                    'tgl'             => $tgl,
                    'subjek'          => "Toko " . ($toko?->nama_toko ?? 'Tidak Diketahui'),
                    'kategori'        => "Pendapatan Umum",
                    'item'            => "Transaksi Digital",
                    'jml'             => $group->sum('total_item'),
                    'sat'             => "Ls",
                    'hst'             => (float) $group->sum('total_harga_jual'),
                    'nilai_transaksi' => (float) $group->sum('total_harga_jual'),
                    'kas_kecil_in'    => (float) $group->sum('total_harga_jual'),
                    'kas_kecil_out'   => 0,
                    'kas_besar_in'    => 0,
                    'kas_besar_out'   => 0,
                    'piutang_in'      => 0,
                    'piutang_out'     => 0,
                    'hutang_in'       => 0,
                    'hutang_out'      => 0,
                ];
            })
            ->values();

        $dompetSaldoData = $dompetSaldoList
            ->map(function ($dompetSaldo) {
                return [
                    'id'              => 'dompet-saldo' . $dompetSaldo->id,
                    'tgl'             => Carbon::parse($dompetSaldo->created_at)->format('d-m-Y H:i:s'),
                    'subjek'          => "Toko " . ($dompetSaldo->createdBy?->toko?->nama_toko ?? 'Tidak Diketahui'),
                    'kategori'        => "Saldo Digital",
                    'item'            => "Pengisian " . ($dompetSaldo->dompetKategori?->nama ?? '-'),
                    'jml'             => 1,
                    'sat'             => "Ls",
                    'hst'             => (float) $dompetSaldo->harga_beli,
                    'nilai_transaksi' => (float) $dompetSaldo->harga_beli,
                    'kas_kecil_in'    => 0,
                    'kas_kecil_out'   => $dompetSaldo->kas == 1 ? (float) $dompetSaldo->harga_beli : 0,
                    'kas_besar_in'    => 0,
                    'kas_besar_out'   => $dompetSaldo->kas == 0 ? (float) $dompetSaldo->harga_beli : 0,
                    'piutang_in'      => 0,
                    'piutang_out'     => 0,
                    'hutang_in'       => 0,
                    'hutang_out'      => 0,
                ];
            })
            ->values();

        $data = $pengeluaranData
            ->concat($kasirData)
            ->concat($pembelianData)
            ->concat($pemasukanData)
            ->concat($mutasiData)
            ->concat($kasbonData)
            ->concat($hutangData)
            ->concat($piutangData)
            ->concat($returMemberRefundData)
            ->concat($returSuplierUntungData)
            ->concat($returSuplierRugiData)
            ->concat($penjualanNFData)
            ->concat($dompetSaldoData)
            ->sortByDesc('tgl')->values();

        $totalBulanLalu = $this->calculateBulanLalu($year, $month);
        $KB_saldoAwal = $totalBulanLalu['kas_besar']['saldo_awal'];

        // dd($KB_saldoAwal);

        // Calculate totals
        $kas_kecil_in = $data->sum('kas_kecil_in');
        $kas_kecil_out = $data->sum('kas_kecil_out');
        $saldo_berjalan = $kas_kecil_in - $kas_kecil_out;
        $saldo_awal = 0;
        $saldo_akhir = $saldo_berjalan - $saldo_awal;

        $kas_besar_in = $data->sum('kas_besar_in');
        $kas_besar_out = $data->sum('kas_besar_out');
        $kas_besar_saldo_berjalan = $kas_besar_in - $kas_besar_out;
        $kas_besar_saldo_awal = $KB_saldoAwal;
        $kas_besar_saldo_akhir = abs($kas_besar_saldo_berjalan - $kas_besar_saldo_awal);

        $piutang_out = $data->sum('piutang_out');
        $piutang_in = $data->sum('piutang_in');
        $piutang_saldo_berjalan = $piutang_in - $piutang_out;
        $piutang_saldo_awal = 0;
        $piutang_saldo_akhir = $piutang_saldo_berjalan - $piutang_saldo_awal;

        $hutang_in = $data->sum('hutang_in');
        $hutang_out = $data->sum('hutang_out');
        $hutang_saldo_berjalan = $hutang_in - $hutang_out;
        $hutang_saldo_awal = 0;
        $hutang_saldo_akhir = $hutang_saldo_berjalan - $hutang_saldo_awal;

        $asetPeralatanBesar = $pengeluaranList->where('is_asset', 'Asset Peralatan Besar')->sum('nilai');
        $asetPeralatanKecil = $pengeluaranList->where('is_asset', 'Asset Peralatan Kecil')->sum('nilai');

        $modal = $pemasukanList->where('id_jenis_pemasukan', 1)->sum('nilai');
        $modalLainnya = $pemasukanList->where('id_jenis_pemasukan', 2)->sum('nilai');

        $total_modal = $modal + $modalLainnya;

        $hutangPendek = $pemasukanList->where('is_pinjam', 1);
        $hutangPanjang = $pemasukanList->where('is_pinjam', 2);

        // Mapping data hutang menjadi format item
        $hutangPendekItems = $hutangPendek->map(function ($item, $index) {
            return [
                "kode" => "III.1." . ($index + 1),
                "nama" => $item->nama_pemasukan,
                "nilai" => $item->nilai,
            ];
        })->toArray();

        $hutangPanjangItems = $hutangPanjang->map(function ($item, $index) {
            return [
                "kode" => "III.2." . ($index + 1),
                "nama" => $item->nama_pemasukan,
                "nilai" => $item->nilai,
            ];
        })->toArray();

        $data_total = [
            'kas_kecil' => [
                'saldo_awal' => $saldo_awal,
                'saldo_akhir' => $saldo_akhir,
                'saldo_berjalan' => $saldo_berjalan,
                'kas_kecil_in' => $kas_kecil_in,
                'kas_kecil_out' => $kas_kecil_out,
            ],
            'kas_besar' => [
                'saldo_awal' => $kas_besar_saldo_awal,
                'saldo_akhir' => $kas_besar_saldo_akhir,
                'saldo_berjalan' => $kas_besar_saldo_berjalan,
                'kas_besar_in' => $kas_besar_in,
                'kas_besar_out' => $kas_besar_out,
            ],
            'piutang' => [
                'saldo_awal' => $piutang_saldo_awal,
                'saldo_akhir' => $piutang_saldo_akhir,
                'saldo_berjalan' => $piutang_saldo_berjalan,
                'piutang_in' => $piutang_in,
                'piutang_out' => $piutang_out,
            ],
            'hutang' => [
                'saldo_awal' => $hutang_saldo_awal,
                'saldo_akhir' => $hutang_saldo_akhir,
                'saldo_berjalan' => $hutang_saldo_berjalan,
                'hutang_in' => $hutang_in,
                'hutang_out' => $hutang_out,
            ],
            'aset_besar' => [
                'aset_peralatan_besar' => $asetPeralatanBesar,
            ],
            'aset_kecil' => [
                'aset_peralatan_kecil' => $asetPeralatanKecil,
            ],
            'modal' => [
                'total_modal' => $modal,
            ]
        ];

        return [
            'data' => $data,
            'data_total' => $data_total,
            'hutang' => [
                'pendek' => $hutangPendekItems,
                'panjang' => $hutangPanjangItems,
            ],
        ];
    }

    protected function calculateBulanLalu($year, $month)
    {
        // Hitung bulan dan tahun sebelumnya
        $prevMonth = $month - 1;
        $prevYear = $year;

        if ($month == 1) {
            $prevMonth = 12;
            $prevYear = $year;
        }

        // Buat request baru untuk data bulan sebelumnya
        $newRequest = new Request([
            'year' => $prevYear,
            'month' => $prevMonth,
            'page' => 1,
            'limit' => 10,
            'ascending' => 0,
            'search' => "",
        ]);

        // Ambil data bulan sebelumnya
        $dataBulanSebelumnyaResponse = $this->getArusKasData($newRequest);

        // Pastikan respons adalah JSON dan ubah menjadi array
        if ($dataBulanSebelumnyaResponse instanceof \Illuminate\Http\JsonResponse) {
            $dataBulanSebelumnya = $dataBulanSebelumnyaResponse->getData(true); // Konversi ke array
        } else {
            $dataBulanSebelumnya = $dataBulanSebelumnyaResponse; // Jika sudah array
        }

        // Hitung saldo awal
        $KB_saldoAwal = $dataBulanSebelumnya['data_total']['kas_besar']['saldo_akhir'] ?? 0;

        $data = [
            'kas_besar' => [
                'saldo_awal' => $KB_saldoAwal,
            ],
        ];

        return $data;
    }
}
