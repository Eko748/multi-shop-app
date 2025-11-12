<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Imports\PembelianBarangImport;
use App\Models\Barang;
use App\Models\DetailPembelianBarang;
use App\Models\DetailStockBarang;
use App\Models\Hutang;
use App\Models\JenisBarang;
use App\Models\LevelHarga;
use App\Models\PembelianBarang;
use App\Models\StockBarang;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Label\Font\NotoSans;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;

class PembelianBarangController extends Controller
{
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Data Pembelian Barang',
            'Detail Data'
        ];
    }

    public function getpembelianbarang(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = PembelianBarang::query();

        $query->with(['detail.barang.jenis', 'supplier', 'level_harga'])
            ->orderByRaw("CASE WHEN status = 'completed_debt' THEN 1 ELSE 0 END DESC")
            ->orderByRaw("CASE WHEN status = 'progress' THEN 1 ELSE 0 END DESC")
            ->orderByDesc('id');

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(no_nota) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereHas('supplier', function ($subquery) use ($searchTerm) {
                    $subquery->whereRaw("LOWER(nama_supplier) LIKE ?", ["%$searchTerm%"]);
                });
                $query->orWhereHas('detail.barang', function ($subquery) use ($searchTerm) {
                    $subquery->whereRaw("LOWER(nama_barang) LIKE ?", ["%$searchTerm%"]);
                });
            });
        }

        if ($request->has('startDate') && $request->has('endDate')) {
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');

            $query->whereBetween('tgl_nota', [$startDate, $endDate]);
        }

        $totalNilai = $query->sum('total_nilai');
        $totalItem = $query->sum('total_item');

        $data = $query->paginate($meta['limit']);

        $paginationMeta = [
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'total_pages' => $data->lastPage()
        ];

        $mappedData = collect($data->items())->map(function ($item) {
            if ($item->status === 'progress') {
                $detailSum = DB::table('temp_detail_pembelian_barang')
                    ->where('id_pembelian_barang', $item->id)
                    ->selectRaw('COALESCE(SUM(qty),0) as total_item, COALESCE(SUM(harga_barang * qty),0) as total_nilai')
                    ->first();
            } else {
                $detailSum = DB::table('detail_pembelian_barang')
                    ->where('id_pembelian_barang', $item->id)
                    ->selectRaw('COALESCE(SUM(qty),0) as total_item, COALESCE(SUM(harga_barang * qty),0) as total_nilai')
                    ->first();
            }

            $totalItem = (int) ($detailSum->total_item ?? 0);
            $totalNilai = (float) ($detailSum->total_nilai ?? 0);

            $kas = null;

            if ($item->status === 'success' || $item->status === 'completed_debt' || $item->status === 'success_debt') {
                $detailSingle = $item->detail->first();
                $namaJenisBarang = $detailSingle?->barang?->jenis?->nama_jenis_barang ?? 'Tidak Diketahui';
                $kasLabel = $item->label == 1 ? 'Kas Kecil' : 'Kas Besar';
                $kas = $kasLabel . ' - ' . $namaJenisBarang;
            }

            return [
                'id' => $item->id,
                'nama_supplier' => optional($item->supplier)->nama_supplier ?? 'Tidak Ada',
                'status' => match ($item->status) {
                    'success' => 'Sukses',
                    'failed' => 'Gagal',
                    default => $item->status,
                },
                'tgl_nota' => Carbon::parse($item->tgl_nota)->format('d-m-Y'),
                'no_nota' => $item->no_nota,
                'tipe' => $item->tipe,
                'total_item' => $totalItem,
                'total_nilai' => 'Rp. ' . number_format($totalNilai, 0, ',', '.'),
                'kas' => $kas,
            ];
        });

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Sukses',
            'pagination' => $paginationMeta,
            'total' => 'Rp. ' . number_format($totalNilai, 0, '.', '.'),
            'totals' => $totalItem
        ], 200);
    }

    public function index(Request $request)
    {
        $menu = [$this->title[0], $this->label[1]];
        $suppliers = Supplier::all();       // Kirim data ke view
        $barang = Barang::all();       // Kirim data ke view
        $LevelHarga = LevelHarga::all();       // Kirim data ke view
        return view('transaksi.pembelianbarang.index', compact('menu', 'suppliers', 'barang', 'LevelHarga'));
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
                'tipe'        => $request->tipe, // ✅ langsung ambil dari request
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

    public function update(Request $request, $id)
    {
        $levelNamas = $request->input('level_nama', []);
        $levelHargas = $request->input('level_harga', []);
        $labelRaw = $request->input('label');
        [$labelValue, $limitTotal] = explode('/', $labelRaw) + [null, null];

        if (!is_numeric($limitTotal)) {
            return response()->json([
                'success' => false,
                'message' => 'Format label tidak valid. Gunakan format: kode/limit',
            ], 422);
        }

        $rules = [
            'id_barang' => 'required|array',
            'qty' => 'required|array',
            'harga_barang' => 'required|array',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->with('error', 'Data tidak lengkap, pastikan semua input diisi.');
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $pembelian = PembelianBarang::findOrFail($id);

            $totalItem = 0;
            $totalNilai = 0;
            $counter = 1;

            $tempDetails = DB::table('temp_detail_pembelian_barang')
                ->where('id_pembelian_barang', $id)
                ->get();

            foreach ($tempDetails as $tempDetail) {
                $id_barang = $tempDetail->id_barang;
                $qty = $tempDetail->qty;
                $harga_barang = $tempDetail->harga_barang;

                $barang = Barang::findOrFail($id_barang);

                $tglNota = Carbon::parse($pembelian->tgl_nota)->format('dmY');
                $idSupplier = $pembelian->id_supplier;
                $idPembelian = $pembelian->id;
                $qrCodeValue = "{$tglNota}SP{$idSupplier}ID{$idPembelian}-{$counter}";

                $qrCodeFilename = "{$idPembelian}-{$counter}.png";
                $qrCodeStoragePath = "qrcodes/pembelian/{$qrCodeFilename}";
                $qrCodePublicPath = "{$qrCodeStoragePath}";

                if (!Storage::disk('public')->exists($qrCodeStoragePath)) {
                    $qrCode = QrCode::create($qrCodeValue)
                        ->setEncoding(new Encoding('UTF-8'))
                        ->setSize(200)
                        ->setMargin(10);

                    $writer = new PngWriter();
                    $result = $writer->write($qrCode);
                    Storage::disk('public')->put($qrCodeStoragePath, $result->getString());
                }

                $detail = DetailPembelianBarang::updateOrCreate(
                    [
                        'id_pembelian_barang' => $pembelian->id,
                        'id_barang' => $id_barang,
                        'id_supplier' => $idSupplier,
                    ],
                    [
                        'qty' => $qty,
                        'harga_barang' => $harga_barang,
                        'total_harga' => $qty * $harga_barang,
                        'qrcode' => $qrCodeValue,
                        'qrcode_path' => $qrCodePublicPath,
                    ]
                );

                $detail->status = 'success';
                $detail->save();

                $totalItem += $detail->qty;
                $totalNilai += $detail->total_harga;

                // Handle level harga
                $levelHargaBarang = [];

                if (!empty($levelHargas[$id_barang]) && is_array($levelHargas[$id_barang])) {
                    foreach ($levelHargas[$id_barang] as $levelIndex => $hargaLevel) {
                        $levelNama = $levelNamas[$levelIndex] ?? 'Level ' . ($levelIndex + 1);
                        if (!is_null($hargaLevel)) {
                            $levelHargaBarang[] = "{$levelNama} : {$hargaLevel}";
                        }
                    }
                } else {
                    $levelHargaBarang = json_decode($tempDetail->level_harga, true) ?? [];
                }

                $barang->level_harga = json_encode($levelHargaBarang);
                $barang->save();

                // Update stok
                $stockBarang = StockBarang::firstOrNew(['id_barang' => $id_barang]);
                $stockBarang->level_harga = json_encode($levelHargaBarang);
                $hpp_awal = $stockBarang->hpp_baru ?: $stockBarang->hpp_awal ?: $harga_barang;
                $stock_awal = $stockBarang->stock ?: 0;

                $qty_detail_toko = DB::table('detail_toko')
                    ->where('id_barang', $id_barang)
                    ->sum('qty');

                $total_stock_lama = $stock_awal + $qty_detail_toko;
                $nilai_total_lama = $total_stock_lama * $hpp_awal;
                $nilai_pembelian_baru = $qty * $harga_barang;
                $total_qty_baru = $total_stock_lama + $qty;
                $hpp_baru = $total_qty_baru > 0
                    ? ($nilai_total_lama + $nilai_pembelian_baru) / $total_qty_baru
                    : $hpp_awal;

                $stockBarang->stock = $stock_awal + $detail->qty;
                $stockBarang->hpp_awal = $hpp_awal;
                $stockBarang->hpp_baru = $hpp_baru;
                $stockBarang->nilai_total = $hpp_baru * $stockBarang->stock;
                $stockBarang->nama_barang = $barang->nama_barang;
                // if ($request->filled('jenis_id')) {
                //     $pembelian->kas_jenis_barang = $request->jenis_id;
                // }
                $stockBarang->save();

                DetailStockBarang::updateOrCreate(
                    [
                        'id_pembelian' => $pembelian->id,
                        'id_detail_pembelian' => $detail->id,
                        'id_barang' => $id_barang,
                        'id_supplier' => $idSupplier,
                    ],
                    [
                        'id_stock' => $stockBarang->id,
                        'qty_buy' => $qty,
                        'qty_now' => $qty,
                    ]
                );

                $counter++;
            }

            if ($totalNilai > (float) $limitTotal && $pembelian->tipe == 'cash') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Total nilai pembelian melebihi sumber dana yang dipilih.'
                ], 422);
            }

            $pembelian->total_item = $totalItem;
            $pembelian->total_nilai = $totalNilai;
            $pembelian->id_users = $user->id;
            $pembelian->label = $labelValue;

            if ($pembelian->tipe == 'cash') {
                $pembelian->status = 'success';
            } elseif ($pembelian->tipe == 'hutang') {
                $pembelian->status = 'completed_debt';

                $firstDetail = DetailPembelianBarang::where('id_pembelian_barang', $pembelian->id)
                    ->with('barang')
                    ->first();

                $kasJenisBarang = $firstDetail && $firstDetail->barang
                    ? $firstDetail->barang->id_jenis_barang
                    : null;

                $hutang  = Hutang::create([
                    'id_toko' => Auth::user()->id_toko,
                    'id_jenis' => 1,
                    'pb_id' => $pembelian->id,
                    'keterangan' => "Hutang Pembelian Barang Nota {$pembelian->no_nota}",
                    'nilai' => $pembelian->total_nilai,
                    'status' => '1',
                    'jangka' => 2,
                    'tanggal' => $pembelian->tgl_nota,
                    'label' => $pembelian->label,
                    'kas_jenis_barang' => $kasJenisBarang,
                ]);

                $kasLabel = match ((int) $hutang->label) {
                    0 => 'Kas Besar',
                    1 => 'Kas Kecil',
                    default => 'Kas Tidak Dikenal'
                };

                $jenisBarangText = 'Tidak Dikenal';
                if (!is_null($hutang->kas_jenis_barang)) {
                    if ($hutang->kas_jenis_barang == 0) {
                        $jenisBarangText = 'Dompet Digital';
                    } else {
                        $jenis = JenisBarang::find($hutang->kas_jenis_barang);
                        $jenisBarangText = $jenis?->nama_jenis_barang ?? 'Tidak Dikenal';
                    }
                }

                $description = "Hutang ditambahkan pada {$kasLabel} ({$jenisBarangText}) senilai Rp " . number_format($hutang->nilai, 0, ',', '.') . " (ID {$hutang->id})";

                $this->saveLogAktivitas(
                    logName: 'Hutang',
                    subjectType: 'App\Models\Hutang',
                    subjectId: $hutang->id,
                    event: 'Tambah Data',
                    properties: [
                        'changes' => [
                            'new' => [
                                'nilai' => $hutang->nilai,
                                'tanggal' => $hutang->tanggal,
                                'kas' => $kasLabel . ' ' . $jenisBarangText,
                            ],
                        ]
                    ],
                    description: $description,
                    userId: Auth::user()->id,
                    message: filled($request->keterangan)
                        ? $request->keterangan
                        : '(Sistem) Hutang dibuat.'
                );
            }

            $pembelian->save();

            DB::table('temp_detail_pembelian_barang')
                ->where('id_pembelian_barang', $pembelian->id)
                ->delete();

            DB::commit();

            return redirect()->route('transaksi.pembelianbarang.index')->with('success', 'Data berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update pembelian barang. ' . $e->getMessage()
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



    public function gettemppembelian(Request $request)
    {
        try {
            // Ambil id_pembelian dari request
            $id_pembelian = $request->input('id_pembelian');

            // Ambil data dari tabel berdasarkan id_pembelian dan join ke tabel barang
            $tempDetails = DB::table('temp_detail_pembelian_barang')
                ->join('barang', 'temp_detail_pembelian_barang.id_barang', '=', 'barang.id') // Join dengan tabel barang
                ->join('jenis_barang', 'barang.id_jenis_barang', '=', 'jenis_barang.id')
                ->select(
                    'temp_detail_pembelian_barang.id_pembelian_barang',
                    'temp_detail_pembelian_barang.id_barang',
                    'barang.nama_barang',
                    'barang.id_jenis_barang',
                    'jenis_barang.nama_jenis_barang',
                    'temp_detail_pembelian_barang.qty',
                    'temp_detail_pembelian_barang.harga_barang',
                    'temp_detail_pembelian_barang.total_harga',
                    'temp_detail_pembelian_barang.level_harga'
                )
                ->where('temp_detail_pembelian_barang.id_pembelian_barang', $id_pembelian)
                ->get();


            // Decode kolom level_harga dari JSON ke array
            foreach ($tempDetails as $detail) {
                $detail->level_harga = json_decode($detail->level_harga);
            }

            // Kirimkan response JSON
            return response()->json([
                'status' => 'success',
                'errors' => false,
                'status_code' => 200,
                'message' => 'Data berhasil diambil',
                'data' => $tempDetails,
            ]);
        } catch (\Exception $e) {
            // Tangani error dan kirimkan response JSON
            return response()->json([
                'status' => 'error',
                'errors' => true,
                'status_code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Konz
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

    public function storeTemp(Request $request)
    {
        try {
            $request->validate([
                'id_pembelian' => 'required|exists:pembelian_barang,id',
                'id_barang' => 'required|exists:barang,id',
                'qty' => 'required|numeric|min:1',
                'harga_barang' => 'required|numeric|min:1',
                'level_harga' => 'array',
                'level_harga.*' => 'string',
                'hpp_awal' => 'required|numeric|min:0',
                'hpp_baru' => 'required|numeric|min:0',
            ]);

            $stock = StockBarang::where('id_barang', $request->id_barang)
                ->update([
                    'level_harga'   => json_encode($request->level_harga),
                    'hpp_awal'      => $request->hpp_awal,
                    'hpp_baru'      => $request->hpp_baru,
                ]);

            $barang = Barang::where('id', $request->id_barang)
                ->update(['level_harga' => json_encode($request->level_harga)]);

            $tempDetail = DB::table('temp_detail_pembelian_barang')->insert([
                'id_pembelian_barang' => $request->id_pembelian,
                'id_barang' => $request->id_barang,
                'qty' => $request->qty,
                'harga_barang' => $request->harga_barang,
                'total_harga' => $request->qty * $request->harga_barang,
                'level_harga' => json_encode($request->level_harga),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil disimpan',
                'data' => $tempDetail
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function hapusTemp(Request $request)
    {
        try {
            $request->validate([
                'id_pembelian' => 'required|exists:temp_detail_pembelian_barang,id_pembelian_barang',
                'id_barang' => 'required|exists:temp_detail_pembelian_barang,id_barang'
            ]);

            $deleted = DB::table('temp_detail_pembelian_barang')
                ->where('id_pembelian_barang', $request->id_pembelian)
                ->where('id_barang', $request->id_barang)
                ->delete();

            if ($deleted) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data Berhasil diEdit'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan atau sudah diHapus'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
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
