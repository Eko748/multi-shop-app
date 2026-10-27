<?php

namespace App\Services;

use App\Models\DompetSaldo;
use App\Models\Kas;
use App\Models\KasSaldoHistory;
use App\Models\KasTransaksi;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Models\ReturMember;
use App\Models\Toko;
use App\Models\TransaksiKasirHarian;
use App\Repositories\DompetSaldoRepository;
use App\Repositories\PenjualanNonFisikRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        // --------------------------------------------------------------------------
        // 1. SORTING & TANGGAL BASE
        // --------------------------------------------------------------------------
        $orderDirection = strtolower($request->order ?? ($request->ascending ? 'asc' : 'desc'));
        if (! in_array($orderDirection, ['asc', 'desc'])) {
            $orderDirection = 'desc';
        }

        $sortBy = strtolower($request->sort_by ?? 'tanggal');
        $sortColumn = ($sortBy === 'nominal') ? 'total_nominal' : 'tanggal';

        $hasDateFilter = ! empty($request->from_date) && ! empty($request->to_date);

        if ($hasDateFilter) {
            $startDate = Carbon::parse($request->from_date)->startOfDay();
            $endDate = Carbon::parse($request->to_date)->endOfDay();
            $month = (int) Carbon::now()->month;
            $year = (int) Carbon::now()->year;
        } else {
            $month = ! empty($request->month) ? (int) $request->month : (int) Carbon::now()->month;
            $year = ! empty($request->year) ? (int) $request->year : (int) Carbon::now()->year;

            $startDate = Carbon::create($year, $month, 1, 0, 0, 0)->startOfDay();
            $endDate = Carbon::create($year, $month, 1, 0, 0, 0)->endOfMonth()->endOfDay();
        }

        // --------------------------------------------------------------------------
        // FILTER TOKO
        // --------------------------------------------------------------------------
        $tokoIds = [];

        if ($request->filled('toko_selected')) {
            $selected = $request->toko_selected;
            $selected = is_array($selected) ? $selected : [$selected];
            $tokoIds = array_filter($selected, fn ($val) => ! empty($val) && strtolower((string) $val) !== 'all');
        } elseif ($request->filled('toko_id')) {
            $selected = $request->toko_id;
            $selected = is_array($selected) ? $selected : [$selected];
            $tokoIds = array_filter($selected, fn ($val) => ! empty($val) && strtolower((string) $val) !== 'all');
        }

        // Helper filter toko konsisten seperti LabaRugiService
        $filterToko = function ($query) use ($tokoIds) {
            if (! empty($tokoIds)) {
                $query->whereHas('kas', function ($q) use ($tokoIds) {
                    $q->whereIn('toko_id', $tokoIds);
                });
            }
        };

        // --------------------------------------------------------------------------
        // 2. BASE QUERY & PEMISAHAN QUERY
        // --------------------------------------------------------------------------
        $baseQuery = KasTransaksi::with(['kas.toko'])
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->where('total_nominal', '>', 0);

        if (! empty($tokoIds)) {
            $baseQuery->whereHas('kas', function ($q) use ($tokoIds) {
                $q->whereIn('toko_id', $tokoIds);
            });
        }

        // FILTER KATEGORI
        if ($request->filled('kategori')) {
            $kategori = is_array($request->kategori) ? $request->kategori : [$request->kategori];
            $kategori = array_filter($kategori, fn ($v) => ! is_null($v) && $v !== '' && strpos($v, 'HPP') === false);

            if (! empty($kategori)) {
                $baseQuery->whereIn('kategori', $kategori);
            }
        }

        // KELONING 1: Ringkasan Header Saldo (MURNI TANPA SEARCH)
        $summaryQuery = clone $baseQuery;

        // KELONING 2: List Data Tabel (MEMAKAI SEARCH)
        $query = clone $baseQuery;

        // FILTER SEARCH (Hanya diaplikasikan ke list tabel bawah)
        if ($request->filled('search') && trim($request->search) !== '') {
            $searchTerm = trim(strtolower($request->search));

            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(kategori) LIKE ?', ["%{$searchTerm}%"])
                    ->orWhereRaw('LOWER(keterangan) LIKE ?', ["%{$searchTerm}%"])
                    ->orWhereHas('kas.toko', function ($tokoQuery) use ($searchTerm) {
                        $tokoQuery->whereRaw('LOWER(nama) LIKE ?', ["%{$searchTerm}%"])
                            ->orWhereRaw('LOWER(singkatan) LIKE ?', ["%{$searchTerm}%"]);
                    });
            });
        }

        // --------------------------------------------------------------------------
        // 3. HITUNG TOTAL SALDO HEADER (MEMAKAI $summaryQuery & FORMULASI PRESISI LABA RUGI)
        // --------------------------------------------------------------------------
        $totals = [
            'kas_kecil_in' => 0, 'kas_kecil_out' => 0,
            'kas_besar_in' => 0, 'kas_besar_out' => 0,
            'piutang_in' => 0, 'piutang_out' => 0,
            'hutang_in' => 0, 'hutang_out' => 0,
        ];

        // 1. Ambil Penjualan Umum Raw (Murni dari TransaksiKasirHarian)
        $penjualanUmumRawQuery = KasTransaksi::where('tipe', 'in')
            ->where('sumber_type', TransaksiKasirHarian::class)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->where($filterToko);
        $penjualanUmumRaw = (float) $penjualanUmumRawQuery->sum('total_nominal');

        // 2. Ambil Pemasukan Lainnya (Kecuali Tipe 1 & 2)
        $lainnyaQuery = KasTransaksi::where('tipe', 'in')
            ->where('sumber_type', Pemasukan::class)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->where($filterToko)
            ->whereHasMorph('sumber', [Pemasukan::class], function ($q) {
                $q->whereNotIn('pemasukan_tipe_id', [1, 2])
                    ->whereNull('deleted_at');
            });
        $lainnya = (float) $lainnyaQuery->sum('total_nominal');

        // 3. Ambil Pendapatan Non Transaksi (Dompet Saldo)
        $pendapatanNonTransaksiQuery = DompetSaldo::query()
            ->whereColumn('harga_beli', '<', 'saldo');
        if (! empty($tokoIds)) {
            $pendapatanNonTransaksiQuery->whereIn('toko_id', $tokoIds);
        }
        $pendapatanNonTransaksiQuery->whereBetween('created_at', [$startDate, $endDate]);
        $pendapatanNonTransaksi = (float) ($pendapatanNonTransaksiQuery->selectRaw('SUM(saldo - harga_beli) as total')->value('total') ?? 0);

        // 4. Ambil Komponen Pengurang Omzet
        $nilaiReturMember = (float) KasTransaksi::where('tipe', 'out')
            ->where('sumber_type', ReturMember::class)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->where($filterToko)
            ->sum('total_nominal');

        $pembatalanTrx = (float) KasTransaksi::where('tipe', 'out')
            ->where('kategori', 'Pembatalan Transaksi')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->where($filterToko)
            ->sum('total_nominal');

        $pembatalanTrxBulanLalu = (float) KasTransaksi::where('tipe', 'out')
            ->where('kategori', 'Pembatalan Transaksi Bulan Lalu')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->where($filterToko)
            ->sum('total_nominal');

        $pengeluaranTipe15 = (float) KasTransaksi::where('tipe', 'out')
            ->where('sumber_type', Pengeluaran::class)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->where($filterToko)
            ->whereHasMorph('sumber', [Pengeluaran::class], function ($q) {
                $q->where('pengeluaran_tipe_id', 15)
                    ->whereNull('deleted_at');
            })
            ->sum('total_nominal');

        // Hitung Pendapatan Umum Bersih & Pendapatan Lainnya
        $penjualanUmum = $penjualanUmumRaw - $nilaiReturMember - $pembatalanTrx - $pengeluaranTipe15;
        $pendapatanLainnya = $lainnya + $pendapatanNonTransaksi;
        $totalPendapatanKaskecilIn = $penjualanUmum + $pendapatanLainnya - $pembatalanTrxBulanLalu;

        // Set Kas Kecil In ke Totals
        $totals['kas_kecil_in'] = max(0, $totalPendapatanKaskecilIn);

        // Hitung Totals Lainnya (Kas Kecil Out, Kas Besar, Piutang, Hutang) dari $summaryQuery
        $summaryQuery->get()->each(function ($item) use (&$totals) {
            $nilai = (float) $item->total_nominal;
            $tipe = strtolower(trim($item->tipe));
            $tipeKas = $item->kas?->tipe_kas ?? 'kecil';

            // Biarkan kas_kecil_in dikelola oleh rumus di atas, akumulasi komponen lain di sini
            if (in_array($tipeKas, ['kecil', 'besar'])) {
                $key = "kas_{$tipeKas}_{$tipe}";
                if (array_key_exists($key, $totals) && $key !== 'kas_kecil_in') {
                    $totals[$key] += $nilai;
                }
            } else {
                $key = "{$tipeKas}_{$tipe}";
                if (array_key_exists($key, $totals)) {
                    $totals[$key] += $nilai;
                }
            }
        });
        // --------------------------------------------------------------------------
        // 4. FETCH DATA LIST TABEL & PAGINATION (MEMAKAI $query)
        // --------------------------------------------------------------------------
        $totalRows = (clone $query)->count();

        if ($hasDateFilter) {
            $rawResult = $query->orderBy($sortColumn, $orderDirection)->orderBy('id', 'desc')->get();
            $hasMorePages = false;
            $currentPage = 1;
        } else {
            $skip = (int) ($request->skip ?? 0);
            $limit = (int) ($request->limit ?? 30);

            $rawResult = $query->orderBy($sortColumn, $orderDirection)
                ->orderBy('id', 'desc')
                ->skip($skip)
                ->take($limit)
                ->get();

            $hasMorePages = ($skip + $rawResult->count()) < $totalRows;
            $currentPage = floor($skip / max($limit, 1)) + 1;
        }

        // Mapping Row Data
        $data = collect($rawResult)->map(function ($item) {
            $nilai = (float) $item->total_nominal;
            $tipe = strtolower(trim($item->tipe));
            $tipeKas = $item->kas?->tipe_kas ?? 'kecil';

            $rowTotals = [
                'kas_kecil_in' => 0, 'kas_kecil_out' => 0,
                'kas_besar_in' => 0, 'kas_besar_out' => 0,
                'piutang_in' => 0, 'piutang_out' => 0,
                'hutang_in' => 0, 'hutang_out' => 0,
            ];

            $key = in_array($tipeKas, ['kecil', 'besar']) ? "kas_{$tipeKas}_{$tipe}" : "{$tipeKas}_{$tipe}";
            if (array_key_exists($key, $rowTotals)) {
                $rowTotals[$key] = $nilai;
            }

            return [
                'id' => $item->id,
                'tgl' => Carbon::parse($item->tanggal)->format('d-m-Y H:i:s'),
                'subjek' => $item->kas?->toko?->singkatan ?? '-',
                'kategori' => $item->kategori,
                'item' => $item->keterangan,
                'nilai_transaksi' => $this->formatAngka($nilai),
                'kas_kecil_in' => $this->formatAngka($rowTotals['kas_kecil_in']),
                'kas_kecil_out' => $this->formatAngka($rowTotals['kas_kecil_out']),
                'kas_besar_in' => $this->formatAngka($rowTotals['kas_besar_in']),
                'kas_besar_out' => $this->formatAngka($rowTotals['kas_besar_out']),
                'piutang_in' => $this->formatAngka($rowTotals['piutang_in']),
                'piutang_out' => $this->formatAngka($rowTotals['piutang_out']),
                'hutang_in' => $this->formatAngka($rowTotals['hutang_in']),
                'hutang_out' => $this->formatAngka($rowTotals['hutang_out']),
            ];
        });

        // --------------------------------------------------------------------------
        // 5. KALKULASI SALDO AWAL & RESPONSE
        // --------------------------------------------------------------------------
        $kasList = ! empty($tokoIds)
            ? Kas::whereIn('toko_id', $tokoIds)->get()
            : Kas::whereHas('toko', function ($q) {
                $q->whereNull('deleted_at');
            })->get();

        $kecil_awal = 0;
        $besar_awal = 0;

        foreach ($kasList as $kas) {
            if ($kas->tipe_kas === 'kecil') {
                $kecil_awal += $this->getSaldoAwal($kas, $year, $month);
            } elseif ($kas->tipe_kas === 'besar') {
                $besar_awal += $this->getSaldoAwal($kas, $year, $month);
            }
        }

        $data_total = [
            'kas_kecil' => [
                'saldo_awal' => $this->formatAngka($kecil_awal),
                'kas_kecil_in' => $this->formatAngka($totals['kas_kecil_in']),
                'kas_kecil_out' => $this->formatAngka($totals['kas_kecil_out']),
                'saldo_berjalan' => $this->formatAngka($totals['kas_kecil_in'] - $totals['kas_kecil_out']),
                'saldo_akhir' => $this->formatAngka($kecil_awal + ($totals['kas_kecil_in'] - $totals['kas_kecil_out'])),
            ],
            'kas_besar' => [
                'saldo_awal' => $this->formatAngka($besar_awal),
                'kas_besar_in' => $this->formatAngka($totals['kas_besar_in']),
                'kas_besar_out' => $this->formatAngka($totals['kas_besar_out']),
                'saldo_berjalan' => $this->formatAngka($totals['kas_besar_in'] - $totals['kas_besar_out']),
                'saldo_akhir' => $this->formatAngka($besar_awal + ($totals['kas_besar_in'] - $totals['kas_besar_out'])),
            ],
            'piutang' => [
                'saldo_awal' => $this->formatAngka(0),
                'piutang_in' => $this->formatAngka($totals['piutang_in']),
                'piutang_out' => $this->formatAngka($totals['piutang_out']),
                'saldo_berjalan' => $this->formatAngka($totals['piutang_in'] - $totals['piutang_out']),
                'saldo_akhir' => $this->formatAngka($totals['piutang_in'] - $totals['piutang_out']),
            ],
            'hutang' => [
                'saldo_awal' => $this->formatAngka(0),
                'hutang_in' => $this->formatAngka($totals['hutang_in']),
                'hutang_out' => $this->formatAngka($totals['hutang_out']),
                'saldo_berjalan' => $this->formatAngka($totals['hutang_in'] - $totals['hutang_out']),
                'saldo_akhir' => $this->formatAngka($totals['hutang_in'] - $totals['hutang_out']),
            ],
        ];

        return [
            'data' => $data,
            'data_total' => $data_total,
            'current_page' => $currentPage,
            'has_more_pages' => $hasMorePages,
            'total_rows' => $totalRows,
        ];
    }

    private function getSaldoAwal($kas, $year, $month)
    {
        $prevMonth = $month - 1;
        $prevYear = $year;

        if ($prevMonth == 0) {
            $prevMonth = 12;
            $prevYear--;
        }

        $history = KasSaldoHistory::where('kas_id', $kas->id)
            ->where('tahun', $year)
            ->where('bulan', $month)
            ->first();

        if ($history) {
            return $history->saldo_awal;
        }

        $prevHistory = KasSaldoHistory::where('kas_id', $kas->id)
            ->where('tahun', $prevYear)
            ->where('bulan', $prevMonth)
            ->orderByDesc('id')
            ->first();

        return $prevHistory
            ? $prevHistory->saldo_akhir
            : $kas->saldo_awal;
    }

    private function formatAngka($value)
    {
        $value = $value ?? 0;

        if (floor($value) == $value) {
            return number_format($value, 0, ',', '.');
        }

        return number_format($value, 2, ',', '.');
    }
}
