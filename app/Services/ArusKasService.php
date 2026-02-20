<?php

namespace App\Services;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Pengeluaran;
use App\Models\Kasir;
use App\Models\PembelianBarang;
use App\Models\Pemasukan;
use App\Models\DetailPemasukan;
use App\Models\KasSaldoHistory;
use App\Models\Kasbon;
use App\Models\Mutasi;
use App\Models\Toko;
use App\Models\Hutang;
use App\Models\Kas;
use App\Models\KasTransaksi;
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
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit']   = $request->limit <= 30 ? $request->limit : 30;

        $tokoId = $request->toko_id;
        $filterToko = $request->toko_selected;

        $month = $request->month ?? Carbon::now()->month;
        $year  = $request->year  ?? Carbon::now()->year;

        $fromDate = $request->from_date;
        $toDate   = $request->to_date;

        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        if ($fromDate && $toDate) {
            $startDate = Carbon::parse($fromDate)->startOfDay();
            $endDate   = Carbon::parse($toDate)->endOfDay();
        }

        $accessibleTokoIds = $this->getAccessibleTokoIds($tokoId);

        if (!empty($filterToko)) {
            $filterToko = is_array($filterToko) ? $filterToko : [$filterToko];
            $accessibleTokoIds = array_values(array_intersect($accessibleTokoIds, $filterToko));
        }

        $query = KasTransaksi::whereBetween('tanggal', [$startDate, $endDate])
            ->whereHas('kas', function ($q) use ($accessibleTokoIds) {
                $q->whereIn('toko_id', $accessibleTokoIds);
            })->where('total_nominal', '>', 0)
            ->orderBy('tanggal', $meta['orderBy'])
            ->orderBy('id', 'desc');

        $data_raw = $query->get()->map(function ($item) {

            $nilai = $item->total_nominal;

            $jenis = strtolower($item->item);
            $tipe  = strtolower($item->tipe);

            $out = [
                'kas_kecil_in' => 0,
                'kas_kecil_out' => 0,
                'kas_besar_in' => 0,
                'kas_besar_out' => 0,
                'piutang_in' => 0,
                'piutang_out' => 0,
                'hutang_in' => 0,
                'hutang_out' => 0,
            ];

            if ($jenis === 'kecil') {
                $out["kas_kecil_{$tipe}"] = $nilai;
            }

            if ($jenis === 'besar') {
                $out["kas_besar_{$tipe}"] = $nilai;
            }

            if ($jenis === 'hutang') {
                $out["hutang_{$tipe}"] = $nilai;
            }

            if ($jenis === 'piutang') {
                $out["piutang_{$tipe}"] = $nilai;
            }

            return array_merge([
                'id' => $item->id,
                'tgl' => Carbon::parse($item->tanggal)->format('d-m-Y H:i:s'),
                'subjek' => "Toko {$item->kas->toko->nama}",
                'kategori' => $item->kategori,
                'item' => $item->keterangan,
                'nilai_transaksi' => $nilai,
            ], $out);
        });

        $kas_kecil_in_total  = $data_raw->sum('kas_kecil_in');
        $kas_kecil_out_total = $data_raw->sum('kas_kecil_out');

        $kas_besar_in_total  = $data_raw->sum('kas_besar_in');
        $kas_besar_out_total = $data_raw->sum('kas_besar_out');

        $piutang_in_total  = $data_raw->sum('piutang_in');
        $piutang_out_total = $data_raw->sum('piutang_out');

        $hutang_in_total  = $data_raw->sum('hutang_in');
        $hutang_out_total = $data_raw->sum('hutang_out');

        $kas_kecil_list = Kas::whereIn('toko_id', $accessibleTokoIds)
            ->where('tipe_kas', 'kecil')
            ->get();

        $kas_besar_list = Kas::whereIn('toko_id', $accessibleTokoIds)
            ->where('tipe_kas', 'besar')
            ->get();

        $kecil_awal = 0;
        $besar_awal = 0;

        foreach ($kas_kecil_list as $kas) {
            $kecil_awal += $this->getSaldoAwal($kas, $year, $month);
        }

        foreach ($kas_besar_list as $kas) {
            $besar_awal += $this->getSaldoAwal($kas, $year, $month);
        }

        $piutang_awal = 0;
        $hutang_awal  = 0;

        $data_total = [

            'kas_kecil' => [
                'saldo_awal'     => $this->formatAngka($kecil_awal),
                'kas_kecil_in'   => $this->formatAngka($kas_kecil_in_total),
                'kas_kecil_out'  => $this->formatAngka($kas_kecil_out_total),
                'saldo_berjalan' => $this->formatAngka($kas_kecil_in_total - $kas_kecil_out_total),
                'saldo_akhir'    => $this->formatAngka($kecil_awal + ($kas_kecil_in_total - $kas_kecil_out_total)),
            ],

            'kas_besar' => [
                'saldo_awal'     => $this->formatAngka($besar_awal),
                'kas_besar_in'   => $this->formatAngka($kas_besar_in_total),
                'kas_besar_out'  => $this->formatAngka($kas_besar_out_total),
                'saldo_berjalan' => $this->formatAngka($kas_besar_in_total - $kas_besar_out_total),
                'saldo_akhir'    => $this->formatAngka($besar_awal + ($kas_besar_in_total - $kas_besar_out_total)),
            ],

            'piutang' => [
                'saldo_awal'     => $this->formatAngka($piutang_awal),
                'piutang_in'     => $this->formatAngka($piutang_in_total),
                'piutang_out'    => $this->formatAngka($piutang_out_total),
                'saldo_berjalan' => $this->formatAngka($piutang_in_total - $piutang_out_total),
                'saldo_akhir'    => $this->formatAngka($piutang_awal + ($piutang_in_total - $piutang_out_total)),
            ],

            'hutang' => [
                'saldo_awal'     => $this->formatAngka($hutang_awal),
                'hutang_in'      => $this->formatAngka($hutang_in_total),
                'hutang_out'     => $this->formatAngka($hutang_out_total),
                'saldo_berjalan' => $this->formatAngka($hutang_in_total - $hutang_out_total),
                'saldo_akhir'    => $this->formatAngka($hutang_awal + ($hutang_in_total - $hutang_out_total)),
            ],
        ];

        $data = $data_raw->map(function ($x) {
            return [
                'id' => $x['id'],
                'tgl' => $x['tgl'],
                'subjek' => $x['subjek'],
                'kategori' => $x['kategori'],
                'item' => $x['item'],
                'nilai_transaksi' => $this->formatAngka($x['nilai_transaksi']),

                'kas_kecil_in'  => $this->formatAngka($x['kas_kecil_in']),
                'kas_kecil_out' => $this->formatAngka($x['kas_kecil_out']),
                'kas_besar_in'  => $this->formatAngka($x['kas_besar_in']),
                'kas_besar_out' => $this->formatAngka($x['kas_besar_out']),
                'piutang_in'    => $this->formatAngka($x['piutang_in']),
                'piutang_out'   => $this->formatAngka($x['piutang_out']),
                'hutang_in'     => $this->formatAngka($x['hutang_in']),
                'hutang_out'    => $this->formatAngka($x['hutang_out']),
            ];
        });

        return [
            'data'       => $data,
            'data_total' => $data_total,
        ];
    }

    private function getSaldoAwal($kas, $year, $month)
    {
        $prevMonth = $month - 1;
        $prevYear  = $year;

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

    private function getAccessibleTokoIds($tokoId)
    {
        $toko = Toko::find($tokoId);

        // 1. Jika ini adalah parent (parent_id null) → tidak boleh dilihat oleh siapapun
        //    Maka hanya bisa melihat dirinya sendiri (atau bisa juga return empty, sesuai kebutuhan)
        if ($toko->parent_id === null) {
            return [$tokoId];
        }

        // 2. Ambil semua child dari toko ini (jika ada)
        $childIds = Toko::where('parent_id', $tokoId)->pluck('id')->toArray();

        // 3. Child hanya bisa melihat dirinya sendiri + anak-anaknya
        return array_unique(array_merge([$tokoId], $childIds));
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
