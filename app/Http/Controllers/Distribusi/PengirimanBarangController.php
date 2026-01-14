<?php

namespace App\Http\Controllers\Distribusi;

use App\Http\Controllers\Controller;
use App\Helpers\QrGenerator;
use App\Models\{StockBarang, StockBarangBatch, StockBarangBermasalah, Hutang, JenisBarang, Kas, Toko};
use App\Models\{PengirimanBarang, PengirimanBarangDetail, PengirimanBarangDetailTemp, Piutang};
use App\Services\KasService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengirimanBarangController extends Controller
{
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Pengiriman Barang',
            'Tambah Data',
            'Detail Data',
            'Edit Data',
            'Reture Data',
        ];
    }

    public function index(Request $request)
    {
        $menu = [$this->title[0], $this->label[1]];

        return view('transaksi.pengirimanbarang.index', compact('menu'));
    }

    public function get(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $id_toko = $request->input('toko_id');

        $query = PengirimanBarang::query()
            ->select('pengiriman_barang.*')
            ->selectRaw("
                CASE
                    WHEN status = 'pending'
                        THEN (SELECT COALESCE(SUM(qty_send), 0)
                            FROM pengiriman_barang_detail_temp
                            WHERE pengiriman_barang_id = pengiriman_barang.id)
                    ELSE (SELECT COALESCE(SUM(qty_send), 0)
                        FROM pengiriman_barang_detail
                        WHERE pengiriman_barang_id = pengiriman_barang.id)
                END AS qty_total
            ");

        if ($id_toko != 1) {

            $query->where(function ($q) use ($id_toko) {
                $q->where('toko_asal_id', $id_toko)
                    ->orWhere(function ($r) use ($id_toko) {
                        $r->where('toko_tujuan_id', $id_toko)
                            ->where('status', '!=', 'pending');
                    });
            });
        }

        $query->orderByRaw("
            CASE
                WHEN status = 'pending' THEN 0
                WHEN status = 'progress' THEN 1
                WHEN status = 'success' THEN 2
                ELSE 3
            END
        ")
            ->orderBy('created_at', $meta['orderBy']);


        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(no_resi) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereRaw("LOWER(ekspedisi) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereRaw("LOWER(status) LIKE ?", ["%$searchTerm%"]);

                $query->orWhereHas('tokoAsal', function ($subquery) use ($searchTerm) {
                    $subquery->whereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]);
                });
                $query->orWhereHas('tokoTujuan', function ($subquery) use ($searchTerm) {
                    $subquery->whereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]);
                });
                $query->orWhereHas('user', function ($subquery) use ($searchTerm) {
                    $subquery->whereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]);
                });
            });
        }

        if ($request->has('startDate') && $request->has('endDate')) {
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');

            $query->whereBetween('tgl_kirim', [$startDate, $endDate]);
        }

        $data = $query->paginate($meta['limit']);

        $paginationMeta = [
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'total_pages' => $data->lastPage()
        ];

        $data = [
            'data' => $data->items(),
            'meta' => $paginationMeta
        ];

        if (empty($data['data'])) {
            return response()->json([
                'status_code' => 400,
                'errors' => true,
                'message' => 'Tidak ada data'
            ], 400);
        }

        $mappedData = collect($data['data'])->map(function ($item) {
            return [
                'id' => $item['id'],
                'no_resi' => $item->no_resi,
                'ekspedisi' => $item->ekspedisi,
                'toko_asal' => $item->tokoAsal->nama ?? null,
                'toko_asal_id' => $item->toko_asal_id ?? null,
                'nama_pengirim' => $item->sender->nama ?? null,
                'toko_tujuan' => $item->tokoTujuan->nama ?? null,
                'toko_tujuan_id' => $item->toko_tujuan_id ?? null,
                'status' => match ($item->status) {
                    'success' => 'Sukses',
                    'progress' => 'Progress',
                    'pending' => 'Pending',
                    'failed' => 'Gagal',
                    default => $item->status,
                },
                'tgl_kirim' => \Carbon\Carbon::parse($item->send_at)->format('d-m-Y'),
                'tgl_terima' => $item->verified_at ? \Carbon\Carbon::parse($item->verified_at)->format('d-m-Y') : null,
                'total_item' => $item->qty_total,
            ];
        });

        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => true,
            'message' => 'Sukses',
            'pagination' => $data['meta']
        ], 200);
    }

    public function progress(Request $request)
    {
        $pb = PengirimanBarang::with([
            'tokoAsal:id,nama',
            'tokoTujuan:id,nama',
            'sender:id,nama',
            'pengirimanBarangDetail.barang:id,nama,barcode',
            'pengirimanBarangDetail.batch:id,parent_id,qrcode,qty_sisa,harga_beli'
        ])
            ->select('id', 'toko_asal_id', 'toko_tujuan_id', 'status', 'no_resi', 'ekspedisi', 'send_by', 'send_at')
            ->where('id', $request->id)
            ->first();

        if (!$pb) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $data = [
            'id'           => $pb->id,
            'no_resi'      => $pb->no_resi,
            'ekspedisi'    => $pb->ekspedisi,
            'status'       => $pb->status,
            'send_at'      => $pb->send_at,
            'toko_asal'    => [
                'id'   => $pb->tokoAsal->id,
                'nama' => $pb->tokoAsal->nama,
            ],
            'toko_tujuan'  => [
                'id'   => $pb->tokoTujuan->id,
                'nama' => $pb->tokoTujuan->nama,
            ],
            'sender'       => [
                'id'   => $pb->sender->id,
                'nama' => $pb->sender->nama,
            ],
            'details'      => $pb->pengirimanBarangDetail->map(function ($d) {
                $img = asset("storage/qrcodes/pembelian/{$d->batch->qrcode}.png");
                return [
                    'id'        => $d->id,
                    'barang'    => [
                        'id'     => $d->barang->id,
                        'nama'   => $d->barang->nama,
                        'barcode' => $d->barang->barcode,
                    ],
                    'qty_send'  => $d->qty_send,
                    'qty_verified' => $d->qty_verified,
                    'batch'     => [
                        'id'      => $d->batch->id,
                        'qrcode'  => $d->batch->qrcode,
                        'path'  => "qrcodes/pembelian/{$d->batch->qrcode}.png",
                        'qty_sisa' => $d->batch->qty_sisa,
                        'harga_beli' => $d->batch->harga_beli
                    ],
                    'format_harga_beli' => 'Rp ' . number_format($d->batch->harga_beli, 0, ',', '.'),
                    'text' => "
                        <div style='display: flex; align-items: center; gap: 8px;' class='p-1'>
                            <img src='{$img}' width='28' height='28' style='border-radius: 3px;'>

                            <div style='display: flex; flex-direction: column; line-height: 1.2;'>
                                <span style='font-weight: 550; font-size: 12px;'>{$d->barang->nama}</span>
                                <small class='text-dark'>
                                    Stok: {$d->qty_send}
                                </small>
                            </div>
                        </div>
                    "
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function post(Request $request)
    {
        $request->validate([
            'pengiriman_barang_id' => 'nullable|integer',
            'toko_asal_id'     => 'required|integer',
            'toko_tujuan_id'   => 'required|integer|different:toko_asal_id',
            'no_resi'          => 'required|string',
            'ekspedisi'        => 'required|string',
            'send_by'          => 'required|integer',
            'send_at'          => 'required|date',
            'details'          => 'required|array|min:1',
            'details.*.barang_id' => 'required|integer',
            'details.*.stock_barang_batch_id' => 'required|integer',
            'details.*.qty_send' => 'required|integer|min:1'
        ]);

        DB::beginTransaction();

        try {
            if ($request->pengiriman_barang_id) {
                $pengiriman = PengirimanBarang::update(['id' => $request->pengiriman_barang_id], [
                    'toko_asal_id'   => $request->toko_asal_id,
                    'toko_tujuan_id' => $request->toko_tujuan_id,
                    'no_resi'        => $request->no_resi,
                    'ekspedisi'      => $request->ekspedisi,
                    'send_by'        => $request->send_by,
                    'send_at'        => $request->send_at,
                    'status'         => 'progress'
                ]);

                PengirimanBarangDetailTemp::where('pengiriman_barang_id', $pengiriman->id)->delete();
            } else {
                $pengiriman = PengirimanBarang::create([
                    'toko_asal_id'   => $request->toko_asal_id,
                    'toko_tujuan_id' => $request->toko_tujuan_id,
                    'no_resi'        => $request->no_resi,
                    'ekspedisi'      => $request->ekspedisi,
                    'send_by'        => $request->send_by,
                    'send_at'        => $request->send_at,
                    'status'         => 'progress'
                ]);
            }

            foreach ($request->details as $row) {

                $batchOrigin = StockBarangBatch::lockForUpdate()->find($row['stock_barang_batch_id']);

                if (!$batchOrigin || $batchOrigin->qty_sisa < $row['qty_send']) {
                    throw new \Exception("Stok batch asal tidak mencukupi");
                }

                $batchOrigin->qty_sisa -= $row['qty_send'];
                $batchOrigin->save();

                $stockOrigin = StockBarang::where('barang_id', $row['barang_id'])
                    ->where('toko_id', $request->toko_asal_id)
                    ->lockForUpdate()
                    ->first();

                if (!$stockOrigin || $stockOrigin->stok < $row['qty_send']) {
                    throw new \Exception("Stok toko asal tidak mencukupi");
                }

                $stockOrigin->stok -= $row['qty_send'];
                $stockOrigin->save();

                PengirimanBarangDetail::create([
                    'pengiriman_barang_id' => $pengiriman->id,
                    'barang_id'             => $row['barang_id'],
                    'stock_barang_batch_id' => $row['stock_barang_batch_id'],
                    'qty_send'              => $row['qty_send'],
                    'qty_verified'          => 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Pengiriman berhasil dibuat',
                'data'    => $pengiriman
            ], 201);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function draft(Request $request)
    {
        $request->validate([
            'pengiriman_barang_id' => 'nullable|integer',
            'toko_asal_id'         => 'required|integer',
            'toko_tujuan_id'       => 'required|integer|different:toko_asal_id',
            'no_resi'              => 'required|string',
            'ekspedisi'            => 'required|string',
            'send_by'              => 'required|integer',
            'send_at'              => 'required|date',
            'details'              => 'required|array|min:1',
            'details.*.barang_id'  => 'required|integer',
            'details.*.stock_barang_batch_id' => 'required|integer',
            'details.*.qty_send'   => 'required|integer|min:1'
        ]);

        DB::beginTransaction();

        try {
            if (!$request->pengiriman_barang_id) {
                $pengiriman = PengirimanBarang::create([
                    'toko_asal_id'   => $request->toko_asal_id,
                    'toko_tujuan_id' => $request->toko_tujuan_id,
                    'no_resi'        => $request->no_resi,
                    'ekspedisi'      => $request->ekspedisi,
                    'send_by'        => $request->send_by,
                    'send_at'        => $request->send_at,
                    'status'         => 'pending',
                ]);
            } else {
                $pengiriman = PengirimanBarang::findOrFail($request->pengiriman_barang_id);

                $pengiriman->update([
                    'toko_asal_id'   => $request->toko_asal_id,
                    'toko_tujuan_id' => $request->toko_tujuan_id,
                    'no_resi'        => $request->no_resi,
                    'ekspedisi'      => $request->ekspedisi,
                    'send_by'        => $request->send_by,
                    'send_at'        => $request->send_at,
                ]);

                PengirimanBarangDetailTemp::where('pengiriman_barang_id', $pengiriman->id)->delete();
            }

            foreach ($request->details as $row) {
                PengirimanBarangDetailTemp::create([
                    'pengiriman_barang_id' => $pengiriman->id,
                    'barang_id'             => $row['barang_id'],
                    'stock_barang_batch_id' => $row['stock_barang_batch_id'],
                    'qty_send'              => $row['qty_send'],
                    'qty_verified'          => 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Draft pengiriman tersimpan',
                'pengiriman_barang_id' => $pengiriman->id,
                'status'  => 'pending'
            ], 201);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function getDetailTemp($pengirimanBarangId)
    {
        $header = PengirimanBarang::find($pengirimanBarangId);

        if (!$header || $header->status !== 'pending') {
            return response()->json([
                'message' => 'Draft tidak ditemukan'
            ], 404);
        }

        $details = PengirimanBarangDetailTemp::where('pengiriman_barang_id', $pengirimanBarangId)
            ->with(['barang', 'batch'])
            ->get();

        return response()->json([
            'header'  => $header,
            'details' => $details
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'verified_by' => 'required|integer',
            'details' => 'required|array',
            'details.*.id' => 'required|integer',
            'details.*.qty_verified' => 'required|integer|min:0',
            'details.*.qty_problem' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            $pb = PengirimanBarang::where('id', $request->id)
                ->lockForUpdate()
                ->first();

            if (!$pb) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $hutangGrouped = [];

            foreach ($request->details as $row) {

                $detail = PengirimanBarangDetail::where('id', $row['id'])
                    ->where('pengiriman_barang_id', $pb->id)
                    ->lockForUpdate()
                    ->first();

                if (!$detail) continue;

                $qtyVerified = $row['qty_verified'];

                $detail->qty_verified = $qtyVerified;
                $detail->save();

                if ($qtyVerified > 0) {

                    $stockOrigin = StockBarang::where('toko_id', $pb->toko_asal_id)
                        ->where('barang_id', $detail->barang_id)
                        ->first();

                    $stockTarget = StockBarang::firstOrCreate(
                        [
                            'toko_id'   => $pb->toko_tujuan_id,
                            'barang_id' => $detail->barang_id
                        ],
                        [
                            'hpp_awal' => $stockOrigin->hpp_awal,
                            'hpp_baru' => $stockOrigin->hpp_baru,
                            'level_harga' => $stockOrigin->level_harga,
                            'stok' => 0
                        ]
                    );

                    $stockTarget->stok += $qtyVerified;
                    $stockTarget->save();

                    $batchOrigin = StockBarangBatch::find($detail->stock_barang_batch_id);

                    $batchNew = StockBarangBatch::create([
                        'stock_barang_id' => $stockTarget->id,
                        'parent_id'       => $batchOrigin->id,
                        'qrcode'          => QrGenerator::generate()['value'],
                        'qty_masuk'       => $qtyVerified,
                        'qty_sisa'        => $qtyVerified,
                        'harga_beli'      => $batchOrigin->harga_beli,
                        'hpp_awal'        => $batchOrigin->hpp_awal,
                        'hpp_baru'        => $batchOrigin->hpp_baru,
                    ]);

                    $totalBiaya = $qtyVerified * $batchOrigin->harga_beli;
                    $jenisBarangId = $detail->barang->jenis_barang_id;
                    $tipeKas = $request->tipe_kas ?? 'besar';
                    $kas = Kas::firstOrCreate(
                        [
                            'toko_id' => $pb->toko_tujuan_id,
                            'jenis_barang_id' => $jenisBarangId,
                            'tipe_kas' => $tipeKas,
                        ],
                        [
                            'saldo_awal' => 0,
                            'saldo' => 0
                        ]
                    );

                    $kasMampu = min($kas->saldo, $totalBiaya);

                    $sisaHutang = $totalBiaya - $kasMampu;

                    if ($sisaHutang > 0) {
                        if (!isset($hutangGrouped[$jenisBarangId])) {
                            $hutangGrouped[$jenisBarangId] = 0;
                        }
                        $hutangGrouped[$jenisBarangId] += $sisaHutang;
                    }

                    $isLunas = ($kasMampu == $totalBiaya);

                    $keteranganKeluar = $isLunas
                        ? "Pembayaran lunas ke {$pb->tokoTujuan->nama}"
                        : "Pembayaran sebagian PB #{$pb->id}";

                    $keteranganMasuk = $isLunas
                        ? "Penerimaan pembayaran dari {$pb->tokoAsal->nama}"
                        : "Penerimaan pembayaran PB #{$pb->id}";

                    if ($kasMampu > 0) {
                        KasService::out(
                            toko_id: $pb->toko_tujuan_id,
                            jenis_barang_id: $jenisBarangId,
                            tipe_kas: $tipeKas,
                            qty: 1,
                            nominal: $kasMampu,
                            total_nominal: $kasMampu,
                            item: 'besar',
                            kategori: 'Pengiriman Barang',
                            keterangan: $keteranganKeluar,
                            sumber: $pb
                        );

                        KasService::in(
                            toko_id: $pb->toko_asal_id,
                            jenis_barang_id: $jenisBarangId,
                            tipe_kas: 'kecil',
                            qty: 1,
                            nominal: $kasMampu,
                            total_nominal: $kasMampu,
                            item: 'kecil',
                            kategori: 'Pengiriman Barang',
                            keterangan: $keteranganMasuk,
                            sumber: $pb
                        );
                    }

                    $this->recalcHPPGlobal($stockTarget);

                    $batchNew->hpp_awal = $stockTarget->hpp_awal;
                    $batchNew->hpp_baru = $stockTarget->hpp_baru;
                    $batchNew->save();
                }

                if ($row['qty_problem'] > 0) {
                    StockBarangBermasalah::create([
                        'stock_barang_batch_id' => $detail->stock_barang_batch_id,
                        'status' => 'hilang',
                        'qty' => $row['qty_problem'],
                    ]);
                }
            }

            foreach ($hutangGrouped as $jenisBarangId => $totalHutang) {
                $tokoAsal   = Toko::find($pb->toko_asal_id);
                $tokoTujuan = Toko::find($pb->toko_tujuan_id);
                $jb         = JenisBarang::find($jenisBarangId);
                $tipeKas    = $request->tipe_kas ?? 'besar';
                $kasAsal    = Kas::where('toko_id', $pb->toko_asal_id)->where('jenis_barang_id', $jenisBarangId)->where('tipe_kas', $tipeKas)->first();
                $kasTujuan  = Kas::where('toko_id', $pb->toko_tujuan_id)->where('jenis_barang_id', $jenisBarangId)->where('tipe_kas', $tipeKas)->first();

                $hutangModel = Hutang::create([
                    'kas_id'          => $kasTujuan->id,
                    'toko_id'         => $pb->toko_tujuan_id,
                    'hutang_tipe_id'  => 2,
                    'nominal'         => $totalHutang,
                    'tanggal'         => now(),
                    'keterangan'      => "Hutang barang {$jb->nama_jenis_barang} ke {$tokoAsal->nama}",
                    'status'          => '0',
                    'jangka'          => 'pendek',
                    'sumber_id'       => $pb->id,
                    'sumber_type'     => PengirimanBarang::class,
                ]);

                $piutangModel = Piutang::create([
                    'kas_id'          => $kasAsal->id,
                    'toko_id'         => $pb->toko_asal_id,
                    'piutang_tipe_id' => 2,
                    'nominal'         => $totalHutang,
                    'tanggal'         => now(),
                    'keterangan'      => "Piutang barang {$jb->nama_jenis_barang} dari {$tokoTujuan->nama}",
                    'status'          => '0',
                    'jangka'          => 'pendek',
                    'sumber_id'       => $pb->id,
                    'sumber_type'     => PengirimanBarang::class,
                ]);

                KasService::neutralIN(
                    toko_id: $pb->toko_tujuan_id,
                    jenis_barang_id: $jenisBarangId,
                    tipe_kas: $tipeKas,
                    qty: 1,
                    nominal: $totalHutang,
                    total_nominal: $totalHutang,
                    item: 'hutang',
                    kategori: 'Hutang',
                    keterangan: "Hutang ke {$tokoAsal->nama}",
                    sumber: $hutangModel
                );

                KasService::neutralIN(
                    toko_id: $pb->toko_asal_id,
                    jenis_barang_id: $jenisBarangId,
                    tipe_kas: 'kecil',
                    qty: 1,
                    nominal: $totalHutang,
                    total_nominal: $totalHutang,
                    item: 'piutang',
                    kategori: 'Piutang',
                    keterangan: "Piutang dari {$tokoTujuan->nama}",
                    sumber: $piutangModel
                );
            }

            $pb->update([
                'status' => 'success',
                'verified_by' => $request->verified_by,
                'verified_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Verifikasi Pengiriman Barang Berhasil'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $th->getMessage()
            ], 500);
        }
    }

    private function recalcHPPGlobal(StockBarang $stock)
    {
        $allStocks = StockBarang::where('barang_id', $stock->barang_id)->get();

        $allStockIds = $allStocks->pluck('id');
        $allBatches = StockBarangBatch::whereIn('stock_barang_id', $allStockIds)->get();

        $totalQty = $allBatches->sum('qty_sisa');
        if ($totalQty <= 0) return;

        $totalValue = 0;
        foreach ($allBatches as $b) {
            $totalValue += ($b->qty_sisa * $b->harga_beli);
        }

        $hppGlobal = round($totalValue / $totalQty, 2);

        foreach ($allStocks as $s) {
            $s->hpp_awal = $s->hpp_baru;
            $s->hpp_baru = $hppGlobal;
            $s->save();
        }
    }
}
