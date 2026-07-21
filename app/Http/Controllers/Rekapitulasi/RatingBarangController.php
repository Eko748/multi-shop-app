<?php

namespace App\Http\Controllers\Rekapitulasi;

use App\Http\Controllers\Controller;
use App\Models\StockBarang;
use App\Models\Toko;
use App\Models\TransaksiKasirDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RatingBarangController extends Controller
{
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Rating Barang',
        ];
    }

    public function index(Request $request)
    {
        $menu = [$this->title[0], $this->label[2]];

        return view('laporan.rating.index', compact('menu'));
    }

    public function getRatingBarang(Request $request)
    {
        try {
            $limit = min((int) $request->input('limit', 100), 200);
            $page = (int) $request->input('page', 1);
            $search = strtolower(trim($request->input('search', '')));
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            $jenisBarang = $request->input('jenis_barang');

            // PAYLOAD BARU: role_id dan toko_id user
            $roleId = (int) $request->input('role_id');
            $userTokoId = $request->input('toko_id');

            // 1. Tentukan toko mana saja yang boleh diakses
            if ($roleId === 1) {
                // Jika admin, ambil semua toko
                $tokoMap = Toko::pluck('singkatan', 'id')->toArray();
                $selectedTokoIds = array_keys($tokoMap);
            } else {
                // Jika bukan admin, kunci hanya untuk toko si user
                $toko = Toko::find($userTokoId);
                $tokoNama = $toko->nama ?? $toko->singkatan ?? 'Toko User';
                $tokoMap = [$userTokoId => $tokoNama];
                $selectedTokoIds = [$userTokoId];
            }

            $hasSearch = ! empty($search);
            $hasDate = ! empty($startDate) && ! empty($endDate);
            $allowShowEmptyItems = $hasSearch;

            // 2. Query Data Transaksi Utama
            $query = TransaksiKasirDetail::select(
                'barang.id as barang_id',
                'barang.nama',
                'transaksi_kasir.toko_id',
                DB::raw('SUM(transaksi_kasir_detail.qty) as total_item'),
                DB::raw('SUM(transaksi_kasir_detail.qty - COALESCE(retur_member_detail.qty_request,0)) as net_terjual'),
                DB::raw('MAX(stock_barang.hpp_baru) as hpp_jual')
            )
                ->join('transaksi_kasir', 'transaksi_kasir_detail.transaksi_kasir_id', '=', 'transaksi_kasir.id')
                ->join('stock_barang_batch', 'transaksi_kasir_detail.stock_barang_batch_id', '=', 'stock_barang_batch.id')
                ->join('stock_barang', 'stock_barang_batch.stock_barang_id', '=', 'stock_barang.id')
                ->join('barang', 'stock_barang.barang_id', '=', 'barang.id')
                ->join('jenis_barang', 'barang.jenis_barang_id', '=', 'jenis_barang.id')
                ->leftJoin('retur_member_detail', function ($join) {
                    $join->on('transaksi_kasir_detail.id', '=', 'retur_member_detail.transaksi_kasir_detail_id')
                        ->on('barang.id', '=', 'retur_member_detail.barang_id');
                })
                ->where('transaksi_kasir.total_qty', '>', 0)
                ->whereIn('transaksi_kasir.toko_id', $selectedTokoIds)
                ->groupBy(
                    'barang.id',
                    'barang.nama',
                    'transaksi_kasir.toko_id'
                );

            if (! empty($jenisBarang)) {
                $query->where('jenis_barang.id', $jenisBarang);
            }

            if ($hasDate) {
                // Tips: Pastikan format startDate dan endDate menyertakan full day interval
                $query->whereBetween('transaksi_kasir_detail.created_at', [$startDate, $endDate]);
            } else {
                $query->whereDate('transaksi_kasir_detail.created_at', Carbon::today());
            }

            $rawData = $query->get();

            // 3. Query Master Stock Barang (Dipakai untuk mapping nama & stok saat ini)
            // Kita ikut filter Eager Loading stockBarangBatch berdasarkan toko terpilih agar datanya valid per toko
            $barangQuery = StockBarang::with(['barang.jenis', 'stockBarangBatch' => function ($q) use ($selectedTokoIds) {
                $q->whereIn('toko_id', $selectedTokoIds);
            }]);

            // Filter toko di tingkat StockBarang jika model ini menyimpan info toko_id langsung
            // $barangQuery->whereIn('toko_id', $selectedTokoIds);

            if ($allowShowEmptyItems) {
                if (! empty($search)) {
                    $barangQuery->whereHas('barang', function ($q) use ($search) {
                        $q->where(DB::raw('LOWER(nama)'), 'like', '%'.strtolower($search).'%');
                    });
                }
                if (! empty($jenisBarang)) {
                    $barangQuery->whereHas('barang', function ($q) use ($jenisBarang) {
                        $q->where('jenis_barang_id', $jenisBarang);
                    });
                }
                $barangList = $barangQuery->get();
            } else {
                $barangIds = $rawData->pluck('barang_id')->unique();
                $barangList = $barangQuery->whereIn('barang_id', $barangIds)->get();
            }

            // 4. Proses Grouping Data
            $grouped = [];

            foreach ($barangList as $barang) {
                $barangId = (int) $barang->barang_id;
                $barangName = $barang->barang->nama;

                // Hitung sisa stok hanya dari batch toko terpilih
                $stockNow = $barang->stockBarangBatch ? $barang->stockBarangBatch->sum('qty_sisa') : 0;

                // Buat template default berisi 0 untuk toko-toko terpilih
                $dataPerToko = array_fill_keys(array_values($tokoMap), ['terjual' => 0]);

                // Filter data transaksi yang cocok dengan ID barang ini
                $matchedData = $rawData->filter(function ($item) use ($barangId) {
                    return (int) $item->barang_id === $barangId;
                });

                $totalTerjual = 0;
                $hppJual = 0;

                if ($matchedData->isNotEmpty()) {
                    foreach ($matchedData as $item) {
                        $tokoId = (int) $item->toko_id;
                        $tokoNama = $tokoMap[$tokoId] ?? null;

                        if ($tokoNama) {
                            $netTerjual = (int) $item->net_terjual;
                            $dataPerToko[$tokoNama]['terjual'] = $netTerjual;
                            $totalTerjual += $netTerjual;
                        }

                        if ($item->hpp_jual > 0) {
                            $hppJual = (float) $item->hpp_jual;
                        }
                    }
                } else {
                    $hppJual = $barang ? (float) $barang->hpp_baru : 0;
                }

                // Jika tidak ada penjualan sama sekali dan tidak sedang mencari sesuatu, skip
                if ($totalTerjual === 0 && ! $allowShowEmptyItems) {
                    continue;
                }

                $dataPerToko['stock_now'] = (int) $stockNow;
                $dataPerToko['hpp_jual'] = $hppJual;

                $grouped[$barangName] = $dataPerToko;
            }

            // 5. Sorting berdasarkan total penjualan terbanyak
            $sorted = collect($grouped)
                ->mapWithKeys(function ($dataPerToko, $barang) {
                    $totalTerjual = collect($dataPerToko)->filter(function ($value, $key) {
                        return $key !== 'stock_now' && $key !== 'hpp_jual';
                    })->sum(function ($val) {
                        return $val['terjual'] ?? 0;
                    });

                    return [$barang => ['data' => $dataPerToko, 'total' => $totalTerjual]];
                })
                ->sortByDesc('total')
                ->mapWithKeys(function ($item, $barang) {
                    return [$barang => $item['data']];
                });

            // 6. Pagination & Formatting Output JSON
            $tokoCount = count($tokoMap);
            $total = $sorted->count();
            $paginated = $sorted->slice(($page - 1) * $limit, $limit);

            $finalData = [];
            foreach ($paginated as $barang => $dataPerToko) {
                $stockNow = $dataPerToko['stock_now'] ?? 0;
                $hppJual = $dataPerToko['hpp_jual'] ?? 0;
                unset($dataPerToko['stock_now'], $dataPerToko['hpp_jual']);

                if ($tokoCount === 1) {
                    $firstData = array_values($dataPerToko)[0] ?? ['terjual' => 0];
                    $finalData[$barang] = [
                        'Jumlah Item Terjual' => $firstData['terjual'],
                        'HPP Jual' => $hppJual,
                        'Stok Sekarang' => $stockNow,
                    ];
                } else {
                    $formattedPerToko = [];
                    foreach ($dataPerToko as $tokoNama => $values) {
                        $formattedPerToko[$tokoNama] = $values['terjual'];
                    }
                    $finalData[$barang] = [
                        'Jumlah Item Terjual Per Toko' => $formattedPerToko,
                        'HPP Jual' => $hppJual,
                        'Stok Sekarang' => $stockNow,
                    ];
                }
            }

            $pagination = [
                'total' => $total,
                'per_page' => $limit,
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
            ];

            return response()->json([
                'data' => $finalData,
                'status_code' => 200,
                'errors' => false,
                'message' => $total ? 'Data retrieved successfully' : 'No data found',
                'pagination' => $pagination,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to retrieve data',
                'status_code' => 500,
                'trace' => $th->getMessage(),
            ]);
        }
    }
}
