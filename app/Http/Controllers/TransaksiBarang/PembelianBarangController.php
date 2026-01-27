<?php

namespace App\Http\Controllers\TransaksiBarang;

use App\Helpers\FormatHarga;
use App\Helpers\KasJenisBarangGenerate;
use App\Helpers\LogAktivitasGenerate;
use App\Helpers\QrGenerator;
use App\Helpers\RupiahGenerate;
use App\Http\Controllers\Controller;
use App\Imports\PembelianBarangImport;
use App\Models\Barang;
use App\Models\PembelianBarangDetail;
use App\Models\PembelianBarangDetailTemp;
use App\Models\DetailStockBarang;
use App\Models\Hutang;
use App\Models\Kas;
use App\Models\LevelHarga;
use App\Models\PembelianBarang;
use App\Models\StockBarang;
use App\Models\StockBarangBatch;
use App\Models\Supplier;
use App\Services\KasService;
use App\Services\TransaksiBarang\PembelianBarangService;
use App\Traits\{ApiResponse, HasFilter};
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;

class PembelianBarangController extends Controller
{
    use ApiResponse, HasFilter;

    private array $menu = [];
    protected $service;

    public function __construct(PembelianBarangService $service)
    {
        $this->menu;
        $this->title = [
            'Data Pembelian Barang',
            'Detail Data'
        ];
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $menu = [$this->title[0], $this->label[1]];
        $LevelHarga = LevelHarga::all();
        return view('transaksi.pembelianbarang.index', compact('menu', 'LevelHarga'));
    }

