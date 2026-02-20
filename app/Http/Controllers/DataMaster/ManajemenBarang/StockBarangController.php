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
use App\Helpers\LogAktivitasGenerate;
use App\Helpers\RupiahGenerate;
use App\Helpers\TextGenerate;
use App\Models\LevelHarga;
use App\Models\PembelianBarangDetail;
use App\Models\StockBarangBatch;
use App\Services\KasService;
use App\Traits\ApiResponse;

class StockBarangController extends Controller
{
    use ApiResponse;
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Stok Barang',
        ];
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[0]];

        return view('master.stockbarang.index', compact('menu'));
    }

    public function get(Request $request)
    {
        $meta['orderBy'] = $request->input('ascending', 0) ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = StockBarang::with(['barang', 'tokoGroup']);

        if (!empty($request['toko_id'])) {
            $query->whereHas('tokoGroup.toko', function ($q) use ($request) {
                $q->where('toko.id', $request['toko_id']);
            });
        }

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereHas('barang', function ($subquery) use ($searchTerm) {
                    $subquery->whereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]);
                });
            });
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
            $query->whereBetween('created_at', [$start_date, $end_date]);
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
            $tokoGroup = $item->tokoGroup;

            $decoded = json_decode($item->level_harga, true);
            $decoded = is_array($decoded) ? $decoded : [];

            $levelNames = LevelHarga::orderBy('id', 'asc')
                ->pluck('nama_level_harga')
                ->toArray();

            $level_harga = [];

            foreach ($decoded as $index => $harga) {

                $numeric = preg_replace('/[^0-9]/', '', $harga);
                $numeric = $numeric !== '' ? (int) $numeric : 0;

                $formatted = RupiahGenerate::build($numeric);

                $level_name = $levelNames[$index] ?? "Level " . ($index + 1);

                $level_harga[$level_name] = $formatted;
            }

            return [
                'id'                => $item->id,
                'id_barang'         => $barang->id ?? null,
                'nama_barang'       => TextGenerate::smartTail($barang->nama),
                'barcode'           => $barang->barcode ?? null,

                'toko_group_id'     => $tokoGroup->id ?? null,
                'nama_toko_group'   => $tokoGroup->nama ?? null,

                'hpp_baru'          => RupiahGenerate::build($item->hpp_baru),
                'stock'             => (int) ($item->stok ?? 0),
                'level_harga'       => $level_harga,
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

    public function refreshStock(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'id_barang' => 'required|integer',
            'pin' => 'required|integer',
            'toko_id' => 'required|integer',
            'user_id' => 'required|string',
            'message' => 'nullable|string',
        ]);

        $toko = Toko::find($request->toko_id);
        if (!$toko) {
            return $this->error(404, 'Data toko tidak ditemukan.');
        }

        if ((int) $toko->pin !== (int) $request->pin) {
            return $this->error(403, 'PIN yang Anda masukkan salah.');
        }

        DB::beginTransaction();
        try {
            $stok = StockBarang::lockForUpdate()->find($request->id);
            if (!$stok) {
                return $this->error(404, 'Data stok tidak ditemukan.');
            }

            $oldStock = $stok->stok;
            $totalHargaBeli = 0; // 🔑 total kerugian HPP

            // Ambil semua batch
            $batches = StockBarangBatch::where('stock_barang_id', $stok->id)
                ->lockForUpdate()
                ->get();

            foreach ($batches as $batch) {
                if ($batch->qty_sisa <= 0) continue;

                // hitung HPP batch
                $batchHargaBeli = $batch->qty_sisa * ($batch->harga_beli ?? 0);
                $totalHargaBeli += $batchHargaBeli;

                // Catat stok bermasalah PER BATCH
                StockBarangBermasalah::create([
                    'stock_barang_batch_id' => $batch->id,
                    'status' => 'mati',
                    'qty' => $batch->qty_sisa,
                ]);

                // Kosongkan batch
                $batch->qty_sisa = 0;
                $batch->save();
            }

            // Nolkan stok utama
            $stok->stok = 0;
            $stok->save();

            // 🔥 Update laba rugi (OUT)
            if ($totalHargaBeli > 0) {
                $tanggal = now();

                KasService::updateLabaRugi(
                    tokoId: $request->toko_id,
                    tahun: $tanggal->year,
                    bulan: $tanggal->month,
                    tipe: 'out',
                    nominal: $totalHargaBeli
                );
            }

            // Log aktivitas
            LogAktivitasGenerate::store(
                logName: $this->title[0] ?? 'Stok Barang',
                subjectType: StockBarang::class,
                subjectId: $stok->id,
                event: 'Kosongkan Stok',
                properties: [
                    'old' => ['stok' => $oldStock],
                    'new' => ['stok' => 0],
                ],
                description: "Stok barang ID {$request->id_barang} dikosongkan.",
                userId: $request->user_id,
                message: $request->message ?? '(Sistem) Stok barang dikosongkan.'
            );

            DB::commit();
            return $this->success(null, 200, 'Stok berhasil dikosongkan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error(500, $e->getMessage());
        }
    }

    public function updateStock(Request $request)
    {
        $request->validate([
            'toko_id' => 'required|integer|exists:toko,id',
            'user_id' => 'required|string',
            'message' => 'nullable|string',
            'reductions' => 'required|array|min:1',
            'reductions.*.stock_barang_batch_id' => 'required|integer|exists:stock_barang_batch,id',
            'reductions.*.qty' => 'required|integer|min:1',
            'reductions.*.status' => ['required', Rule::in(StatusStockBarang::values())],
        ]);

        DB::beginTransaction();

        try {
            $userId = $request->user_id;
            $tokoId = $request->toko_id;
            $message = $request->message ?? '(Sistem) Stok barang dikurangi.';

            $stokPengurangan = []; // per stock_barang_id
            $totalHargaBeli = 0;         // 🔥 total beban HPP

            foreach ($request->reductions as $reduction) {

                $batch = StockBarangBatch::lockForUpdate()
                    ->find($reduction['stock_barang_batch_id']);

                if (!$batch || $batch->qty_sisa <= 0) continue;

                $qtyKurangi = min($reduction['qty'], $batch->qty_sisa);
                if ($qtyKurangi <= 0) continue;

                $qtyOld = $batch->qty_sisa;

                // 🔥 HITUNG TOTAL HPP
                $totalHargaBeli += ($batch->harga_beli * $qtyKurangi);

                // kurangi batch
                $batch->qty_sisa -= $qtyKurangi;
                $batch->save();

                // kumpulkan total per stok
                $stokPengurangan[$batch->stock_barang_id] =
                    ($stokPengurangan[$batch->stock_barang_id] ?? 0) + $qtyKurangi;

                // stok bermasalah
                StockBarangBermasalah::create([
                    'stock_barang_batch_id' => $batch->id,
                    'status' => $reduction['status'],
                    'qty' => $qtyKurangi,
                ]);

                // log
                $stok = StockBarang::find($batch->stock_barang_id);
                $barang = Barang::find($stok?->id_barang);
                $namaBarang = $barang?->nama_barang ?? '-';

                LogAktivitasGenerate::store(
                    logName: $this->title[0] ?? 'Stok Barang',
                    subjectType: StockBarang::class,
                    subjectId: $stok->id,
                    event: 'Pengurangan Stok ' . ucfirst(strtolower($reduction['status'])),
                    properties: [
                        'changes' => [
                            'old' => ['qty_sisa' => $qtyOld],
                            'new' => ['qty_sisa' => $batch->qty_sisa],
                        ]
                    ],
                    description: "Stok barang {$namaBarang} (Batch ID {$batch->id}) dikurangi {$qtyKurangi}.",
                    userId: $userId,
                    message: $message
                );
            }

            /* ============================
        | UPDATE STOK UTAMA
        ============================ */
            foreach ($stokPengurangan as $stockBarangId => $qtyTotal) {

                $stok = StockBarang::lockForUpdate()->find($stockBarangId);
                if (!$stok) {
                    throw new \Exception("Stok barang ID {$stockBarangId} tidak ditemukan.");
                }

                if ($qtyTotal > $stok->stok) {
                    throw new \Exception("Pengurangan melebihi stok tersedia (Stock ID {$stockBarangId}).");
                }

                $stok->stok -= $qtyTotal;
                $stok->save();
            }

            /* ============================
        | 🔥 UPDATE LABA RUGI
        ============================ */
            if ($totalHargaBeli > 0) {
                $fTanggal = now();

                KasService::updateLabaRugi(
                    tokoId: $tokoId,
                    tahun: $fTanggal->year,
                    bulan: $fTanggal->month,
                    tipe: 'out',
                    nominal: $totalHargaBeli
                );
            }

            DB::commit();
            return $this->success(null, 200, 'Stok berhasil dikurangi.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error(500, $e->getMessage());
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

    public function getDetail(Request $request)
    {
        $stockBarang = StockBarang::where('barang_id', $request->barang_id)->first();
        $stockUtama = $stockBarang->stok ?? 0;

        // ================================
        // HITUNG HPP BARU
        // ================================
        $detail = PembelianBarangDetail::where('barang_id', $request->barang_id)->get();
        $totalHargaSuccess = $detail->sum('subtotal');
        $totalQtySuccess = $detail->sum('qty');
        $hppBaru = $totalQtySuccess > 0
            ? round($totalHargaSuccess / $totalQtySuccess, 2)
            : 0.00;

        // ================================
        // LEVEL HARGA (ARRAY NUMERIC)
        // ================================
        $level_harga = [];

        if ($stockBarang && $stockBarang->level_harga) {

            $raw = $stockBarang->level_harga;

            if (is_array($raw)) {
                $level_harga = array_values(array_map('intval', $raw));
            } elseif (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $level_harga = array_values(array_map('intval', $decoded));
                }
            }
        }

        // ================================
        // MASTER LEVEL HARGA
        // ================================
        $levelHargaMaster = LevelHarga::orderBy('id', 'asc')->get();

        // ================================
        // GABUNG → KEY VALUE
        // ================================
        $levelHargaKeyValue = [];

        foreach ($levelHargaMaster as $index => $lv) {
            if (isset($level_harga[$index])) {
                $levelHargaKeyValue[$lv->nama_level_harga] = $level_harga[$index];
            }
        }

        // ================================
        // DATA PER TOKO
        // ================================
        $tokoList = Toko::all()->sortByDesc(fn($tk) => $tk->id == $request->toko_id ? 1 : 0);

        // $dataPerToko = $tokoList->map(function ($tk) use ($stockBarang, $levelHargaMaster, $hargaMapping) {

        //     $stock = $tk->id == 1 ? ($stockBarang->stok ?? 0) : 0;

        //     // Decode level harga toko (aman)
        //     $raw = $tk->level_harga;
        //     $array = json_decode($raw, true);

        //     if (!is_array($array)) {
        //         if (is_string($raw) && str_contains($raw, ',')) {
        //             $array = array_map('intval', explode(',', $raw));
        //         } elseif (is_numeric($raw)) {
        //             $array = [(int) $raw];
        //         } else {
        //             $array = [];
        //         }
        //     }

        //     // Bangun output string
        //     $output = [];

        //     foreach ($array as $id) {

        //         $namaLevel = $levelHargaMaster
        //             ->firstWhere('id', $id)
        //             ->nama_level_harga ?? 'N/A';

        //         $harga = $hargaMapping[$id] ?? null;

        //         if ($harga !== null) {
        //             $formatted = 'Rp ' . number_format($harga, 0, ',', '.');
        //             $output[] = "{$namaLevel} ({$formatted})";
        //         }
        //     }

        //     return [
        //         'nama_toko'   => $tk->nama,
        //         'stock'       => $stock,
        //         'level_harga' => implode(', ', $output),
        //     ];
        // });

        // ================================
        // RESPONSE
        // ================================
        $data = [
            'id'                  => $stockBarang->id,
            'stock'               => $stockUtama,
            'hpp_awal'            => $stockBarang ? (float) $stockBarang->hpp_baru : 0.00,
            'hpp_baru'            => $hppBaru,
            'total_harga_success' => $totalHargaSuccess,
            'total_qty_success'   => $totalQtySuccess,
            'level_harga'         => $levelHargaKeyValue, // ✅ KEY VALUE
        ];

        return $this->success($data, 200, 'Data berhasil diambil!');
    }

    public function getLevelHarga(Request $request)
    {
        $stockBarang = StockBarang::where('barang_id', $request->barang_id)->first();
        $stockUtama = $stockBarang->stok ?? 0;

        // ================================
        // HITUNG HPP BARU
        // ================================
        $detail = PembelianBarangDetail::where('barang_id', $request->barang_id)->get();
        $totalHargaSuccess = $detail->sum('subtotal');
        $totalQtySuccess = $detail->sum('qty');
        $hppBaru = $totalQtySuccess > 0
            ? round($totalHargaSuccess / $totalQtySuccess, 2)
            : 0.00;

        // ================================
        // LEVEL HARGA (MURNI ARRAY NUMERIC)
        // ================================
        $level_harga = [];

        if ($stockBarang && $stockBarang->level_harga) {

            $raw = $stockBarang->level_harga;

            if (is_array($raw)) {
                // Sudah array
                $level_harga = array_values(array_map('intval', $raw));
            } elseif (is_string($raw)) {
                // Masih JSON string
                $decoded = json_decode($raw, true);

                if (is_array($decoded)) {
                    $level_harga = array_values(array_map('intval', $decoded));
                }
            }
        }


        // ================================
        // MASTER LEVEL HARGA
        // ================================
        $levelHargaMaster = LevelHarga::orderBy('id', 'asc')->get();

        // Mapping harga berdasarkan urutan level
        $hargaMapping = [];
        foreach ($levelHargaMaster as $index => $lv) {
            $hargaMapping[$lv->id] = $level_harga[$index] ?? null;
        }

        // ================================
        // DATA PER TOKO
        // ================================
        $tokoList = Toko::all()->sortByDesc(fn($tk) => $tk->id == $request->toko_id ? 1 : 0);

        // $dataPerToko = $tokoList->map(function ($tk) use ($stockBarang, $levelHargaMaster, $hargaMapping) {

        //     $stock = $tk->id == 1 ? ($stockBarang->stok ?? 0) : 0;

        //     // Decode level harga toko (aman)
        //     $raw = $tk->level_harga;
        //     $array = json_decode($raw, true);

        //     if (!is_array($array)) {
        //         if (is_string($raw) && str_contains($raw, ',')) {
        //             $array = array_map('intval', explode(',', $raw));
        //         } elseif (is_numeric($raw)) {
        //             $array = [(int) $raw];
        //         } else {
        //             $array = [];
        //         }
        //     }

        //     // Bangun output string
        //     $output = [];

        //     foreach ($array as $id) {

        //         $namaLevel = $levelHargaMaster
        //             ->firstWhere('id', $id)
        //             ->nama_level_harga ?? 'N/A';

        //         $harga = $hargaMapping[$id] ?? null;

        //         if ($harga !== null) {
        //             $formatted = 'Rp ' . number_format($harga, 0, ',', '.');
        //             $output[] = "{$namaLevel} ({$formatted})";
        //         }
        //     }

        //     return [
        //         'nama_toko'   => $tk->nama,
        //         'stock'       => $stock,
        //         'level_harga' => implode(', ', $output),
        //     ];
        // });

        // ================================
        // RESPONSE
        // ================================
        $data = [
            'stock'                => $stockUtama,
            'hpp_awal'             => $stockBarang->hpp_awal ?? 0,
            'hpp_baru'             => $stockBarang->hpp_baru ?? 0,
            'total_harga_success'  => $totalHargaSuccess,
            'total_qty_success'    => $totalQtySuccess,
            'level_harga'          => $level_harga, // ✅ PURE ARRAY
            // 'per_toko'             => $dataPerToko,
        ];

        return $this->success($data, 200, 'Data berhasil diambil!');
    }

    public function getBarang(Request $request)
    {
        $userTokoId = Auth::user()->toko_id;

        $detail = StockBarangBatch::whereHas('stockBarang', function ($q) use ($request, $userTokoId) {
            $q->where('barang_id', $request->barang_id)
                ->where('toko_id', $userTokoId)->where('stok', '>', 0);
        })->orderByDesc('created_at')->get();

        if ($detail->isEmpty()) {
            return response()->json([
                'status_code' => 404,
                'errors' => true,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $data = $detail->map(function ($item) {
            return [
                'id'        => $item->id,
                'qty'       => $item->qty_sisa ?? 0,
                'harga'     => RupiahGenerate::build($item->harga_beli),
                'hpp_awal'     => RupiahGenerate::build($item->hpp_awal),
                'hpp_baru'     => RupiahGenerate::build($item->hpp_baru),
                'qrcode'    => $item->qrcode ?? null,
                'created_at' => $item->created_at
                    ? $item->created_at->format('Y-m-d H:i:s')
                    : null,
            ];
        });

        return $this->success($data, 200, 'Data berhasil diambil!');
    }

    public function getHpp(Request $request)
    {
        $id_barang = $request->input('barang_id');
        $qty_request = $request->input('qty');
        $harga_request = $request->input('harga');

        $stockBarang = StockBarang::where('barang_id', $id_barang)->where('toko_group_id', $request->toko_group_id)->first();

        if (!$stockBarang) {
            return response()->json([
                'hpp_lama'   => (float) $harga_request,
                'hpp_baru'   => (float) $harga_request,
                'stok_lama'  => (int) $qty_request,
                'stok_baru'  => (int) $qty_request
            ]);
        }

        $stock = $stockBarang->stok;
        $hpp_lama = $stockBarang->hpp_baru;

        $totalQtyLama = $stock;
        $totalQtyBaru = $totalQtyLama + $qty_request;

        $totalHpp = ($totalQtyLama * $hpp_lama) + ($qty_request * $harga_request);
        $hpp_baru = $totalQtyBaru > 0
            ? round($totalHpp / $totalQtyBaru, 2)
            : 0.00;

        return response()->json([
            'hpp_lama'   => (float) $hpp_lama,
            'hpp_baru'   => (float) $hpp_baru,
            'stok_lama'  => (int) $totalQtyLama,
            'stok_baru'  => (int) $totalQtyBaru
        ]);
    }

    public function updateHarga(Request $request)
    {
        $request->validate([
            'stock_barang_id' => 'required|integer|exists:stock_barang,id',
            'level_harga'     => 'required|array|min:1',
            'level_harga.*'   => 'required',
        ]);

        try {
            DB::beginTransaction();

            // 🔥 NORMALISASI → PASTI ANGKA
            $levelHarga = array_map(function ($harga) {
                return (int) preg_replace('/\D/', '', $harga);
            }, $request->level_harga);

            // 🔥 PAKSA JADI STRING DENGAN PETIK
            // hasil: "[33000,43000,45000]"
            $levelHargaString = '"' . json_encode($levelHarga) . '"';

            StockBarang::where('id', $request->stock_barang_id)
                ->update([
                    'level_harga' => $levelHargaString,
                ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Level harga berhasil diperbarui',
                'data'    => $levelHargaString,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
