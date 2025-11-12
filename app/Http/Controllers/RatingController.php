<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailKasir;
use App\Models\Toko;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RatingController extends Controller
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

            $idUser = $request->input('id_user');
            $user = User::find($idUser);
            $userToko = $user?->id_toko;
            $userTokoName = $user?->toko->nama_toko ?? 'Toko User';

            $selectedTokoIds = $request->input('toko_select', []);
            if (in_array(1, $selectedTokoIds)) {
                $selectedTokoIds = Toko::pluck('id')->toArray();
            }
            if (empty($selectedTokoIds)) {
                $selectedTokoIds = Toko::pluck('id')->toArray();
            }

            $tokoMap = Toko::whereIn('id', $selectedTokoIds)->pluck('nama_toko', 'id')->toArray();
            if ($userToko && array_key_exists($userToko, $tokoMap)) {
                $tokoMap[$userToko] = $userTokoName;
            }

            $hasSearch = !empty($search);
            $hasDate = !empty($startDate) && !empty($endDate);
            $allowShowEmptyItems = $hasSearch;

            $query = DetailKasir::select(
                'detail_kasir.id_barang',
                'barang.nama_barang',
                'kasir.id_toko',
                DB::raw('SUM(kasir.total_item) as total_item'),
                DB::raw('SUM(detail_kasir.qty - COALESCE(detail_retur.qty, 0)) as net_terjual'),
                DB::raw('MAX(detail_kasir.hpp_jual) as hpp_jual')
            )
                ->join('kasir', 'detail_kasir.id_kasir', '=', 'kasir.id')
                ->join('barang', 'detail_kasir.id_barang', '=', 'barang.id')
                ->join('jenis_barang', 'barang.id_jenis_barang', '=', 'jenis_barang.id')
                ->leftJoin('detail_retur', function ($join) {
                    $join->on('detail_kasir.id_kasir', '=', 'detail_retur.id_transaksi')
                        ->on('detail_kasir.id_barang', '=', 'detail_retur.id_barang')
                        ->where('detail_retur.status', 'success');
                })
                ->where('kasir.total_item', '>', 0)
                ->whereIn('kasir.id_toko', $selectedTokoIds)
                ->groupBy(
                    'detail_kasir.id_barang',
                    'barang.nama_barang',
                    'kasir.id_toko'
                );

            if (!empty($jenisBarang)) {
                $query->where('jenis_barang.id', $jenisBarang);
            }

            if ($hasDate) {
                $query->whereBetween('detail_kasir.created_at', [$startDate, $endDate]);
            } else {
                $query->whereDate('detail_kasir.created_at', Carbon::today());
            }

            $rawData = $query->get();

            // Ambil daftar barang tergantung kondisi
            if ($allowShowEmptyItems) {
                $barangQuery = Barang::with('stockBarang', 'jenis');
                if (!empty($search)) {
                    $barangQuery->where(DB::raw('LOWER(nama_barang)'), 'like', '%' . strtolower($search) . '%');
                }
                if (!empty($jenisBarang)) {
                    $barangQuery->where('id_jenis_barang', $jenisBarang);
                }
                $barangList = $barangQuery->get();
            } else {
                $barangIds = $rawData->pluck('id_barang')->unique();
                $barangList = Barang::with('stockBarang', 'jenis')
                    ->whereIn('id', $barangIds)
                    ->get();
            }

            $grouped = [];

            foreach ($barangList as $barang) {
                $barangId = $barang->id;
                $barangName = $barang->nama_barang;
                $stockNow = $barang->stockBarang->sum('stock');
                $dataPerToko = array_fill_keys(array_values($tokoMap), ['terjual' => 0]);

                $matchedData = $rawData->where('id_barang', $barangId);
                $totalTerjual = 0;
                $hppJual = 0;

                if ($matchedData->isNotEmpty()) {
                    foreach ($matchedData as $item) {
                        $tokoId = $item->id_toko;
                        $tokoNama = $tokoMap[$tokoId] ?? 'Unknown Toko';
                        $netTerjual = (int) $item->net_terjual;
                        $dataPerToko[$tokoNama]['terjual'] = $netTerjual;
                        $totalTerjual += $netTerjual;

                        if ($item->hpp_jual > 0) {
                            $hppJual = (float) $item->hpp_jual;
                        }
                    }
                } else {
                    $stockInfo = $barang->stockBarang->first();
                    $hppJual = $stockInfo ? $stockInfo->hpp_baru : 0;
                }

                if ($totalTerjual === 0 && !$allowShowEmptyItems) {
                    continue;
                }

                $dataPerToko['stock_now'] = (int) $stockNow;
                $dataPerToko['hpp_jual'] = $hppJual;

                $grouped[$barangName] = $dataPerToko;
            }

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

            $tokoCount = count($tokoMap);
            $total = $sorted->count();
            $paginated = $sorted->slice(($page - 1) * $limit, $limit);

            $finalData = [];
            foreach ($paginated as $barang => $dataPerToko) {
                $stockNow = $dataPerToko['stock_now'] ?? 0;
                $hppJual = $dataPerToko['hpp_jual'] ?? 0;
                unset($dataPerToko['stock_now'], $dataPerToko['hpp_jual']);

                if ($tokoCount === 1) {
                    $firstData = array_values($dataPerToko)[0];
                    $finalData[$barang] = [
                        'Jumlah Item Terjual' => $firstData['terjual'],
                        'HPP Jual' => $hppJual,
                        'Stock Sekarang' => $stockNow
                    ];
                } else {
                    $formattedPerToko = [];
                    foreach ($dataPerToko as $tokoNama => $values) {
                        $formattedPerToko[$tokoNama] = $values['terjual'];
                    }
                    $finalData[$barang] = [
                        'Jumlah Item Terjual Per Toko' => $formattedPerToko,
                        'HPP Jual' => $hppJual,
                        'Stock Sekarang' => $stockNow
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
                'trace' => $th->getMessage()
            ]);
        }
    }

    public function getBarangJual(Request $request)
    {
        $selectedTokoIds = $request->input('toko_select', []);

        if (!empty($selectedTokoIds)) {
            // Filter berdasarkan toko yang dipilih
            $dataBarang = DetailKasir::select('detail_kasir.id_barang', 'kasir.id_toko', DB::raw('SUM(detail_kasir.qty) as total_terjual'))
                ->join('kasir', 'detail_kasir.id_kasir', '=', 'kasir.id')
                ->whereIn('kasir.id_toko', $selectedTokoIds)
                ->groupBy('detail_kasir.id_barang', 'kasir.id_toko')
                ->get()
                ->groupBy('id_barang'); // Grupkan data berdasarkan id_barang
        } else {
            // Tampilkan data untuk semua toko
            $dataBarang = DetailKasir::select('detail_kasir.id_barang', 'kasir.id_toko', DB::raw('SUM(detail_kasir.qty) as total_terjual'))
                ->join('kasir', 'detail_kasir.id_kasir', '=', 'kasir.id')
                ->groupBy('detail_kasir.id_barang', 'kasir.id_toko')
                ->get()
                ->groupBy('id_barang'); // Grupkan data berdasarkan id_barang
        }

        return response()->json($dataBarang);
    }
}
