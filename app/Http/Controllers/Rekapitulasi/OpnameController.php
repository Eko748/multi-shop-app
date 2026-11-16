<?php

namespace App\Http\Controllers\Rekapitulasi;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\StockBarang;
use App\Models\StockBarangBatch;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Models\Toko;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpnameController extends Controller
{
    use ApiResponse;

    public function get(Request $request)
    {
        $limit = $request->has('limit') && $request->limit <= 50 ? $request->limit : 30;

        $query = StockOpname::with(['toko', 'user', 'details.batch.stockBarang.barang'])
            ->orderBy('tanggal', 'desc');

        // Filter berdasarkan toko_id jika dikirim
        if ($request->filled('toko_id') && $request->toko_id !== 'all') {
            $query->where('toko_id', $request->toko_id);
        }

        // Filter berdasarkan bulan & tahun (Default: Bulan ini)
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $query->whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month);

        // Filter pencarian teks bebas (keterangan atau nama toko)
        if ($request->filled('search')) {
            $searchTerm = strtolower($request->search);
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw("LOWER(keterangan) LIKE ?", ["%$searchTerm%"])
                    ->orWhereHas('toko', fn($sub) => $sub->whereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]));
            });
        }

        $opnamePaginated = $query->paginate($limit);

        $mappedData = collect($opnamePaginated->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'toko_id' => $item->toko_id,
                'nama_toko' => $item->toko->nama ?? '-',
                'dibuat_oleh' => $item->user->nama ?? '-',
                'tanggal' => Carbon::parse($item->tanggal)->format('d-m-Y'),
                'status' => $item->status,
                'keterangan' => $item->keterangan ?? '-',
                'total_item_dianalisis' => $item->details->count(),
            ];
        });

        $paginationMeta = [
            'total' => $opnamePaginated->total(),
            'per_page' => $opnamePaginated->perPage(),
            'current_page' => $opnamePaginated->currentPage(),
            'total_pages' => $opnamePaginated->lastPage(),
        ];

        if ($opnamePaginated->isEmpty()) {
            return $this->error(404, 'Tidak ada riwayat stok opname untuk periode ini.');
        }

        return $this->success([
            'data' => $mappedData,
            'meta_filter' => [
                'month' => (int) $month,
                'year' => (int) $year,
            ]
        ], 200, 'Riwayat stok opname berhasil diambil', $paginationMeta);
    }

    public function detail(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:stock_opnames,id',
        ]);

        try {
            $opname = StockOpname::with(['toko', 'user', 'details.batch.stockBarang.barang'])->find($request->id);

            if (!$opname) {
                return $this->error(404, 'Data sesi stok opname tidak ditemukan.');
            }

            $details = $opname->details->map(function ($detail) {
                $barang = $detail->batch?->stockBarang?->barang;
                return [
                    'detail_id' => $detail->id,
                    'nama_barang' => $barang->nama ?? '-',
                    'qrcode' => $barang->qrcode ?? '-',
                    'barcode' => $barang->barcode ?? '-',
                    'qty_sistem' => $detail->qty_sistem,
                    'qty_fisik' => $detail->qty_fisik,
                    'selisih' => $detail->selisih,
                    'status_kondisi' => $detail->status_kondisi,
                    'catatan_item' => $detail->catatan_item ?? '-',
                ];
            });

            $data = [
                'session_info' => [
                    'id' => $opname->id,
                    'toko_id' => $opname->toko_id,
                    'nama_toko' => $opname->toko->nama ?? '-',
                    'user_id' => $opname->user_id,
                    'nama_user' => $opname->user->nama ?? '-',
                    'tanggal' => Carbon::parse($opname->tanggal)->format('d-m-Y'),
                    'status' => $opname->status,
                    'keterangan' => $opname->keterangan ?? '-',
                ],
                'items' => $details,
            ];

            return $this->success($data, 200, 'Detail stok opname berhasil ditemukan.');
        } catch (\Throwable $e) {
            return $this->error(500, 'Gagal mengambil detail stok opname', $e->getMessage());
        }
    }

    public function scanItem(Request $request)
    {
        $request->validate([
            'toko_id' => 'required|integer|exists:toko,id',
            'qrcode' => 'required|string',
        ]);

        try {
            $barang = Barang::where('qrcode', $request->qrcode)->first();

            if (!$barang) {
                return $this->error(404, 'Barang dengan QR Code tersebut tidak ditemukan.');
            }

            // Ambil data toko beserta grup-grupnya dari tabel pivot toko_group_item
            $toko = Toko::with('groups')->find($request->toko_id);

            // Ambil kumpulan ID grup tempat toko ini berada
            $tokoGroupIds = $toko ? $toko->groups->pluck('id')->toArray() : [];

            // Cari stok berdasarkan barang_id dan salah satu toko_group_id yang dimiliki toko tersebut
            $stockBarang = StockBarang::where('barang_id', $barang->id)
                ->when(!empty($tokoGroupIds), function ($q) use ($tokoGroupIds) {
                    $q->whereIn('toko_group_id', $tokoGroupIds);
                })
                ->first();

            $batches = [];
            if ($stockBarang) {
                // Ambil batch berdasarkan stock_barang_id dan toko_id spesifik
                $batches = StockBarangBatch::where('stock_barang_id', $stockBarang->id)
                    ->where('toko_id', $request->toko_id)
                    ->where('qty_sisa', '>', 0)
                    ->get()
                    ->map(fn($batch) => [
                        'stock_barang_batch_id' => $batch->id,
                        'qty_sisa' => (int) $batch->qty_sisa,
                        'harga_beli' => (float) $batch->harga_beli,
                        'tanggal' => $batch->tanggal,
                    ]);
            }

            $data = [
                'barang_id' => $barang->id,
                'nama_barang' => $barang->nama,
                'qrcode' => $barang->qrcode,
                'barcode' => $barang->barcode,
                'total_stok_sistem' => $stockBarang ? (int) $stockBarang->stok : 0,
                'batches' => $batches,
            ];

            return $this->success($data, 200, 'Data barang berhasil di-scan.');
        } catch (\Throwable $e) {
            return $this->error(500, 'Gagal memproses scan barang', $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'toko_id' => 'required|integer|exists:toko,id',
            'user_id' => 'required|integer|exists:users,id',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.stock_barang_batch_id' => 'required|integer|exists:stock_barang_batch,id',
            'items.*.qty_fisik' => 'required|integer|min:0',
            'items.*.status_kondisi' => 'required|in:normal,mati,rusak,hilang',
            'items.*.catatan_item' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $opname = StockOpname::create([
                'toko_id' => $request->toko_id,
                'user_id' => $request->user_id,
                'tanggal' => Carbon::parse($request->tanggal)->toDateString(),
                'status' => 'selesai',
                'keterangan' => $request->keterangan,
            ]);

            foreach ($request->items as $item) {
                $batch = StockBarangBatch::find($item['stock_barang_batch_id']);
                if (!$batch) {
                    continue;
                }

                $qtySistem = $batch->qty_sisa;
                $qtyFisik = $item['qty_fisik'];
                $selisih = $qtyFisik - $qtySistem;

                StockOpnameDetail::create([
                    'stock_opname_id' => $opname->id,
                    'stock_barang_batch_id' => $batch->id,
                    'qty_sistem' => $qtySistem,
                    'qty_fisik' => $qtyFisik,
                    'selisih' => $selisih,
                    'status_kondisi' => $item['status_kondisi'],
                    'catatan_item' => $item['catatan_item'] ?? null,
                ]);
            }

            DB::commit();

            return $this->success([
                'stock_opname_id' => $opname->id,
                'total_item' => count($request->items),
            ], 200, 'Data stok opname berhasil disimpan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error(500, 'Gagal menyimpan data stok opname', $e->getMessage());
        }
    }
}
