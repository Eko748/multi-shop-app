<?php

namespace App\Http\Controllers\JurnalKeuangan;

use App\Enums\{Jangka, StatusLunas, TipeKas};
use App\Helpers\{KasJenisBarangGenerate, KasGenerate, LogAktivitasGenerate, RupiahGenerate, UserIdentify, GroupIdentify};
use App\Http\Controllers\Controller;
use App\Models\{Hutang, HutangDetail, Kas};
use App\Models\{PembelianBarang, PengirimanBarang, Piutang, PiutangDetail};
use App\Services\KasService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HutangController extends Controller
{
    use ApiResponse;
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Hutang',
            'Tambah Data',
            'Edit Data'
        ];
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[5]];

        return view('hutang.index', compact('menu'));
    }

    public function get(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Hutang::query()->orderBy('status');

        if ($request->toko_id != 1) {
            $query->where('toko_id', $request->toko_id);
        }

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));
            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(keterangan) LIKE ?", ["%$searchTerm%"])
                    ->orWhereHas('toko', fn($q) => $q->whereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]))
                    ->orWhereHas('hutangTipe', fn($q) => $q->whereRaw("LOWER(tipe) LIKE ?", ["%$searchTerm%"]));
            });
        }

        if ($request->filled('toko') && $request->id == 1) {
            $query->whereIn('toko_id', $request->input('toko'));
        }

        if ($request->has('jenis')) {
            $query->where('hutang_tipe_id', $request->input('jenis'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('startDate') && $request->has('endDate')) {
            $query->whereBetween('tanggal', [$request->input('startDate'), $request->input('endDate')]);
        }

        $hutangList = $query->get();
        $totalNilai = $hutangList->sum('nominal');

        $totalSisa = $hutangList->sum(function ($item) {
            return $item->nominal - HutangDetail::where('hutang_id', $item->id)->sum('nominal');
        });

        $data = $query->paginate($meta['limit']);

        $paginationMeta = [
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'total_pages' => $data->lastPage()
        ];

        if ($data->isEmpty()) {
            return $this->error(404, 'Tidak ada data');
        }

        $mappedData = collect($data->items())->map(function ($item) use ($request) {
            $status     = StatusLunas::from($item->status)->label();
            $attr       = StatusLunas::from($item->status)->attr();
            $icon       = StatusLunas::from($item->status)->icon();
            $type       = StatusLunas::from($item->status)->type();
            $identify   = StatusLunas::from($item->status)->identify();
            $access     = UserIdentify::access($item->toko_id, $request->toko_id);
            $action     = GroupIdentify::andStrict($access, $identify);
            $kas        = KasJenisBarangGenerate::labelForKas($item);
            $jangka     = Jangka::from($item->jangka)->labelSort();

            return [
                'id' => $item->id,
                'toko_id' => $item->toko_id,
                'nama_toko' => optional($item->toko)->nama,
                'tipe' => optional($item->hutangTipe)->tipe ?? '-',
                'keterangan' => $item->keterangan,
                'text' => "{$status} - Hutang {$type}",
                'status' => $item->status,
                'jangka' => $jangka,
                'tanggal' => $item->tanggal->format('d-m-Y H:i:s'),
                'nominal' => RupiahGenerate::build($item->nominal),
                'sisa' =>  RupiahGenerate::build($item->sisa),
                'kas' => $kas,
                'attr' => $attr,
                'icon' => $icon,
                'action' => $action,
            ];
        });

        return $this->success(
            [
                'data' => $mappedData,
                'total_nominal' => 'Rp. ' . number_format($totalNilai, 0, '.', '.'),
                'total_sisa' => 'Rp. ' . number_format($totalSisa, 0, '.', '.')
            ],
            200,
            'Sukses',
            $paginationMeta
        );
    }

    public function post(Request $request)
    {
        $validation = [
            'created_by' => 'required|exists:users,id',
            'kas_id' => 'required',
            'toko_id' => 'required|exists:toko,id',
            'keterangan' => 'required|string',
            'nominal' => 'required|numeric',
            'tanggal' => 'required|date',
            'hutang_tipe_id' => 'nullable|exists:hutang_tipe,id',
            'jangka' => 'nullable|in:panjang,pendek',
            'jenis_barang_id' => 'required|integer',
            'tipe_kas' => 'required',
        ];

        $validatedData = $request->validate($validation);
        $nominal = RupiahGenerate::build($validatedData['nominal']);

        try {
            DB::beginTransaction();

            $validatedData['kas_id'] = KasGenerate::resolveKasId(
                kasId: $validatedData['kas_id'],
                tokoId: $validatedData['toko_id'],
                jenisBarangId: $validatedData['jenis_barang_id'],
                tipeKas: $validatedData['tipe_kas'],
                tanggal: $validatedData['tanggal'],
            );

            $data = Hutang::create([
                'kas_id' => $validatedData['kas_id'],
                'toko_id' => $validatedData['toko_id'],
                'hutang_tipe_id' => $validatedData['hutang_tipe_id'],
                'keterangan' => $validatedData['keterangan'],
                'nominal' => $validatedData['nominal'],
                'sisa' => $validatedData['nominal'],
                'status' => false,
                'jangka' => $validatedData['jangka'] ?? null,
                'tanggal' => $validatedData['tanggal'],
                'created_by' => $validatedData['created_by'],
            ]);

            $kas    = KasJenisBarangGenerate::labelForKas($data);

            $description = "{$this->title[0]} ditambahkan pada {$kas} senilai Rp {$nominal} (ID {$data->id})";

            LogAktivitasGenerate::store(
                logName: $this->title[0] ?? 'Hutang',
                subjectType: Hutang::class,
                subjectId: $data->id,
                event: 'Tambah Data',
                properties: [
                    'changes' => [
                        'new' => [
                            'nominal' => $data->nominal,
                            'tanggal' => $data->tanggal,
                            'kas' => $kas
                        ],
                    ]
                ],
                description: $description,
                userId: $request->created_by,
                message: filled($request->keterangan)
                    ? $request->keterangan
                    : "(Sistem) {$this->title[0]} dibuat."
            );

            KasService::neutralIN(
                toko_id: $validatedData['toko_id'],
                jenis_barang_id: $data->kas->jenis_barang_id,
                tipe_kas: $validatedData['tipe_kas'],
                total_nominal: $validatedData['nominal'],
                item: 'hutang',
                kategori: $this->title[0],
                keterangan: $data->hutangTipe->tipe ?? 'Hutang Lainnya',
                sumber: $data,
                tanggal: $validatedData['tanggal'],
            );

            KasService::in(
                toko_id: $validatedData['toko_id'],
                jenis_barang_id: $data->kas->jenis_barang_id,
                tipe_kas: $validatedData['tipe_kas'],
                total_nominal: $validatedData['nominal'],
                item: $validatedData['tipe_kas'],
                kategori: $this->title[0],
                keterangan: $data->hutangTipe->tipe ?? 'Hutang Lainnya',
                sumber: $data,
                tanggal: $validatedData['tanggal'],
            );

            DB::commit();
            return $this->success(null, 200, 'Data berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Terjadi kesalahan saat menyimpan data:' . $e->getMessage());
        }
    }

    public function detail(Request $request)
    {
        try {
            $hutang = Hutang::with(['toko', 'hutangTipe'])->findOrFail($request->id);
            $detailPembayaran = HutangDetail::where('hutang_id', $request->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'nominal' => RupiahGenerate::build($item->nominal),
                        'tanggal' => Carbon::parse($item->created_at)->format('d-m-Y H:i:s')
                    ];
                });

            $totalPembayaran = $hutang->nominal - $hutang->sisa;

            $labelValue = $hutang->kas->tipe_kas;
            $labelText = $labelValue ? TipeKas::from($labelValue)->label() : '-';

            $data = [
                'item' => [
                    'id' => $hutang->id,
                    'nama_toko' => $hutang->toko->nama,
                    'keterangan' => $hutang->keterangan ?? '-',
                    'tipe' => $hutang->hutangTipe->tipe ?? '-',
                    'nominal' => RupiahGenerate::build($hutang->nominal),
                    'status' => $hutang->status,
                    'jangka' => $hutang->jangka,
                    'tanggal' => $hutang->tanggal->format('d-m-Y H:i:s'),
                ],
                'detail_pembayaran' => $detailPembayaran,
                'total_pembayaran' => RupiahGenerate::build($totalPembayaran),
                'sisa' => RupiahGenerate::build($hutang->sisa),
                'kas' => $labelText,
            ];

            return $this->success($data, 200, 'Data berhasil ditemukan!');
        } catch (\Exception $e) {
            return $this->error(500, 'Terjadi kesalahan saat mengambil data:' . $e->getMessage());
        }
    }

    public function pay(Request $request)
    {
        $validation = [
            'nominal' => 'required|numeric',
        ];

        $validatedData = $request->validate($validation);

        $hutang = Hutang::findOrFail($request->id);
        $kas = Kas::find($hutang->kas_id);
        $labelKas = KasJenisBarangGenerate::labelForKas($kas);

        if ($validatedData['nominal'] > $hutang->nominal) {
            throw new \Exception('Nominal bayar melebihi nominal hutang!');
        }

        if ($validatedData['nominal'] > $kas->saldo) {
            $saldo = "{$labelKas} " . RupiahGenerate::build($kas->saldo);
            throw new \Exception("Sisa {$saldo} tidak mencukupi, silahkan Mutasi Kas terlebih dahulu.");
        }

        try {
            DB::beginTransaction();

            $hutang->decrement('sisa', $validatedData['nominal']);

            HutangDetail::create([
                'hutang_id' => $hutang->id,
                'nominal' => $validatedData['nominal'],
            ]);

            $totalBayar = HutangDetail::where('hutang_id', $hutang->id)->sum('nominal');

            if ($totalBayar >= $hutang->nominal) {
                $hutang->update(['status' => 1]);

                if ($hutang->sumber_type == PembelianBarang::class) {
                    $pembelian = PembelianBarang::find($hutang->sumber_id);

                    if ($pembelian) {
                        $pembelian->status = 'success_debt';
                        $pembelian->save();
                    }
                }
            }

            if ($hutang->sumber_type == PengirimanBarang::class) {
                $pengiriman = PengirimanBarang::find($hutang->sumber_id);
                $piutang    = Piutang::where('sumber_type', PengirimanBarang::class)->where('sumber_id', $pengiriman->id)->first();

                PiutangDetail::create([
                    'piutang_id' => $hutang->id,
                    'nominal' => $validatedData['nominal'],
                ]);

                $totalBayarPiutang = PiutangDetail::where('piutang_id', $piutang->id)->sum('nominal');

                if ($totalBayarPiutang >= $piutang->nominal) {
                    $piutang->update(['status' => 1]);
                }

                KasService::neutralOUT(
                    toko_id: $piutang->toko_id,
                    jenis_barang_id: $piutang->kas->jenis_barang_id,
                    tipe_kas: $piutang->kas->tipe_kas,
                    total_nominal: $validatedData['nominal'],
                    item: 'piutang',
                    kategori: 'Pembayaran Piutang',
                    keterangan: $piutang->piutangTipe->tipe,
                    sumber: $piutang,
                    tanggal: $piutang->tanggal
                );

                KasService::in(
                    toko_id: $piutang->toko_id,
                    jenis_barang_id: $piutang->kas->jenis_barang_id,
                    tipe_kas: $piutang->kas->tipe_kas,
                    total_nominal: $validatedData['nominal'],
                    item: $piutang->kas->tipe_kas,
                    kategori: 'Pembayaran Piutang',
                    keterangan: $piutang->piutangTipe->tipe,
                    sumber: $piutang,
                    tanggal: $piutang->tanggal
                );
            }

            KasService::neutralOUT(
                toko_id: $hutang->toko_id,
                jenis_barang_id: $hutang->kas->jenis_barang_id,
                tipe_kas: $hutang->kas->tipe_kas,
                total_nominal: $validatedData['nominal'],
                item: 'hutang',
                kategori: 'Pembayaran Hutang',
                keterangan: $hutang->hutangTipe->tipe,
                sumber: $hutang,
                tanggal: now()
            );

            KasService::out(
                toko_id: $hutang->toko_id,
                jenis_barang_id: $hutang->kas->jenis_barang_id,
                tipe_kas: $hutang->kas->tipe_kas,
                total_nominal: $validatedData['nominal'],
                item: $hutang->kas->tipe_kas,
                kategori: 'Pembayaran Hutang',
                keterangan: $hutang->hutangTipe->tipe,
                sumber: $hutang,
                tanggal: now()
            );

            DB::commit();
            return $this->success(null, 200, 'Berhasil melakukan pembayaran hutang');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Gagal melakukan pembayaran: ' . $e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = Hutang::findOrFail($request->id);

            KasService::delete($data->kas_id, $data->id, Hutang::class, $data->tanggal);

            $kas = KasJenisBarangGenerate::labelForKas($data);
            $nominal = RupiahGenerate::build($data->nominal);

            $description = "{$this->title[0]} dihapus dari {$kas} senilai Rp {$nominal} (ID {$data->id})";

            LogAktivitasGenerate::store(
                logName: $this->title[0] ?? 'Hutang',
                subjectType: Hutang::class,
                subjectId: $data->id,
                event: 'Hapus Data',
                properties: [
                    'changes' => [
                        'old' => [
                            'nominal' => $data->nominal,
                            'tanggal' => $data->tanggal,
                            'kas' => $kas,
                        ],
                    ],
                ],
                description: $description,
                userId: $request->deleted_by,
                message: "(Sistem) {$this->title[0]} dihapus."
            );

            $data->delete();

            DB::commit();
            return $this->success(null, 200, 'Data berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
        }
    }
}
