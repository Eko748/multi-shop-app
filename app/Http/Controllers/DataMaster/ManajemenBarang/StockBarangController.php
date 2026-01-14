<?php

namespace App\Http\Controllers\DataMaster\ManajemenBarang;

use App\Http\Controllers\Controller;
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
use App\Models\LevelHarga;
use App\Models\PembelianBarangDetail;
use App\Models\StockBarangBatch;

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

        $query = StockBarang::with(['barang', 'toko']);

        if (!empty($request['toko_id'])) {
            $query->where('toko_id', $request['toko_id']);
        }

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereHas('barang', function ($subquery) use ($searchTerm) {
                    $subquery->whereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]);
                });
            });
        }

        if ($request->has('startDate') && $request->has('endDate')) {
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $data = $query->paginate($meta['limit']);

        $paginationMeta = [
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'total_pages' => $data->lastPage()
        ];

        $mappedData = collect($data->items())->map(function ($item) {
            $barang = $item->barang;

            $decoded = json_decode($item->level_harga, true);
            $decoded = is_array($decoded) ? $decoded : [];

            $levelNames = LevelHarga::orderBy('id', 'asc')
                ->pluck('nama_level_harga')
                ->toArray();

            $level_harga = [];

            foreach ($decoded as $index => $harga) {

                $numeric = preg_replace('/[^0-9]/', '', $harga);
                $numeric = $numeric !== '' ? (int)$numeric : 0;

                $formatted = 'Rp ' . number_format($numeric, 0, ',', '.');

                $level_name = $levelNames[$index] ?? "Level " . ($index + 1);

                $level_harga[$level_name] = $formatted;
            }

            return [
                'id'          => $item->id,
                'id_barang'   => $barang->id ?? null,
                'nama_barang' => $barang->nama ?? null,
                'barcode'     => $barang->barcode ?? null,
                'hpp_baru'    => $item->hpp_baru,
                'stock'       => $item->stok ?? 0,
                'level_harga' => $level_harga,
            ];
        });

        if ($mappedData->isEmpty()) {
            return response()->json([
                'status_code' => 400,
                'errors' => true,
                'message' => 'Tidak ada data'
            ], 400);
        }

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
            'toko_id' => 'required|string',
            'user_id' => 'required|string',
            'message' => 'nullable|string'
        ]);

        $toko = Toko::where('id', $request->toko_id)->first();

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
                ->where('barang_id', $request->id_barang)
                ->first();
            $barang = Barang::find($request->id_barang);
            $namaBarang = $barang?->nama ?? '-';

            if (!$stok) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data stok tidak ditemukan.',
                ], 404);
            }

            $oldStock = $stok->stok;
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

        $barang = Barang::find($id_barang);
        $stockBarang = StockBarang::where('barang_id', $id_barang)->first();
        $stockUtama = $stockBarang->stok ?? 0;

        // Hitung HPP baru
        $detail = PembelianBarangDetail::where('barang_id', $id_barang)->get();
        $totalHargaSuccess = $detail->sum('subtotal');
        $totalQtySuccess = $detail->sum('qty');
        $hppBaru = $totalQtySuccess > 0 ? $totalHargaSuccess / $totalQtySuccess : 0;

        // TOTAL STOCK
        $totalStock = $stockUtama;

        // ================================
        // 1. LEVEL HARGA DARI STOCK BARANG
        // ================================
        $level_harga = [];

        if ($stockBarang && $stockBarang->level_harga) {

            $decoded = json_decode($stockBarang->level_harga, true);

            // Pastikan array valid
            if (is_array($decoded)) {

                // Ambil semua data level harga berurutan
                $allLevels = LevelHarga::orderBy('id', 'asc')->get();

                foreach ($allLevels as $index => $level) {

                    // Jika index array tersedia, ambil value-nya
                    if (isset($decoded[$index])) {

                        $value = preg_replace('/[^0-9]/', '', $decoded[$index]);

                        $level_harga[$level->nama_level_harga] = $value;
                    }
                }
            }
        }

        // Ambil semua level harga berurutan ASC
        $levelHargaMaster = LevelHarga::orderBy('id', 'asc')->get();

        // Decode harga dari StockBarang
        $decodedHarga = json_decode($stockBarang->level_harga ?? '[]', true);
        if (!is_array($decodedHarga)) $decodedHarga = [];

        // Buat mapping berdasarkan urutan LevelHarga
        $hargaMapping = [];
        foreach ($levelHargaMaster as $index => $lv) {
            $hargaMapping[$lv->id] = $decodedHarga[$index] ?? null;
        }

        $tokoList = Toko::all()->sortByDesc(fn($tk) => $tk->id == $user->toko_id ? 1 : 0);

        $dataPerToko = $tokoList->map(function ($tk) use ($stockBarang, $levelHargaMaster, $hargaMapping) {

            $stock = $tk->id == 1 ? ($stockBarang->stok ?? 0) : 0;

            // Decode id_level_harga aman
            $raw = $tk->level_harga;
            $array = json_decode($raw, true);

            if (!is_array($array)) {
                if (is_string($raw) && str_contains($raw, ',')) {
                    $array = array_map('intval', explode(',', $raw));
                } elseif (is_numeric($raw)) {
                    $array = [(int)$raw];
                } else {
                    $array = [];
                }
            }

            // Bangun string level harga
            $output = [];

            foreach ($array as $id) {

                $namaLevel = $levelHargaMaster->firstWhere('id', $id)->nama_level_harga ?? 'N/A';

                $harga = $hargaMapping[$id] ?? null;

                if ($harga !== null) {
                    $formatted = 'Rp ' . number_format((int)$harga, 0, ',', '.');
                    $output[] = "{$namaLevel} ({$formatted})";
                } else {
                    $output[] = "{$namaLevel} ()";
                }
            }

            return [
                'nama_toko' => $tk->nama,
                'stock' => $stock,
                'level_harga' => implode(', ', $output),
            ];
        });


        // ================================
        // 3. RETURN RESPONSE
        // ================================
        return response()->json([
            'stock' => $totalStock,
            'hpp_awal' => $stockBarang->hpp_baru ?? 0,
            'hpp_baru' => $hppBaru,
            'total_harga_success' => $totalHargaSuccess,
            'total_qty_success' => $totalQtySuccess,
            'level_harga' => $level_harga,   // Sudah berbentuk {"Level 1" : "Rp xx"}
            'per_toko' => $dataPerToko,
        ]);
    }

    public function getdetailbarang($id_barang)
    {
        $userTokoId = Auth::user()->toko_id;

        $detail = StockBarangBatch::whereHas('stockBarang', function ($q) use ($id_barang, $userTokoId) {
            $q->where('barang_id', $id_barang)
                ->where('toko_id', $userTokoId)->where('stok', '>', 0);
        })->get();

        if ($detail->isEmpty()) {
            return response()->json([
                'status_code' => 404,
                'errors' => true,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $mappedData = $detail->map(function ($item) {
            return [
                'id'        => $item->id,
                'qty'       => $item->qty_sisa ?? 0,
                'harga'     => 'Rp. ' . number_format($item->harga_beli ?? 0, 0, ',', '.'),
                'qrcode'    => $item->qrcode ?? null,
            ];
        });

        return response()->json([
            'data'        => $mappedData,
            'status_code' => 200,
            'errors'      => false,
            'message'     => 'Sukses'
        ], 200);
    }

    public function getHppBarang(Request $request)
    {
        $id_barang = $request->input('id_barang');
        $qty_request = $request->input('qty');
        $harga_request = $request->input('harga');

        $stockBarang = StockBarang::where('barang_id', $id_barang)->first();

        if (!$stockBarang) {
            return response()->json(['error' => 'Barang tidak ditemukan'], 404);
        }

        $stock = $stockBarang->stok;
        $hpp_lama = $stockBarang->hpp_baru;

        $totalQtyLama = $stock;
        $totalQtyBaru = $totalQtyLama + $qty_request;

        $totalHpp = ($totalQtyLama * $hpp_lama) + ($qty_request * $harga_request);
        $hpp_baru = $totalQtyBaru > 0 ? $totalHpp / $totalQtyBaru : 0;

        return response()->json([
            'hpp_baru' => $hpp_baru,
            'stok_lama' => $totalQtyLama,
            'stok_baru' => $totalQtyBaru
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

            $barang->save();

            StockBarang::where('barang_id', $id_barang)
                ->update(['level_harga' => json_encode($levelHargaBarang)]);

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
