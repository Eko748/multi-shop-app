<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailPembelianBarang;
use App\Models\DetailStockBarang;
use App\Models\DetailToko;
use App\Models\StockBarang;
use App\Models\StockBarangBermasalah;
use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Enums\StatusStockBarang;

class StockBarangController extends Controller
{
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Stok Barang',
        ];
    }

    public function getstockbarang(Request $request)
    {
        $meta['orderBy'] = $request->input('ascending', 0) ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $idToko = $request->input('id_toko');

        // Ambil data stok barang dari tabel 'stock_barang'
        $query = StockBarang::with(['barang', 'toko']);

        // Sorting berdasarkan kolom stock atau qty
        if ($idToko == 1) {
            $query->orderBy('stock', $meta['orderBy']);
        } else {
            $query->withSum([
                'detailToko as total_qty' => function ($q) use ($idToko) {
                    $q->where('id_toko', $idToko);
                }
            ], 'qty');
        }

        // Tambahkan filter pencarian jika ada
        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereHas('barang', function ($subquery) use ($searchTerm) {
                    $subquery->whereRaw("LOWER(nama_barang) LIKE ?", ["%$searchTerm%"]);
                });
            });
        }

        // Filter berdasarkan tanggal
        if ($request->has('startDate') && $request->has('endDate')) {
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Ambil data dengan pagination
        $data = $query->paginate($meta['limit']);

        $paginationMeta = [
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'total_pages' => $data->lastPage()
        ];

        // Format data untuk respons
        $mappedData = collect($data->items())->map(function ($item) use ($idToko) {
            $barang = $item->barang;

            $level_harga = [];
            if ($barang && $barang->level_harga) {
                $decoded_level_harga = json_decode($barang->level_harga, true);
                foreach ($decoded_level_harga as $lv) {
                    if (strpos($lv, ' : ') !== false) {
                        list($level_name, $level_value) = explode(' : ', $lv);
                        $value = is_numeric($level_value) ? (int)$level_value : 0;
                        $formatted_value = 'Rp ' . number_format($value, 0, ',', '.');
                        $level_harga[$level_name] = $formatted_value;
                    }
                }
            }

            if ($idToko == 1) {
                return [
                    'id' => $item->id,
                    'id_barang' => $barang->id ?? null,
                    'nama_barang' => $barang->nama_barang ?? null,
                    'barcode' => $barang->barcode ?? null,
                    'hpp_baru' => $item->hpp_baru,
                    'stock' => $item->stock,
                    'level_harga' => $level_harga,
                ];
            } else {
                return [
                    'id' => $item->id,
                    'id_barang' => $barang->id ?? null,
                    'nama_barang' => $barang->nama_barang ?? null,
                    'barcode' => $barang->barcode ?? null,
                    'hpp_baru' => $item->hpp_baru,
                    'stock' => $item->total_qty ?? 0,
                    'level_harga' => $level_harga,
                ];
            }
        });

        // Jika tidak ada data, kembalikan respons error
        if ($mappedData->isEmpty()) {
            return response()->json([
                'status_code' => 400,
                'errors' => true,
                'message' => 'Tidak ada data'
            ], 400);
        }

        // Respons JSON
        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Sukses',
            'pagination' => $paginationMeta
        ], 200);
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[0]];

        return view('master.stockbarang.index', compact('menu'));
    }

    public function refreshStok(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'id_barang' => 'required|integer',
            'pin' => 'required|string',
            'id_toko' => 'required|string',
            'user_id' => 'required|string',
            'message' => 'nullable|string'
        ]);

        $toko = Toko::where('id', $request->id_toko)->first();

        if (!$toko) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data toko tidak ditemukan.',
            ], 404);
        }

        if ($toko->pin !== $request->pin) {
            return response()->json([
                'status' => 'error',
                'message' => 'PIN yang Anda masukkan salah.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $stok = StockBarang::where('id', $request->id)
                ->where('id_barang', $request->id_barang)
                ->first();
            $barang = Barang::find($request->id_barang);
            $namaBarang = $barang?->nama_barang ?? '-';

            if (!$stok) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data stok tidak ditemukan.',
                ], 404);
            }

            $oldStock = $stok->stock;
            $hppBaru = $stok->hpp_baru ?? 0;

            StockBarangBermasalah::create([
                'stock_barang_id' => $stok->id,
                'status' => 'mati',
                'qty' => $oldStock,
                'hpp' => $hppBaru,
                'total_hpp' => $oldStock * $hppBaru,
            ]);

            $stok->stock = 0;
            $stok->save();

            DetailStockBarang::where('id_stock', $stok->id)->get()->each(function ($detail) {
                $detail->qty_out = $detail->qty_buy;
                $detail->qty_now = 0;
                $detail->save();
            });

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: 'App\Models\StockBarang',
                subjectId: $stok->id,
                event: 'Kosongkan Stok',
                properties: [
                    'changes' => [
                        'old' => ['stock' => $oldStock],
                        'new' => ['stock' => $stok->stock],
                    ]
                ],
                description: "Stok barang {$namaBarang} (ID {$request->id_barang}) di toko ID {$request->id_toko} dikosongkan.",
                userId: $request->user_id ?? null,
                message: filled($request->message) ? $request->message : '(Sistem) Stok barang dikosongkan.'
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Stok berhasil dikosongkan.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengosongkan stok.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function editStock(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'qty' => 'required|numeric|min:0',
            'user_id' => 'required|string',
            'message' => 'nullable|string',
            'status' => ['required', Rule::in(StatusStockBarang::values())],
        ]);

        $stok = StockBarang::find($request->id);
        $barang = Barang::find($stok?->id_barang);
        $namaBarang = $barang?->nama_barang ?? '-';

        if (!$stok) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data stok tidak ditemukan.',
            ], 404);
        }

        $oldStock = $stok->stock;
        $newStock = $request->qty;

        if ($newStock > $oldStock) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak diperbolehkan menambahkan stok. Anda hanya dapat mengurangi stok.',
            ], 422);
        }

        $diff = $oldStock - $newStock;

        try {
            DB::beginTransaction();

            $details = DetailStockBarang::where('id_stock', $stok->id)
                ->where('qty_now', '>', 0)
                ->orderBy('id', 'asc') // FIFO
                ->get();

            $sisaKurangi = $diff;
            $totalHpp = 0;

            foreach ($details as $detail) {
                if ($sisaKurangi <= 0) break;

                $bisaDikurang = min($detail->qty_now, $sisaKurangi);
                $maksOut = $detail->qty_buy - $detail->qty_out;
                $kurangi = min($bisaDikurang, $maksOut);

                if ($kurangi > 0) {
                    // Ambil harga_barang dari DetailPembelianBarang
                    $detailPembelian = DetailPembelianBarang::find($detail->id_detail_pembelian);
                    $hargaBarang = $detailPembelian?->harga_barang ?? 0;

                    // Tambahkan ke total HPP
                    $totalHpp += ($hargaBarang * $kurangi);

                    // Simpan untuk hitung rata-rata HPP
                    $totalHargaDiambil = ($totalHargaDiambil ?? 0) + ($hargaBarang * $kurangi);
                    $totalQtyDiambil = ($totalQtyDiambil ?? 0) + $kurangi;

                    // Update detail stock
                    $detail->qty_now -= $kurangi;
                    $detail->qty_out += $kurangi;
                    $detail->save();

                    $sisaKurangi -= $kurangi;
                }
            }

            $hppRataRata = ($totalQtyDiambil ?? 0) > 0
                ? ($totalHargaDiambil / $totalQtyDiambil)
                : null;

            // Update stok utama
            $stok->stock = $newStock;
            $stok->save();

            // Catat ke StockBarangBermasalah
            StockBarangBermasalah::create([
                'stock_barang_id' => $stok->id,
                'status' => $request->status,
                'qty' => $diff,
                'hpp' => $hppRataRata,   // rata-rata harga dari detail yang dipakai
                'total_hpp' => $totalHpp, // hasil FIFO akumulasi
            ]);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: 'App\Models\StockBarang',
                subjectId: $stok->id,
                event: 'Pengurangan Stok ' . ucfirst(strtolower($request->status)),
                properties: [
                    'changes' => [
                        'old' => ['stock' => $oldStock],
                        'new' => ['stock' => $stok->stock],
                    ]
                ],
                description: "Stok barang {$namaBarang} (ID {$stok->id_barang}) dikurangi dari {$oldStock} ke {$newStock}.",
                userId: $request->user_id,
                message: filled($request->message) ? $request->message : '(Sistem) Stok barang dikurangi.'
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Stok berhasil dikurangi.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui stok.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function getItem($id_barang)
    {
        $item = StockBarang::where('id_barang', $id_barang)->first();

        if ($item) {
            return response()->json([
                'nama_barang' => $item->nama_barang
            ]);
        } else {
            return response()->json(['error' => 'Item not found'], 404);
        }
    }

    public function create()
    {
        return view('master.stockbarang.create');
    }


    public function getStockDetails($id_barang)
    {
        $user = Auth::user();

        // Ambil data barang & stok utama
        $barang = Barang::find($id_barang);
        $stockBarang = StockBarang::where('id_barang', $id_barang)->first();
        $stockUtama = $stockBarang->stock ?? 0;

        // Ambil semua pembelian sukses
        $successfulDetails = DetailPembelianBarang::where('id_barang', $id_barang)->get();
        $totalHargaSuccess = $successfulDetails->sum('total_harga');
        $totalQtySuccess = $successfulDetails->sum('qty');

        $hppBaru = $totalQtySuccess > 0 ? $totalHargaSuccess / $totalQtySuccess : 0;

        // Hitung total stok toko lain
        $stokTokoLain = DB::table('detail_toko')->where('id_barang', $id_barang)->sum('qty');
        $totalStock = $stockUtama + $stokTokoLain;

        // Decode level harga dari JSON
        $level_harga = [];
        if ($barang && $barang->level_harga) {
            $decoded = json_decode($barang->level_harga, true);
            foreach ($decoded as $item) {
                if (strpos($item, ' : ') !== false) {
                    list($level_name, $level_value) = explode(' : ', $item);
                    $level_harga[$level_name] = $level_value;
                }
            }
        }

        // Jika user admin (level 1), kembalikan juga data per toko
        // if ($user->id_level == 1) {
            $tokoList = \App\Models\Toko::all()->sortByDesc(fn($tk) => $tk->id == $user->id_toko ? 1 : 0);
            $stokTokoLain = DB::table('detail_toko')
                ->select('id_toko', DB::raw('SUM(qty) as qty'))
                ->where('id_barang', $id_barang)
                ->groupBy('id_toko')
                ->pluck('qty', 'id_toko');

            $dataPerToko = $tokoList->map(function ($tk) use ($id_barang, $stockBarang, $stokTokoLain) {
                $stock = $tk->id == 1
                    ? $stockBarang?->stock ?? 0
                    : ($stokTokoLain[$tk->id] ?? 0);

                $levelHargaArray = json_decode($tk->id_level_harga, true);
                if (is_int($levelHargaArray)) $levelHargaArray = [$levelHargaArray];

                $levelHargaNama = [];
                if (is_array($levelHargaArray)) {
                    sort($levelHargaArray);
                    $map = \App\Models\LevelHarga::orderBy('id', 'asc')->get()->keyBy('id');
                    foreach ($levelHargaArray as $id) {
                        $levelHargaNama[] = $map[$id]->nama_level_harga ?? 'N/A';
                    }
                }

                return [
                    'nama_toko' => $tk->nama_toko,
                    'stock' => $stock,
                    'level_harga' => $levelHargaNama,
                ];
            })->values();

            return response()->json([
                'stock' => $totalStock,
                'hpp_awal' => $stockBarang->hpp_baru ?? 0,
                'hpp_baru' => $hppBaru,
                'total_harga_success' => $totalHargaSuccess,
                'total_qty_success' => $totalQtySuccess,
                'level_harga' => $level_harga,
                'per_toko' => $dataPerToko,
            ]);
        // }

        // Untuk non-admin (bukan level 1)
        // $stokTokoUser = DB::table('detail_toko')
        //     ->where('id_barang', $id_barang)
        //     ->where('id_toko', $user->id_toko)
        //     ->sum('qty');

        // $totalStockUser = $stokTokoUser;

        // return response()->json([
        //     'stock' => $totalStockUser,
        //     'hpp_awal' => $stockBarang->hpp_baru ?? 0,
        //     'hpp_baru' => $hppBaru,
        //     'total_harga_success' => $totalHargaSuccess,
        //     'total_qty_success' => $totalQtySuccess,
        //     'level_harga' => $level_harga,
        // ]);
    }

    public function getdetailbarang($id_barang)
    {
        $userTokoId = Auth::user()->id_toko;

        if ($userTokoId == 1) {
            $detail = DB::table('detail_stock as ds')
                ->leftJoin('detail_pembelian_barang as dpb', 'ds.id_detail_pembelian', '=', 'dpb.id')
                ->leftJoin('pembelian_barang as pb', 'dpb.id_pembelian_barang', '=', 'pb.id')
                ->leftJoin('barang as b', 'ds.id_barang', '=', 'b.id')
                ->select(
                    'ds.id',
                    'ds.id_barang',
                    'b.nama_barang',
                    'ds.qty_now',
                    'dpb.harga_barang',
                    'dpb.qrcode',
                    'pb.no_nota',
                    'pb.tgl_nota'
                )
                ->where('ds.id_barang', $id_barang)
                ->orderBy('ds.id', 'desc')
                ->get();

            if ($detail->isEmpty()) {
                return response()->json([
                    'status_code' => 404,
                    'errors' => true,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $mappedData = $detail->map(function ($item) {
                return [
                    'id' => $item->id,
                    'qty' => $item->qty_now ?? 0,
                    'harga' => 'Rp. ' . number_format($item->harga_barang ?? 0, 0, ',', '.'),
                    'qrcode' => $item->qrcode,
                    'no_nota' => $item->no_nota,
                    'tgl_nota' => $item->tgl_nota ? \Carbon\Carbon::parse($item->tgl_nota)->format('d/m/Y H:i:s') : null
                ];
            });
        } else {
            $detail_toko = DB::table('detail_toko as dt')
                ->leftJoin('detail_pembelian_barang as dpb', 'dt.qrcode', '=', 'dpb.qrcode')
                ->leftJoin('pembelian_barang as pb', 'dpb.id_pembelian_barang', '=', 'pb.id')
                ->leftJoin('barang as b', 'dt.id_barang', '=', 'b.id')
                ->leftJoin('toko as t', 'dt.id_toko', '=', 't.id')
                ->select(
                    'dt.id',
                    'dt.qty',
                    'dt.harga',
                    'dt.qrcode',
                    'pb.no_nota',
                    'pb.tgl_nota',
                    'b.nama_barang',
                    't.nama_toko'
                )
                ->where('dt.id_barang', $id_barang)
                ->where('dt.id_toko', $userTokoId)
                ->orderBy('dt.id', 'desc')
                ->get();

            if ($detail_toko->isEmpty()) {
                return response()->json([
                    'status_code' => 404,
                    'errors' => true,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $mappedData = $detail_toko->map(function ($item) {
                return [
                    'id' => $item->id,
                    'qty' => $item->qty,
                    'harga' => 'Rp. ' . number_format($item->harga, 0, ',', '.'),
                    'qrcode' => $item->qrcode,
                    'no_nota' => $item->no_nota,
                    'tgl_nota' => $item->tgl_nota ? \Carbon\Carbon::parse($item->tgl_nota)->format('d/m/Y H:i:s') : null
                ];
            });
        }

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Sukses'
        ], 200);
    }


    public function getHppBarang(Request $request)
    {
        $id_barang = $request->input('id_barang');
        $qty_request = $request->input('qty');
        $harga_request = $request->input('harga');

        // Ambil data dari tabel stock_barang
        $stockBarang = StockBarang::where('id_barang', $id_barang)->first();

        // Ambil total qty dari detail_toko
        $qtyDetailToko = DetailToko::where('id_barang', $id_barang)->sum('qty');

        if (!$stockBarang) {
            return response()->json(['error' => 'Barang tidak ditemukan di tabel stock_barang'], 404);
        }

        $stock = $stockBarang->stock;
        $hpp_lama = $stockBarang->hpp_baru;

        // Hitung HPP baru
        $totalQtyLama = $stock + $qtyDetailToko;
        $totalQtyBaru = $totalQtyLama + $qty_request;

        $totalHpp = ($totalQtyLama * $hpp_lama) + ($qty_request * $harga_request);
        $hpp_baru = $totalQtyBaru > 0 ? $totalHpp / $totalQtyBaru : 0;

        return response()->json([
            'hpp_baru' => $hpp_baru
        ]);
    }

    public function updateLevelHarga(Request $request)
    {
        $id_barang = $request->input('id_barang');

        try {
            DB::beginTransaction();

            $barang = Barang::findOrFail($id_barang);

            $levelNamas = $request->input('level_nama', []);
            $levelHargas = $request->input('level_harga', []);

            $levelHargaBarang = [];

            foreach ($levelHargas as $index => $hargaLevel) {
                $levelNama = $levelNamas[$index] ?? 'Level ' . ($index + 1);

                if (!is_null($hargaLevel)) {
                    $hargaLevel = str_replace('.', '', $hargaLevel);
                    $levelHargaBarang[] = "{$levelNama} : {$hargaLevel}";
                }
            }

            $barang->level_harga = json_encode($levelHargaBarang);
            $barang->save();

            DB::table('stock_barang')
                ->where('id_barang', $id_barang)
                ->update(['level_harga' => $barang->level_harga]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Level harga berhasil diperbarui',
                'data' => [
                    'id_barang' => $id_barang,
                    'level_harga' => $levelHargaBarang,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
