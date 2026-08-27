<?php

namespace App\Services;

use App\Models\Kas;
use App\Models\KasSaldoHistory;
use App\Models\KasTransaksi;
use App\Models\Toko;
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

        $sortBy     = strtolower($request->sort_by ?? 'tanggal');
        $sortColumn = ($sortBy === 'nominal') ? 'total_nominal' : 'tanggal';

        // Cek apakah user menggunakan Filter Tanggal Spesifik
        $hasDateFilter = $request->filled('from_date') && $request->filled('to_date');

        if ($hasDateFilter) {
            $startDate = Carbon::parse($request->from_date)->startOfDay();
            $endDate   = Carbon::parse($request->to_date)->endOfDay();
        } else {
            $month     = $request->month ?? Carbon::now()->month;
            $year      = $request->year ?? Carbon::now()->year;
            $startDate = Carbon::create($year, $month, 1)->startOfDay();
            $endDate   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        }

        // Filter Toko
        $tokoIds = [];
        if ($request->filled('toko_selected')) {
            $selected = is_array($request->toko_selected) ? $request->toko_selected : [$request->toko_selected];
            $tokoIds  = array_filter($selected, fn ($val) => ! empty($val) && strtolower((string) $val) !== 'all');
        } elseif ($request->filled('toko_id') && strtolower((string) $request->toko_id) !== 'all') {
            $tokoIds = is_array($request->toko_id) ? $request->toko_id : [$request->toko_id];
        }

        // --------------------------------------------------------------------------
        // 2. BASE QUERY & FILTERING
        // --------------------------------------------------------------------------
        $query = KasTransaksi::with(['kas.toko'])
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->where('total_nominal', '>', 0);

        if (! empty($tokoIds)) {
            $query->whereHas('kas', function ($q) use ($tokoIds) {
                $q->whereIn('toko_id', $tokoIds);
            });
        }

        // FILTER KATEGORI (Membuang null & empty string)
        if ($request->filled('kategori')) {
            $kategori = is_array($request->kategori) ? $request->kategori : [$request->kategori];
            $kategori = array_filter($kategori, fn($v) => !is_null($v) && $v !== '');

            if (!empty($kategori)) {
                $query->whereIn('kategori', $kategori);
            }
        }

        // FILTER SEARCH (Encapsulation dengan where(function) agar OR tidak merusak Kategori)
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
        // 3. HITUNG TOTAL KESELURUHAN DATA RINGKASAN
        // --------------------------------------------------------------------------
        $totals = [
            'kas_kecil_in'  => 0, 'kas_kecil_out' => 0,
            'kas_besar_in'  => 0, 'kas_besar_out' => 0,
            'piutang_in'    => 0, 'piutang_out'   => 0,
            'hutang_in'     => 0, 'hutang_out'    => 0,
        ];

        $jenisMap = [
            'kecil'     => 'kas_kecil', 'kas kecil' => 'kas_kecil', 'kas_kecil' => 'kas_kecil',
            'besar'     => 'kas_besar', 'kas besar' => 'kas_besar', 'kas_besar' => 'kas_besar',
            'piutang'   => 'piutang',   'hutang'    => 'hutang',
        ];

        (clone $query)->get()->each(function ($item) use (&$totals, $jenisMap) {
            $nilai   = (float) $item->total_nominal;
            $tipe    = strtolower(trim($item->tipe));
            $rawItem = strtolower(trim($item->item ?? $item->keterangan ?? ''));
            $jenis   = $jenisMap[$rawItem] ?? $rawItem;
            $key     = "{$jenis}_{$tipe}";

            if (array_key_exists($key, $totals)) {
                $totals[$key] += $nilai;
            }
        });

        // --------------------------------------------------------------------------
        // 4. LOGIKA FETCH DATA (BYPASS TANGGAL VS OFFSET-SKIP)
        // --------------------------------------------------------------------------
        $totalRows = (clone $query)->count();

        if ($hasDateFilter) {
            // 🔥 Jika ada filter dari-sampai tanggal: Bypass paginasi (ambil semua)
            $rawResult    = $query->orderBy($sortColumn, $orderDirection)->orderBy('id', 'desc')->get();
            $hasMorePages = false;
            $currentPage  = 1;
        } else {
            // 🔥 Jika normal: Gunakan Skip & Take (Dinamis Offset)
            $skip  = (int) ($request->skip ?? 0);
            $limit = (int) ($request->limit ?? 30);

            $rawResult = $query->orderBy($sortColumn, $orderDirection)
                            ->orderBy('id', 'desc')
                            ->skip($skip)
                            ->take($limit)
                            ->get();

            $hasMorePages = ($skip + $rawResult->count()) < $totalRows;
            $currentPage  = floor($skip / max($limit, 1)) + 1;
        }

        // Mapping Row Data
        $data = collect($rawResult)->map(function ($item) use ($jenisMap) {
            $nilai   = (float) $item->total_nominal;
            $tipe    = strtolower(trim($item->tipe));
            $rawItem = strtolower(trim($item->item ?? $item->keterangan ?? ''));
            $jenis   = $jenisMap[$rawItem] ?? $rawItem;

            $rowTotals = [
                'kas_kecil_in'  => 0, 'kas_kecil_out' => 0,
                'kas_besar_in'  => 0, 'kas_besar_out' => 0,
                'piutang_in'    => 0, 'piutang_out'   => 0,
                'hutang_in'     => 0, 'hutang_out'    => 0,
            ];

            $key = "{$jenis}_{$tipe}";
            if (array_key_exists($key, $rowTotals)) {
                $rowTotals[$key] = $nilai;
            }

            return [
                'id'              => $item->id,
                'tgl'             => Carbon::parse($item->tanggal)->format('d-m-Y H:i:s'),
                'subjek'          => $item->kas?->toko?->singkatan ?? '-',
                'kategori'        => $item->kategori,
                'item'            => $item->keterangan,
                'nilai_transaksi' => $this->formatAngka($nilai),
                'kas_kecil_in'    => $this->formatAngka($rowTotals['kas_kecil_in']),
                'kas_kecil_out'   => $this->formatAngka($rowTotals['kas_kecil_out']),
                'kas_besar_in'    => $this->formatAngka($rowTotals['kas_besar_in']),
                'kas_besar_out'   => $this->formatAngka($rowTotals['kas_besar_out']),
                'piutang_in'      => $this->formatAngka($rowTotals['piutang_in']),
                'piutang_out'     => $this->formatAngka($rowTotals['piutang_out']),
                'hutang_in'       => $this->formatAngka($rowTotals['hutang_in']),
                'hutang_out'      => $this->formatAngka($rowTotals['hutang_out']),
            ];
        });

        // --------------------------------------------------------------------------
        // 5. KALKULASI SALDO AWAL & RESPONSE JSON
        // --------------------------------------------------------------------------
        $kasList = ! empty($tokoIds) ? Kas::whereIn('toko_id', $tokoIds)->get() : Kas::all();
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
                'saldo_awal'     => $this->formatAngka($kecil_awal),
                'kas_kecil_in'   => $this->formatAngka($totals['kas_kecil_in']),
                'kas_kecil_out'  => $this->formatAngka($totals['kas_kecil_out']),
                'saldo_berjalan' => $this->formatAngka($totals['kas_kecil_in'] - $totals['kas_kecil_out']),
                'saldo_akhir'    => $this->formatAngka($kecil_awal + ($totals['kas_kecil_in'] - $totals['kas_kecil_out'])),
            ],
            'kas_besar' => [
                'saldo_awal'     => $this->formatAngka($besar_awal),
                'kas_besar_in'   => $this->formatAngka($totals['kas_besar_in']),
                'kas_besar_out'  => $this->formatAngka($totals['kas_besar_out']),
                'saldo_berjalan' => $this->formatAngka($totals['kas_besar_in'] - $totals['kas_besar_out']),
                'saldo_akhir'    => $this->formatAngka($besar_awal + ($totals['kas_besar_in'] - $totals['kas_besar_out'])),
            ],
            'piutang' => [
                'saldo_awal'     => $this->formatAngka(0),
                'piutang_in'     => $this->formatAngka($totals['piutang_in']),
                'piutang_out'    => $this->formatAngka($totals['piutang_out']),
                'saldo_berjalan' => $this->formatAngka($totals['piutang_in'] - $totals['piutang_out']),
                'saldo_akhir'    => $this->formatAngka($totals['piutang_in'] - $totals['piutang_out']),
            ],
            'hutang' => [
                'saldo_awal'     => $this->formatAngka(0),
                'hutang_in'      => $this->formatAngka($totals['hutang_in']),
                'hutang_out'     => $this->formatAngka($totals['hutang_out']),
                'saldo_berjalan' => $this->formatAngka($totals['hutang_in'] - $totals['hutang_out']),
                'saldo_akhir'    => $this->formatAngka($totals['hutang_in'] - $totals['hutang_out']),
            ],
        ];

        return response()->json([
            'data'           => $data,
            'data_total'     => $data_total,
            'current_page'   => $currentPage,
            'has_more_pages' => $hasMorePages,
            'total_rows'     => $totalRows,
        ]);
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

    protected function getAccessibleTokoIds($tokoId = null)
    {
        // Jika tokoId kosong atau 'all', ambil SELURUH ID toko aktif yang belum dihapus (withoutTrashed)
        if (empty($tokoId) || strtolower((string) $tokoId) === 'all') {
            return Toko::whereNull('deleted_at')->pluck('id')->toArray();
        }

        $toko = Toko::whereNull('deleted_at')->find($tokoId);

        if (! $toko) {
            return [];
        }

        $parentId = $toko->parent_id ?? $toko->id;

        // Ambil parent dan seluruh cabang/child yang aktif
        return Toko::whereNull('deleted_at')
            ->where(function ($query) use ($parentId) {
                $query->where('id', $parentId)
                    ->orWhere('parent_id', $parentId);
            })
            ->pluck('id')
            ->toArray();
    }

    private function formatAngka($value)
    {
        $value = $value ?? 0;

        // tanpa desimal
        if (floor($value) == $value) {
            return number_format($value, 0, ',', '.');
        }

        // ada desimal
        return number_format($value, 2, ',', '.');
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
            'search' => '',
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