    public function get(Request $request)
    {
        try {
            $filter = $this->makeFilter(
                $request,
                30,
                [
                    'toko_id' => $request->input('toko_id'),
                    'nota' => $request->input('nota'),
                ]
            );
            $data = $this->service->getAll($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function getTemporary(Request $request)
    {
        try {
            $filter = $this->makeFilter(
                $request,
                30,
                [
                    'toko_id' => $request->input('toko_id'),
                    'id' => $request->input('pembelian_barang_id'),
                ]
            );
            $data = $this->service->getDetail($filter);

            return $this->success($data, 200, 'Berhasil');
        } catch (Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function create()
    {
        $menu = [$this->title[0], $this->label[1], $this->title[1]];
        $barang = Barang::all();
        $suppliers = Supplier::all();
        $LevelHarga = LevelHarga::all();

        return view('transaksi.pembelianbarang.create', compact('menu', 'suppliers', 'barang', 'LevelHarga'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_supplier' => 'required|exists:supplier,id',
            'tgl_nota'    => 'required|date',
            'no_nota'     => 'required|string',
            'tipe'        => 'required|in:cash,hutang',
        ], [
            'no_nota.unique' => 'Nomor Nota sudah digunakan!',
            'tipe.in' => 'Tipe hanya boleh berisi cash atau hutang.',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();

            // pastikan jam diisi jika kosong
            $tglNota = Carbon::parse($request->tgl_nota);
            if ($tglNota->format('H:i:s') === '00:00:00') {
                $tglNota->setTimeFromTimeString(Carbon::now()->format('H:i:s'));
            }

            // simpan data pembelian
            $pembelian = PembelianBarang::create([
                'id_supplier' => $request->id_supplier,
                'id_users'    => $user->id,
                'no_nota'     => $request->no_nota,
                'tgl_nota'    => $tglNota,
                'tipe'        => $request->tipe,
            ]);

            DB::commit();

            return response()->json([
                'status'         => 'success',
                'no_nota'        => $pembelian->no_nota,
                'nama_supplier'  => $pembelian->supplier->nama_supplier ?? '-',
                'tgl_nota'       => Carbon::parse($pembelian->tgl_nota)->format('Y-m-d H:i:s'),
                'id_pembelian'   => $pembelian->id,
                'tipe'           => $pembelian->tipe,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function detail($id)
    {
        $menu = [$this->title[0], $this->label[1], $this->title[1]];

        return view('transaksi.pembelianbarang.edit', compact('menu'));
    }

    public function getDetailPembelian(Request $request)
    {
        $id = $request->input('id_pembelian');

        if (!$id) {
            return response()->json([
                'status' => 'error',
                'errors' => true,
                'status_code' => 400,
                'message' => 'ID Pembelian tidak ditemukan',
            ], 400);
        }

        $limit = ($request->has('limit') && $request->limit <= 300) ? (int)$request->limit : 50;
        $searchTerm = strtolower(trim($request->input('search', '')));

        $pembelian = PembelianBarang::with('supplier:id,nama_supplier')->find($id);

        if (!$pembelian) {
            return response()->json([
                'status' => 'error',
                'errors' => true,
                'status_code' => 404,
                'message' => 'Pembelian tidak ditemukan',
            ], 404);
        }

        $detailQuery = DetailPembelianBarang::select('id', 'id_pembelian_barang', 'id_barang', 'qty', 'harga_barang', 'total_harga', 'status', 'qrcode', 'qrcode_path')
            ->with([
                'barang:id,nama_barang',
                'detailStock:id,id_detail_pembelian,qty_out,qty_now'
            ])
            ->where('id_pembelian_barang', $id);

        if (!empty($searchTerm)) {
            $detailQuery->whereHas('barang', function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(nama_barang) LIKE ?', ["%{$searchTerm}%"]);
            });
        }

        $totalHargaSemua = DetailPembelianBarang::where('id_pembelian_barang', $id)->sum('total_harga');

        $paginated = $detailQuery->paginate($limit);
        $detailItems = $paginated->items();

        $mappedDetails = collect($detailItems)->map(function ($item) {
            return [
                'id' => $item->id,
                'qrcode' => $item->qrcode,
                'qrcode_path' => $item->qrcode_path,
                'nama_barang' => $item->barang->nama_barang ?? '-',
                'qty' => $item->qty,
                'harga_barang' => $item->harga_barang,
                'total_harga' => $item->total_harga,
                'status' => $item->status,
                'qty_out' => $item->detailStock->qty_out ?? 0,
                'qty_now' => $item->detailStock->qty_now ?? 0,
            ];
        });

        return response()->json([
            'data' => [
                'no_nota' => $pembelian->no_nota,
                'nama_supplier' => $pembelian->supplier->nama_supplier ?? '-',
                'tgl_nota' => Carbon::parse($pembelian->tgl_nota)->format('Y-m-d'),
                'detail' => $mappedDetails,
                'sub_total' => $mappedDetails->sum('total_harga'),
                'total' => $totalHargaSemua,
            ],
            'status_code' => 200,
            'errors' => false,
            'message' => 'Sukses',
            'pagination' => [
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'total_pages' => $paginated->lastPage(),
            ],
        ]);
    }

    public function getStock($id_barang)
    {
        $stock = StockBarang::where('id_barang', $id_barang)->first();

        $barang = Barang::where('id', $id_barang)->first();

        $detail = DetailPembelianBarang::where('id_barang', $id_barang)->get();

        $totalHargaSuccess = $detail->sum('total_harga');
        $totalQtySuccess = $detail->sum('qty');

        // Hitung HPP baru
        if ($totalQtySuccess > 0) {
            $hppBaru = $totalHargaSuccess / $totalQtySuccess;
        } else {
            $hppBaru = 0;
        }

        $level_harga = [];
        if ($barang && $barang->level_harga) {
            $decoded_level_harga = json_decode($stock->level_harga, true);
            foreach ($decoded_level_harga as $item) {
                list($level_name, $level_value) = explode(' : ', $item);
                $level_harga[$level_name] = $level_value;
            }
        }

        return response()->json([
            'stock' => $stock->stock ?? 0,
            'hpp_awal' => $stock->hpp_awal ?? 0,
            'hpp_baru' => $hppBaru,
            'level_harga' => $level_harga,
        ]);
    }

    public function update(Request $request)
    {
        $id = $request->id;

        $rules = [
            'toko_group_id'  => 'required|integer|exists:toko_group,id',
            'toko_id'  => 'required|integer|exists:toko,id',
            'created_by' => 'required|integer',
            'id' => 'required|integer',
            'nota' => 'required|string',
            'tipe' => 'required|string',
            'supplier_id' => 'required|integer',
            'tanggal' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.id_barang' => 'required|integer',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.harga_barang' => 'required|numeric|min:0',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak lengkap, pastikan semua input diisi.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();
            $pembelian = PembelianBarang::findOrFail($id);
            $totalItem = 0;
            $totalNilai = 0;
            $counter = 1;

            foreach ($request->items as $index => $item) {
                $id_barang = $item['id_barang'];
                $qty = $item['qty'];
                $harga_barang = $item['harga_barang'];
                $levelHargaFromFront = $item['level_harga'] ?? [];

                $stockBarang = StockBarang::firstOrNew([
                    'barang_id'     => $id_barang,
                    'toko_group_id' => $request->toko_group_id,
                ]);

                $stock_sebelum = $stockBarang->stok ?? 0;

                // Ambil batch terakhir untuk mendapatkan HPP terakhir yang benar
                $lastBatch = null;
                if ($stockBarang->id) {
                    $lastBatch = StockBarangBatch::where('stock_barang_id', $stockBarang->id)
                        ->orderBy('id', 'desc')
                        ->first();
                }

                // HPP sebelum dihitung ulang
                if ($lastBatch) {
                    // Ada batch sebelumnya → pakai hpp_baru batch terakhir
                    $hpp_sebelum = $lastBatch->hpp_baru;
                } else {
                    // Batch pertama → pakai harga pembelian
                    $hpp_sebelum = $harga_barang;
                }

                // Simpan untuk batch (ini yang dimasukkan ke batch sebagai hpp_awal)
                $hpp_awal = $hpp_sebelum;

                // Hitung nilai total sebelum
                $nilai_total_sebelum = $stock_sebelum * $hpp_sebelum;

                // Nilai pembelian sekarang
                $nilai_pembelian_baru = $qty * $harga_barang;

                // Total stok setelah transaksi
                $total_stok_baru = $stock_sebelum + $qty;

                // Hitung HPP baru (Moving Average)
                $hpp_baru = $total_stok_baru > 0
                    ? ($nilai_total_sebelum + $nilai_pembelian_baru) / $total_stok_baru
                    : $hpp_sebelum;

                // Simpan ke StockBarang
                $stockBarang->stok = $total_stok_baru;
                $stockBarang->hpp_awal = $hpp_awal;      // nilai sebelum dihitung ulang
                $stockBarang->hpp_baru = $hpp_baru;      // hasil akhir update
                $stockBarang->toko_group_id = $request->toko_group_id;
                $stockBarang->level_harga = json_encode($levelHargaFromFront);
                $stockBarang->save();

                $batch = StockBarangBatch::create([
                    'toko_id' => $request->toko_id,
                    'qrcode' => QrGenerator::generate()['value'],
                    'stock_barang_id' => $stockBarang->id,
                    'qty_masuk' => $qty,
                    'qty_sisa' => $qty,
                    'harga_beli' => $harga_barang,
                    'hpp_awal' => $hpp_awal,
                    'hpp_baru' => $hpp_baru,
                    'supplier_id' => $pembelian->supplier_id,
                ]);

                $detail = PembelianBarangDetail::updateOrCreate(
                    [
                        'pembelian_barang_id' => $pembelian->id,
                        'barang_id' => $id_barang,
                        'stock_barang_batch_id' => $batch->id,
                    ],
                    [
                        'qty' => $qty,
                        'harga_beli' => $harga_barang,
                        'subtotal' => $qty * $harga_barang,
                    ]
                );

                $detail->save();

                $totalItem += $detail->qty;
                $totalNilai += $detail->subtotal;

                $counter++;
            }

            $limitTotal = Kas::find($pembelian->kas_id);

            if ($totalNilai > (float) $limitTotal->saldo && $pembelian->tipe == 'cash') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Total nilai pembelian melebihi sumber dana yang dipilih.'
                ], 422);
            }

            $pembelian->status = 'success';
            $pembelian->qty = $totalItem;
            $pembelian->total = $totalNilai;
            $pembelian->verified_by = $request->created_by;
            $pembelian->verified_at = now();

            if ($pembelian->tipe == 'cash') {
                $pembelian->status = 'success';
            } elseif ($pembelian->tipe == 'hutang') {
                $pembelian->status = 'completed_debt';
                $nominal = RupiahGenerate::build($pembelian->total_nilai);

                $firstDetail = PembelianBarangDetail::where('pembelian_barang_id', $pembelian->id)
                    ->first();

                $kasJenisBarang = $firstDetail && $firstDetail->barang
                    ? $firstDetail->barang->id_jenis_barang
                    : null;

                $hutang  = Hutang::create([
                    'id_toko' => $request->toko_id,
                    'toko_id' => $request->toko_id,
                    'hutang_tipe_id' => 1,
                    'keterangan' => "Hutang Pembelian Barang Nota {$pembelian->no_nota}",
                    'nominal' => $pembelian->total_nilai,
                    'status' => false,
                    'jangka' => 2,
                    'tanggal' => $pembelian->tgl_nota,
                    'label' => $pembelian->label,
                    'kas_jenis_barang' => $kasJenisBarang,
                ]);

                $kas    = KasJenisBarangGenerate::labelForKas($hutang);

                $description = "Hutang ditambahkan pada {$kas} senilai Rp {$nominal} (ID {$hutang->id})";

                LogAktivitasGenerate::store(
                    logName: 'Hutang',
                    subjectType: Hutang::class,
                    subjectId: $hutang->id,
                    event: 'Tambah Data',
                    properties: [
                        'changes' => [
                            'new' => [
                                'nominal' => $hutang->nominal,
                                'tanggal' => $hutang->tanggal,
                                'kas' => $kas,
                            ],
                        ]
                    ],
                    description: $description,
                    userId: $request->created_by,
                    message: filled($request->keterangan)
                        ? $request->keterangan
                        : '(Sistem) Hutang dibuat.'
                );
            }

            $pembelian->save();

            PembelianBarangDetailTemp::where('pembelian_barang_id', $pembelian->id)
                ->delete();

            $suplier = Supplier::where('id', $pembelian->supplier_id)->first();

            KasService::out(
                toko_id: $pembelian->kas->toko_id,
                jenis_barang_id: $pembelian->kas->jenis_barang_id,
                tipe_kas: $pembelian->kas->tipe_kas,
                total_nominal: $totalNilai,
                item: $pembelian->kas->tipe_kas,
                kategori: 'Pembelian Barang',
                keterangan: $suplier->nama,
                sumber: $pembelian,
                tanggal: $request->tanggal,
                laba: false
            );

            DB::commit();

            return $this->success(null, 201, "Pembelian Barang Nota {$pembelian->nota} disimpan.");
        } catch (\Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function editDetailPembelianBarang(Request $request)
    {
        $request->validate([
            'qty' => 'required|numeric|min:1',
            'harga_barang' => 'required|numeric|min:0',
        ]);

        $id = $request->id;

        try {
            DB::beginTransaction();

            $detail = DetailPembelianBarang::findOrFail($id);
            $barang = Barang::findOrFail($detail->id_barang);
            $pembelian = PembelianBarang::findOrFail($detail->id_pembelian_barang);
            $id_barang = $barang->id;
            $id_supplier = $detail->id_supplier;

            // Backup old values
            $oldQty = $detail->qty;
            $oldHarga = $detail->harga_barang;
            $oldTotalItem = $pembelian->total_item;

            $stockBarang = StockBarang::firstOrNew(['id_barang' => $id_barang]);
            $oldStock = $stockBarang->stock;

            // Update Detail Pembelian
            $detail->qty = $request->qty;
            $detail->harga_barang = $request->harga_barang;
            $detail->total_harga = $request->qty * $request->harga_barang;
            $detail->save();

            // Recalculate stock and HPP
            $hpp_awal = $stockBarang->hpp_baru ?: $stockBarang->hpp_awal ?: $oldHarga;
            $stock_awal = $stockBarang->stock ?: 0;

            $qty_detail_toko = DB::table('detail_toko')
                ->where('id_barang', $id_barang)
                ->sum('qty');

            $total_stock_lama = $stock_awal + $qty_detail_toko - $oldQty;

            $nilai_total_lama = $total_stock_lama * $hpp_awal;
            $nilai_pembelian_baru = $request->qty * $request->harga_barang;
            $total_qty_baru = $total_stock_lama + $request->qty;

            $hpp_baru = $request->harga_barang;

            $stockBarang->stock = $total_stock_lama + $request->qty;
            $stockBarang->hpp_awal = $hpp_awal;
            $stockBarang->hpp_baru = $hpp_baru;
            $stockBarang->nilai_total = $hpp_baru * $stockBarang->stock;
            $stockBarang->nama_barang = $barang->nama_barang;
            $stockBarang->save();

            // Update Detail Stock Barang jika ada
            $detailStock = DetailStockBarang::where('id_detail_pembelian', $detail->id)->first();
            if ($detailStock) {
                $qtyOut = $detailStock->qty_out ?? 0;

                if ($qtyOut > 0 && $request->qty < $qtyOut) {
                    return response()->json([
                        'success' => false,
                        'message' => "Qty tidak boleh kurang dari jumlah yang sudah keluar ({$qtyOut}).",
                    ], 422);
                }

                $detailStock->qty_buy = $request->qty;
                $detailStock->qty_now = $request->qty - $qtyOut;
                $detailStock->save();
            }

            //  total pembelian
            $totalItemBaru = DetailPembelianBarang::where('id_pembelian_barang', $pembelian->id)->sum('qty');
            $totalNilaiBaru = DetailPembelianBarang::where('id_pembelian_barang', $pembelian->id)->sum('total_harga');

            $pembelian->total_item = $totalItemBaru;
            $pembelian->total_nilai = $totalNilaiBaru;
            $pembelian->save();

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: 'App\Models\DetailPembelianBarang',
                subjectId: $detail->id,
                event: 'Edit Detail Pembelian Barang',
                properties: [
                    'changes' => [
                        'old' => [
                            'stock_keseluruhan' => $oldStock,
                            'total_item' => $oldTotalItem,
                            'qty_pembelian' => $oldQty,
                            'harga_barang' => $oldHarga,
                        ],
                        'new' => [
                            'stock_keseluruhan' => $stockBarang->stock,
                            'total_item' => $pembelian->total_item,
                            'qty_pembelian' => $detail->qty,
                            'harga_barang' => $detail->harga_barang,
                        ],
                    ],
                ],
                description: "Detail pembelian ID {$detail->id} untuk barang {$barang->nama_barang} (ID {$barang->id}) pada pembelian ID {$pembelian->id} diperbarui.",
                userId: $request->user_id ?? null,
                message: $request->message ?? '(Sistem) Perubahan data detail pembelian barang.'
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Detail pembelian barang berhasil diperbarui.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui detail pembelian. ' . $e->getMessage(),
            ], 500);
        }
    }

    public function delete($id)
    {
        DB::beginTransaction();

        try {
            $pembelian = PembelianBarang::findOrFail($id);

            $pembelian->detail()->delete();

            $pembelian->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Success to delete pembelian barang. ']);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['success' => false, 'message' => 'Failed to delete pembelian barang. ' . $e->getMessage()]);
        }
    }

    public function postTemp(Request $request)
    {
        try {
            $request->validate([
                'id_pembelian' => 'nullable|exists:pembelian_barang,id',
                'id_barang'    => 'required|exists:barang,id',
                'qty'          => 'required|numeric|min:1',
                'harga_barang' => 'required|numeric|min:1',

                'level_harga'   => 'nullable|array',
                'level_harga.*' => 'numeric|min:0',

                'hpp_awal' => 'required|numeric|min:0',
                'hpp_baru' => 'required|numeric|min:0',

                'toko_group_id' => 'required_without:id_pembelian',
                'supplier_id'     => 'required_without:id_pembelian|exists:supplier,id',
                'kas_id'          => 'required_without:id_pembelian|exists:kas,id',
                'nota'            => 'required_without:id_pembelian|string|max:255',
                'tanggal'         => 'required_without:id_pembelian|date',
                'tipe'            => 'required_without:id_pembelian|string|max:50',
            ]);

            $idPembelian = $request->id_pembelian;
            $levelHarga = FormatHarga::array($request->level_harga);

            if ($idPembelian === null) {
                $pembelian = PembelianBarang::create([
                    'toko_group_id' => $request->toko_group_id,
                    'supplier_id'   => $request->supplier_id,
                    'kas_id'        => $request->kas_id,
                    'nota'          => $request->nota,
                    'tanggal'       => $request->tanggal,
                    'tipe'          => $request->tipe,
                    'created_by'    => $request->created_by,
                ]);

                $idPembelian = $pembelian->id;
            }

            StockBarang::updateOrCreate(
                [
                    'barang_id'     => $request->id_barang,
                    'toko_group_id' => $request->toko_group_id,
                ],
                [
                    'level_harga' => json_encode($levelHarga), // ✅ FIX
                    'hpp_awal'    => (float) $request->hpp_awal,
                    'hpp_baru'    => (float) $request->hpp_baru,
                ]
            );

            $tempDetail = PembelianBarangDetailTemp::create([
                'pembelian_barang_id' => $idPembelian,
                'barang_id'           => $request->id_barang,
                'qty'                 => $request->qty,
                'harga_beli'          => $request->harga_barang,
                'subtotal'            => $request->qty * $request->harga_barang,
                'level_harga'         => json_encode($levelHarga), // ✅ FIX
            ]);

            return response()->json([
                'status' => 'success',
                'data'   => $tempDetail
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'line'   => $e->getLine(),
                'msg'    => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteTemp(Request $request)
    {
        try {
            $request->validate([
                'id_pembelian' => 'required|exists:pembelian_barang_detail_temp,pembelian_barang_id',
                'id_barang'    => 'required|exists:pembelian_barang_detail_temp,barang_id',
                'toko_group_id' => 'required|exists:toko_group,id',
            ]);

            DB::beginTransaction();

            // Ambil data yang akan dihapus (untuk undo)
            $temp = PembelianBarangDetailTemp::where('pembelian_barang_id', $request->id_pembelian)
                ->where('barang_id', $request->id_barang)
                ->first();

            if (!$temp) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            // Hapus temp
            $temp->delete();

            // ================================
            // REHITUNG ULANG STOCK & HPP
            // ================================
            $stockBarang = StockBarang::where('barang_id', $request->id_barang)
                ->where('toko_group_id', $request->toko_group_id)
                ->first();

            if ($stockBarang) {

                // Ambil sisa temp
                $sisaTemp = PembelianBarangDetailTemp::where('barang_id', $request->id_barang)
                    ->where('pembelian_barang_id', $request->id_pembelian)
                    ->get();

                if ($sisaTemp->count() > 0) {

                    // 🔥 Hitung ulang HPP dari sisa temp
                    $totalQty = $sisaTemp->sum('qty');
                    $totalHarga = $sisaTemp->sum('subtotal');

                    $hppBaru = $totalQty > 0
                        ? round($totalHarga / $totalQty, 2)
                        : 0;

                    $stockBarang->update([
                        'hpp_baru' => $hppBaru,
                    ]);
                } else {

                    // 🔥 Tidak ada temp → rollback ke hpp_awal
                    $stockBarang->update([
                        'hpp_baru' => $stockBarang->hpp_awal,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data berhasil di-undo'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'line'   => $e->getLine(),
                'msg'    => $e->getMessage(),
            ], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            $import = new PembelianBarangImport();
            Excel::import($import, $request->file('file'));

            // Cek jika ada error dari dalam import
            if (!empty($import->getErrors())) {
                return back()->with('warning', $import->getErrors());
            }

            return back()->with('success', 'Data berhasil diimpor!');
        } catch (ExcelValidationException $e) {
            // Tangani error bawaan Laravel Excel
            $failures = $e->failures();
            $messages = [];

            foreach ($failures as $failure) {
                $row = $failure->row(); // row di excel
                $attribute = $failure->attribute(); // kolom yang gagal
                $errors = $failure->errors(); // array of messages

                foreach ($errors as $error) {
                    $messages[] = "Baris {$row} kolom {$attribute}: {$error}";
                }
            }

            return back()->with('error', $messages);
        } catch (\Exception $e) {
            // Tangani exception umum
            return back()->with('error', 'Terjadi kesalahan saat mengimpor data: ' . $e->getMessage());
        }
    }
}
