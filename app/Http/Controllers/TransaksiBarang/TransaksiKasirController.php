<?php

namespace App\Http\Controllers\TransaksiBarang;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\DetailKasir;
use App\Models\DetailPembelianBarang;
use App\Models\DetailStockBarang;
use App\Models\DetailToko;
use App\Models\Kasbon;
use App\Models\Kasir;
use App\Models\LevelHarga;
use App\Models\Member;
use App\Models\Promo;
use App\Models\StockBarang;
use App\Models\Toko;
use App\Models\User;
use App\Services\TransaksiBarang\TransaksiKasirService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Label\Font\NotoSans;
use App\Traits\{ApiResponse, HasFilter};
use Illuminate\Validation\ValidationException;
use Exception;

class TransaksiKasirController extends Controller
{
    use ApiResponse, HasFilter;

    private array $menu = [];
    protected $service;

    public function __construct(TransaksiKasirService $service)
    {
        $this->menu;
        $this->title = [
            'Transaksi Kasir',
        ];
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $menu = [$this->title[0], $this->label[1]];

        return view('transaksi.kasir.index', compact('menu'));
    }

    public function get(Request $request)
    {
        try {
            $filter = $this->makeFilter($request, 30,
            [
                'toko_id' => $request->input('toko_id'),
                'nota' => $request->input('nota'),
            ]);
            $data = $this->service->getAll($filter);

            return $this->success($data['data'], 200, 'Berhasil', $data['pagination']);
        } catch (Exception $e) {
            return $this->error(500, "Gagal mengambil data {$this->title[0]}", [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function post(Request $request)
    {
        try {
            $validated = $request->validate([
                'toko_id' => 'required|exists:toko,id',
                'created_by' => 'required|exists:users,id',
                'member_id' => 'nullable',
                'metode' => 'required|in:cash,cashless',
                'tanggal' => 'required|date',
                'total_qty' => 'required|integer|min:1',
                'total_nominal' => 'required|numeric|min:0',
                'total_bayar' => 'required|numeric|min:0',
                'details' => 'required|array|min:1',
                'details.*.stock_barang_batch_id' => 'required|exists:stock_barang_batch,id',
                'details.*.qty' => 'required|integer|min:1',
                'details.*.nominal' => 'required|numeric|min:0',
            ]);

            $data = $this->service->create($validated);

            return $this->success($data, 201, "{$this->title[0]} berhasil disimpan.");
        } catch (ValidationException $e) {
            return $this->error(422, 'Validation Error', $e->errors());
        } catch (\Exception $e) {
            return $this->error(500, 'Internal Server Error', $e->getMessage());
        }
    }


    public function print($id_kasir)
    {
        $kasir = Kasir::with('toko', 'member', 'users', 'kasbon')->findOrFail($id_kasir);

        // Ambil detail kasir sesuai id
        $detail_kasir = DetailKasir::where('id_kasir', $id_kasir)->get();

        // Grouping detail barang
        $groupedDetails = $detail_kasir
            ->groupBy('id_barang')
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'id_barang'   => $first->id_barang,
                    'nama_barang' => $first->barang->nama_barang,
                    'qty'         => $items->sum('qty'),
                    'harga'       => $first->harga,
                    'diskon'      => $first->diskon,
                    'total_harga' => $items->sum('total_harga'),
                ];
            })
            ->values();

        // Format nomor nota
        $noNotaFormatted = substr($kasir->no_nota, 0, 6) . '-' .
            substr($kasir->no_nota, 6, 6) . '-' .
            substr($kasir->no_nota, 12);

        // Data final
        $data = [
            'toko' => [
                'nama'   => $kasir->toko->nama_toko,
                'alamat' => $kasir->toko->alamat,
            ],
            'nota' => [
                'no_nota'   => $noNotaFormatted,
                'tanggal'   => $kasir->created_at->format('d-m-Y H:i:s'),
                'member'    => $kasir->id_member == 0 ? 'Guest' : $kasir->member->nama_member,
                'kasir'     => $kasir->users->nama,
            ],
            'detail' => $groupedDetails,
            'total' => [
                'total_harga'    => $kasir->total_nilai,
                'total_potongan' => $kasir->total_diskon,
                'total_bayar'    => $kasir->total_nilai - $kasir->total_diskon,
                'dibayar'        => $kasir->jml_bayar,
                'kembalian'      => $kasir->kembalian,
                'sisa_pembayaran' => $kasir->kasbon ? $kasir->kasbon->utang : 0,
            ],
            'footer' => 'Terima Kasih'
        ];

        return response()->json($data);
    }

    public function detail(Request $request)
    {
        try {
            $kasir = Kasir::with(['users', 'toko', 'member', 'kasbon'])->findOrFail($request->id);
            $detail_kasir = DetailKasir::where('id_kasir', $request->id)->with('barang')->get();

            $kasir->formatted_created_at = $kasir->created_at
                ? $kasir->created_at->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s')
                : null;

            $groupedDetails = $detail_kasir
                ->groupBy('id_barang')
                ->map(function ($items) {
                    $first = $items->first();
                    return [
                        'nama_barang' => $first->barang->nama_barang,
                        'qty' => $items->sum('qty'),
                        'harga' => $first->harga,
                        'diskon' => $first->diskon,
                        'total_harga' => $items->sum('total_harga'),
                    ];
                })
                ->values();

            return $this->success([
                'kasir' => $kasir,
                'detail_kasir' => $detail_kasir,
                'grouped_details' => $groupedDetails,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error(404, 'Data kasir tidak ditemukan.');
        } catch (\Exception $e) {
            return $this->error(500, 'Terjadi kesalahan saat mengambil detail kasir.', [
                'exception' => $e->getMessage(),
            ]);
        }
    }


    public function getHarga(Request $request)
    {
        $request->validate([
            'id_barang' => 'required|string', // QR Code/id_barang dari frontend
            'id_member' => 'required|string',
            'id_toko' => 'required|integer',
        ]);

        $idBarangParam = $request->input('id_barang'); // Format: qr1,qr2,qr3/123
        $memberId = $request->input('id_member');
        $idToko = $request->input('id_toko');

        // Pastikan format benar
        if (!str_contains($idBarangParam, '/')) {
            return response()->json(['error' => 'Format id_barang tidak valid. Gunakan format qrcode1,qrcode2/.../id_barang.'], 400);
        }

        list($qrCodeString, $idBarang) = explode('/', $idBarangParam, 2);
        $qrCodes = array_filter(explode(',', $qrCodeString)); // pisahkan jadi array

        try {
            // Ambil semua detail pembelian berdasarkan qrcode[] dan id_barang
            $barangDetails = DetailPembelianBarang::whereIn('qrcode', $qrCodes)
                ->where('id_barang', $idBarang)
                ->get();

            if ($barangDetails->isEmpty()) {
                return response()->json(['error' => 'Barang tidak ditemukan berdasarkan QR Code.'], 404);
            }

            $barang = Barang::find($idBarang);
            if (!$barang) {
                return response()->json(['error' => 'Barang tidak ditemukan.'], 404);
            }

            $stock = 0;
            if ((int)$idToko === 1) {
                $stock = DetailStockBarang::whereIn('id_detail_pembelian', function ($query) use ($qrCodes) {
                    $query->select('id')
                        ->from('detail_pembelian_barang')
                        ->whereIn('qrcode', $qrCodes);
                })
                    ->where('id_barang', $idBarang)
                    ->where('qty_now', '>', 0)
                    ->sum('qty_now');
            } else {
                $stock = DetailToko::whereIn('qrcode', $qrCodes)
                    ->where('id_toko', $idToko)
                    ->sum('qty');
            }

            // Parsing level harga
            $levelHarga = is_string($barang->level_harga) ? json_decode($barang->level_harga, true) : $barang->level_harga;

            // Jika Guest
            if ($memberId === 'Guest') {
                $filteredHarga = collect($levelHarga)
                    ->sortByDesc(fn($harga) => (int) explode(' : ', $harga)[1])
                    ->values()
                    ->map(fn($harga) => intval(explode(' : ', $harga)[1]));

                return response()->json([
                    'filteredHarga' => $filteredHarga,
                    'id_barang' => $barang->id,
                    'nama_barang' => $barang->nama_barang,
                    'stock' => $stock,
                ]);
            }

            // Member validasi
            $member = Member::find($memberId);
            if (!$member) {
                return response()->json(['error' => 'Member tidak ditemukan.'], 404);
            }

            $levelInfo = is_string($member->level_info) ? json_decode($member->level_info, true) : $member->level_info;
            $jenisBarangId = $barang->id_jenis_barang;

            // Ambil level id yang cocok
            $levelIds = collect($levelInfo)->map(function ($info) use ($jenisBarangId) {
                list($infoJenisBarangId, $infoLevelId) = explode(' : ', $info);
                return intval($infoJenisBarangId) === intval($jenisBarangId) ? intval($infoLevelId) : null;
            })->filter();

            $levelNames = LevelHarga::whereIn('id', $levelIds)->pluck('nama_level_harga');

            // Filter harga
            $filteredHarga = collect($levelHarga)->filter(function ($harga) use ($levelNames) {
                return $levelNames->contains(fn($levelName) => str_contains($harga, $levelName));
            })->map(fn($harga) => intval(explode(' : ', $harga)[1]))->values();

            $response = count($filteredHarga) === 1 ? $filteredHarga->first() : $filteredHarga;

            return response()->json([
                'filteredHarga' => $response,
                'id_barang' => $barang->id,
                'nama_barang' => $barang->nama_barang,
                'stock' => $stock,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan pada server: ' . $e->getMessage(),
                'status_code' => 500,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $idBarangs = array_values(array_filter($request->input('id_barang', []), fn($v) => !empty($v)));
            $qtys = array_values(array_filter($request->input('qty', []), fn($v) => !empty($v)));
            $hargaBarangs = array_values(array_filter($request->input('harga', []), fn($v) => !empty($v)));

            $maxCount = max(count($idBarangs), count($qtys), count($hargaBarangs));
            $idBarangs = $this->fillArrayToMatchCount($idBarangs, $maxCount);
            $qtys = $this->fillArrayToMatchCount($qtys, $maxCount);
            $hargaBarangs = $this->fillArrayToMatchCount($hargaBarangs, $maxCount);

            if (count($idBarangs) !== count($qtys) || count($idBarangs) !== count($hargaBarangs)) {
                return redirect()->back()->with('error', 'Data tidak sinkron.');
            }

            $user = Auth::user();
            $tglTransaksi = Carbon::now();

            $kasir = Kasir::create([
                'id_member' => $request->id_member == 'Guest' ? 0 : $request->id_member,
                'id_users' => $user->id,
                'tgl_transaksi' => $tglTransaksi,
                'id_toko' => $user->id_toko,
                'nama_guest' => $request->nama_guest ?? null,
                'total_item' => 0,
                'total_nilai' => 0,
                'no_nota' => $request->no_nota,
                'metode' => $request->metode,
                'jml_bayar' => (float) str_replace(',', '', $request->jml_bayar),
                'kembalian' => (float) $request->kembalian,
            ]);

            $totalItem = 0;
            $totalNilai = 0;
            $totalDiskon = 0;
            $counter = 1;
            $detailInserted = 0;

            foreach ($idBarangs as $index => $id_barang) {
                $qty = (float) $qtys[$index];
                $harga_barang = (float) $hargaBarangs[$index];
                if (!$qty || !$harga_barang) continue;

                $parts = explode('/', $id_barang);
                $barcodeStr = $parts[0];
                $id_barang_final = end($parts);
                $qrParts = explode(',', $barcodeStr);

                $sisaQty = $qty;
                $subCounter = 1;

                // Promo dan potongan harga
                $promo = Promo::where('id_barang', $id_barang_final)
                    ->where('status', 'ongoing')
                    ->where('dari', '<=', $tglTransaksi)
                    ->where('sampai', '>=', $tglTransaksi)
                    ->where('id_toko', $user->id_toko)
                    ->first();

                $potongan = 0;
                if ($promo && $qty >= $promo->minimal) {
                    $diskon = $promo->diskon;
                    $qtyDiskon = $promo->jumlah ? min($qty, $promo->jumlah - $promo->terjual) : $qty;
                    $potongan = ($harga_barang * $diskon / 100) * $qtyDiskon;
                    $totalDiskon += $potongan;

                    if ($promo->jumlah) {
                        $promo->terjual += $qtyDiskon;
                        if ($promo->terjual >= $promo->jumlah) {
                            $promo->status = 'done';
                        }
                    } else {
                        $promo->terjual += $qty;
                    }

                    $promo->save();
                }

                if ((int) $user->id_toko === 1) {
                    // Pusat: Ambil dari detail_stock
                    $stocks = DB::table('detail_stock as ds')
                        ->join('detail_pembelian_barang as dpb', 'ds.id_detail_pembelian', '=', 'dpb.id')
                        ->where('dpb.id_barang', $id_barang_final)
                        ->whereIn('dpb.qrcode', $qrParts)
                        ->where('ds.qty_now', '>', 0)
                        ->orderByDesc('ds.qty_now')
                        ->select('ds.id', 'ds.qty_now', 'dpb.id as id_detail_pembelian', 'dpb.qrcode', 'dpb.id_supplier')
                        ->get();

                    foreach ($stocks as $stock) {
                        if ($sisaQty <= 0) break;

                        $ambilQty = min($sisaQty, $stock->qty_now);
                        $sisaQty -= $ambilQty;

                        $tglFormat = $tglTransaksi->format('dmY');
                        $qrValue = "{$tglFormat}TK{$user->id_toko}MM{$kasir->id_member}ID{$kasir->id}-{$counter}-{$subCounter}";
                        $qrPath = "qrcodes/trx_kasir/{$kasir->id}-{$counter}-{$subCounter}.png";
                        $qrFullPath = storage_path("app/public/" . $qrPath);

                        if (!file_exists(dirname($qrFullPath))) mkdir(dirname($qrFullPath), 0755, true);

                        $qr = QrCode::create($qrValue)->setEncoding(new Encoding('UTF-8'))->setSize(200)->setMargin(10);
                        $writer = new PngWriter();
                        $writer->write($qr, null, Label::create($qrValue)->setFont(new NotoSans(12)))->saveToFile($qrFullPath);

                        $stockInfo = StockBarang::where('id_barang', $id_barang_final)->first();
                        $hpp_jual = $stockInfo ? $stockInfo->hpp_baru : 0;

                        DetailKasir::create([
                            'id_kasir' => $kasir->id,
                            'id_barang' => $id_barang_final,
                            'id_supplier' => $stock->id_supplier,
                            'id_detail_pembelian' => $stock->id_detail_pembelian,
                            'qty' => $ambilQty,
                            'harga' => $harga_barang,
                            'diskon' => $potongan,
                            'total_harga' => $ambilQty * $harga_barang,
                            'qrcode' => $qrValue,
                            'qrcode_path' => $qrPath,
                            'hpp_jual' => $hpp_jual,
                            'qrcode_pembelian' => $stock->qrcode,
                        ]);

                        // Kurangi stok pusat
                        $detailStock = DetailStockBarang::find($stock->id);
                        if ($detailStock) {
                            $detailStock->qty_now = max(0, $detailStock->qty_now - $ambilQty);
                            $detailStock->qty_out += $ambilQty;
                            $detailStock->save();
                        }
                        StockBarang::where('id_barang', $id_barang_final)->decrement('stock', $ambilQty);

                        $totalItem += $ambilQty;
                        $totalNilai += $ambilQty * $harga_barang;
                        $subCounter++;
                    }

                    if ($sisaQty > 0) {
                        return redirect()->back()->with('error', "Stok pusat tidak mencukupi untuk barang ID: $id_barang_final.");
                    }
                } else {
                    // Cabang
                    $detailTokos = DetailToko::where('id_toko', $user->id_toko)
                        ->whereIn('qrcode', $qrParts)
                        ->where('qty', '>', 0)
                        ->orderByDesc('qty')
                        ->get();

                    foreach ($detailTokos as $detailToko) {
                        if ($sisaQty <= 0) break;

                        $ambilQty = min($sisaQty, $detailToko->qty);
                        $sisaQty -= $ambilQty;

                        $tglFormat = $tglTransaksi->format('dmY');
                        $qrValue = "{$tglFormat}TK{$user->id_toko}MM{$kasir->id_member}ID{$kasir->id}-{$counter}-{$subCounter}";
                        $qrPath = "qrcodes/trx_kasir/{$kasir->id}-{$counter}-{$subCounter}.png";
                        $qrFullPath = storage_path("app/public/" . $qrPath);

                        if (!file_exists(dirname($qrFullPath))) mkdir(dirname($qrFullPath), 0755, true);

                        $qr = QrCode::create($qrValue)->setEncoding(new Encoding('UTF-8'))->setSize(200)->setMargin(10);
                        $writer = new PngWriter();
                        $writer->write($qr, null, Label::create($qrValue)->setFont(new NotoSans(12)))->saveToFile($qrFullPath);

                        $stockInfo = StockBarang::where('id_barang', $id_barang_final)->first();
                        $hpp_jual = $stockInfo ? $stockInfo->hpp_baru : 0;

                        DetailKasir::create([
                            'id_kasir' => $kasir->id,
                            'id_barang' => $id_barang_final,
                            'id_supplier' => $detailToko->id_supplier,
                            'id_detail_pembelian' => null,
                            'qty' => $ambilQty,
                            'harga' => $harga_barang,
                            'diskon' => $potongan,
                            'total_harga' => $ambilQty * $harga_barang,
                            'qrcode' => $qrValue,
                            'qrcode_path' => $qrPath,
                            'hpp_jual' => $hpp_jual,
                            'qrcode_pembelian' => $detailToko->qrcode,
                        ]);

                        $detailToko->decrement('qty', $ambilQty);

                        $totalItem += $ambilQty;
                        $totalNilai += $ambilQty * $harga_barang;
                        $subCounter++;
                    }

                    if ($sisaQty > 0) {
                        return redirect()->back()->with('error', "Stok tidak mencukupi untuk barang ID: $id_barang_final.");
                    }
                }

                $detailInserted++;
                $counter++;
            }

            if ($detailInserted === 0) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Minimal harus ada satu barang dalam transaksi.'
                ], 422);
            }

            // ✅ Ambil nilai akhir transaksi
            $totalTransaksi = $totalNilai - $totalDiskon;
            $toko = \App\Models\Toko::find($user->id_toko);

            // Kalau tidak menerima kasbon (false)
            if (!$toko->kasbon && $kasir->jml_bayar < $totalTransaksi) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Toko ini tidak menerima kasbon, jumlah bayar minimal harus >= SubTotal Transaksi.'
                ], 422);
            }

            // Hitung kembalian
            $kembalian = $kasir->jml_bayar - $totalTransaksi;
            $kasir->update([
                'total_item' => $totalItem,
                'total_nilai' => $totalNilai,
                'total_diskon' => $totalDiskon,
                'kembalian' => $kembalian > 0 ? $kembalian : 0,
            ]);

            // Kalau toko menerima kasbon (true) dan jml_bayar kurang → buat kasbon
            if ($toko->kasbon && $kasir->jml_bayar < $totalTransaksi) {
                $sisaBayar = $totalTransaksi - $kasir->jml_bayar;

                Kasbon::create([
                    'id_kasir'   => $kasir->id,
                    'id_member'  => $kasir->id_member,
                    'utang'      => $sisaBayar,
                    'utang_sisa' => $sisaBayar,
                    'status'     => 'BL',
                ]);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Data berhasil disimpan', 'data' => $kasir]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Gagal simpan transaksi', ['error' => $th->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan transaksi', 'error' => $th->getMessage()], 500);
        }
    }

    private function fillArrayToMatchCount(array $array, int $count)
    {
        while (count($array) < $count) {

            $array[] = end($array) ?: 0;
        }

        return $array;
    }
}
