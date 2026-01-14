<?php

namespace App\Http\Controllers\JurnalKeuangan;

use App\Enums\{Jangka, StatusLunas, TipeKas};
use App\Helpers\{KasJenisBarangGenerate, KasGenerate, LogAktivitasGenerate, RupiahGenerate, UserIdentify, GroupIdentify};
use App\Http\Controllers\Controller;
use App\Models\{PengirimanBarang, PiutangDetail, Piutang};
use App\Services\KasService;
use App\Traits\{ApiResponse, HasJenisBarang};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PiutangController extends Controller
{
    use ApiResponse;
    use HasJenisBarang;

    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Piutang',
            'Tambah Data',
            'Edit Data'
        ];
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[5]];

        return view('piutang.index', compact('menu'));
    }

    public function get(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Piutang::query()->where('toko_id', $request->toko_id)
            ->orderBy('id', $meta['orderBy']);

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(keterangan) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereHas('toko', function ($subquery) use ($searchTerm) {
                    $subquery->whereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]);
                });
                $query->orWhereHas('piutangTipe', function ($subquery) use ($searchTerm) {
                    $subquery->whereRaw("LOWER(tipe) LIKE ?", ["%$searchTerm%"]);
                });
            });
        }

        if ($request->has('toko_id')) {
            $idToko = $request->input('toko_id');
            $query->where('toko_id', $idToko);
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            $query->where(function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        if ($request->has('startDate') && $request->has('endDate')) {
            $startDate = $request->input('startDate');
            $endDate = $request->input('endDate');

            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        $totalNilai = $query->sum('nominal');
        $totalSisa = $totalNilai - PiutangDetail::whereIn('piutang_id', $query->pluck('id'))->sum('nominal');
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
                'tipe' => optional($item->piutangTipe)->tipe ?? '-',
                'keterangan' => $item->keterangan,
                'text' => "{$status} - Piutang {$type}",
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
                'total_nilai' => 'Rp. ' . number_format($totalNilai, 0, '.', '.'),
                'total_sisa' => 'Rp. ' . number_format($totalSisa, 0, '.', '.'),
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
            'piutang_tipe_id' => 'nullable|exists:piutang_tipe,id',
            'jangka' => 'nullable|in:panjang,pendek',
            'jenis_barang_id' => 'required|integer',
            'tipe_kas' => 'required',
        ];
        $validatedData = $request->validate($validation);
        $nominal = RupiahGenerate::build($validatedData['nominal']);

        try {
            DB::beginTransaction();

            $data = Piutang::create([
                'kas_id' => $validatedData['kas_id'],
                'toko_id' => $validatedData['toko_id'],
                'piutang_tipe_id' => $validatedData['piutang_tipe_id'],
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
                logName: $this->title[0] ?? 'Piutang',
                subjectType: Piutang::class,
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
                item: 'piutang',
                kategori: $this->title[0],
                keterangan: $data->piutangTipe->tipe ?? 'Piutang Lainnya',
                sumber: $data,
                tanggal: $validatedData['tanggal'],
            );

            KasService::out(
                toko_id: $validatedData['toko_id'],
                jenis_barang_id: $data->kas->jenis_barang_id,
                tipe_kas: $validatedData['tipe_kas'],
                total_nominal: $validatedData['nominal'],
                item: $validatedData['tipe_kas'],
                kategori: $this->title[0],
                keterangan: $data->piutangTipe->tipe ?? 'Piutang Lainnya',
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
            $piutang = Piutang::findOrFail($request->id);
            $detailPembayaran = PiutangDetail::where('piutang_id', $request->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'nominal' => RupiahGenerate::build($item->nominal),
                        'tanggal' => Carbon::parse($item->created_at)->format('d-m-Y H:i:s')
                    ];
                });

            $totalPembayaran = $piutang->nominal - $piutang->sisa;

            $labelValue = $piutang->kas->tipe_kas;
            $labelText = $labelValue ? TipeKas::from($labelValue)->label() : '-';

            $data = [
                'item' => [
                    'id' => $piutang->id,
                    'nama_toko' => $piutang->toko->nama,
                    'keterangan' => $piutang->keterangan ?? '-',
                    'tipe' => $piutang->piutangTipe->tipe ?? '-',
                    'nominal' => RupiahGenerate::build($piutang->nominal),
                    'status' => $piutang->status,
                    'jangka' => $piutang->jangka,
                    'tanggal' => $piutang->tanggal->format('d-m-Y H:i:s'),
                ],
                'detail_pembayaran' => $detailPembayaran,
                'total_pembayaran' => RupiahGenerate::build($totalPembayaran),
                'sisa' => RupiahGenerate::build($piutang->sisa),
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
        $piutang = Piutang::findOrFail($request->id);

        if ($validatedData['nominal'] > $piutang->sisa) {
            throw new \Exception('Nominal bayar melebihi sisa piutang!');
        }

        try {
            DB::beginTransaction();

            $piutang->decrement('sisa', $validatedData['nominal']);

            PiutangDetail::create([
                'piutang_id' => $piutang->id,
                'nominal' => $validatedData['nominal'],
            ]);

            $totalBayar = PiutangDetail::where('piutang_id', $piutang->id)->sum('nominal');

            if ($totalBayar >= $piutang->nominal) {
                $piutang->update(['status' => 1]);
            }

            KasService::neutralOUT(
                toko_id: $piutang->toko_id,
                jenis_barang_id: $piutang->kas->jenis_barang_id,
                tipe_kas: $piutang->kas->tipe_kas,
                total_nominal: $validatedData['nominal'],
                item: 'piutang',
                kategori: 'Pembayaran Piutang',
                keterangan: $piutang->keterangan,
                sumber: $piutang,
                tanggal: now()
            );

            KasService::in(
                toko_id: $piutang->toko_id,
                jenis_barang_id: $piutang->kas->jenis_barang_id,
                tipe_kas: $piutang->kas->tipe_kas,
                total_nominal: $validatedData['nominal'],
                item: $piutang->kas->tipe_kas,
                kategori: 'Pembayaran Piutang',
                keterangan: $piutang->keterangan,
                sumber: $piutang,
                tanggal: now()
            );
            DB::commit();
            return $this->success(null, 200, 'Berhasil melakukan pembayaran piutang');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Gagal melakukan pembayaran: ' . $e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = Piutang::findOrFail($request->id);

            KasService::delete($data->kas_id, $data->id, Piutang::class, $data->tanggal);

            $kas = KasJenisBarangGenerate::labelForKas($data);
            $nominal = RupiahGenerate::build($data->nominal);

            $description = "{$this->title[0]} dihapus dari {$kas} senilai Rp {$nominal} (ID {$data->id})";

            LogAktivitasGenerate::store(
                logName: $this->title[0] ?? 'Piutang',
                subjectType: Piutang::class,
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
