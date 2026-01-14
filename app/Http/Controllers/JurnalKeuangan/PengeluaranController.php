<?php

namespace App\Http\Controllers\JurnalKeuangan;

use App\Helpers\{RupiahGenerate, KasJenisBarangGenerate, LogAktivitasGenerate};
use App\Http\Controllers\Controller;
use App\Models\Pengeluaran;
use App\Services\KasService;
use App\Traits\{ApiResponse, HasJenisBarang};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengeluaranController extends Controller
{
    use ApiResponse;
    use HasJenisBarang;
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Pengeluaran Lainnya',
            'Tambah Data',
            'Edit Data'
        ];
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[5]];
        return view('pengeluaran.index', compact('menu'));
    }

    public function get(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Pengeluaran::query()
            ->orderBy('id', $meta['orderBy']);

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));
            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(keterangan) LIKE ?", ["%$searchTerm%"])
                    ->orWhereHas('toko', fn($q) => $q->whereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]))
                    ->orWhereHas('pengeluaranTipe', fn($q) => $q->whereRaw("LOWER(tipe) LIKE ?", ["%$searchTerm%"]));
            });
        }

        if ($request->has('toko_id') && $request->input('toko_id') != 1) {
            $query->where('toko_id', $request->input('toko_id'));
        }

        if ($request->has('toko')) $query->where('toko_id', $request->input('toko'));
        if ($request->has('jenis')) $query->where('pengeluaran_tipe_id', $request->input('jenis'));
        if ($request->has('startDate') && $request->has('endDate')) {
            $query->whereBetween('tanggal', [$request->input('startDate'), $request->input('endDate')]);
        }

        $totalNilai = $query->sum('nominal');
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

        $mappedData = collect($data->items())->map(function ($item) {
            $kas    = KasJenisBarangGenerate::labelForKas($item, 'OUT');

            return [
                'id' => $item->id,
                'toko_id' => $item->toko_id,
                'nama_toko' => optional($item->toko)->nama,
                'created_by' => optional($item->createdBy)->nama,
                'keterangan' => $item->keterangan ?? '-',
                'tipe' => optional($item->pengeluaranTipe)->tipe ?? '-',
                'nominal' => 'Rp. ' . number_format($item->nominal ?? 0, 0, '.', '.'),
                'tanggal' => $item->tanggal->format('d-m-Y H:i:s'),
                'kas' => $kas,
                'attr' => 'danger',
            ];
        });

        return $this->success(
            [
                'data' => $mappedData,
                'total_nominal' => 'Rp. ' . number_format($totalNilai, 0, '.', '.')
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
            'keterangan' => 'nullable|string',
            'nominal' => 'required|numeric',
            'tanggal' => 'required|date',
            'aset' => 'nullable|string',
            'pengeluaran_tipe_id' => 'nullable|exists:pengeluaran_tipe,id',
            'jenis_barang_id' => 'required|integer',
            'tipe_kas' => 'required',
            'saldo_kas' => 'required|numeric',
        ];

        $validatedData = $request->validate($validation);

        $saldo = RupiahGenerate::build($validatedData['saldo_kas']);
        $nominal = RupiahGenerate::build($validatedData['nominal']);

        if ($validatedData['saldo_kas'] < $validatedData['nominal']) {
            return $this->error(400, "Saldo Kas {$saldo} < Nominal {$nominal}");
        }

        try {
            DB::beginTransaction();

            $pengeluaran = Pengeluaran::create([
                'kas_id' => $validatedData['kas_id'],
                'toko_id' => $validatedData['toko_id'],
                'pengeluaran_tipe_id' => $validatedData['pengeluaran_tipe_id'],
                'keterangan' => $validatedData['keterangan'],
                'nominal' => $validatedData['nominal'],
                'tanggal' => $validatedData['tanggal'],
                'aset' => $validatedData['aset'] ?? null,
                'created_by' => $validatedData['created_by'],
            ]);

            $kas    = KasJenisBarangGenerate::labelForKas($pengeluaran);

            $description = "{$this->title[0]} ditambahkan pada {$kas} senilai {$nominal} (ID {$pengeluaran->id})";

            LogAktivitasGenerate::store(
                logName: $this->title[0] ?? 'Pengeluaran',
                subjectType: Pengeluaran::class,
                subjectId: $pengeluaran->id,
                event: 'Tambah Data',
                properties: [
                    'changes' => [
                        'new' => [
                            'nominal' => $pengeluaran->nominal,
                            'tanggal' => $pengeluaran->tanggal,
                            'kas' => $kas,
                        ],
                    ]
                ],
                description: $description,
                userId: $request->created_by,
                message: filled($request->keterangan)
                    ? $request->keterangan
                    : "(Sistem) {$this->title[0]} dibuat."
            );

            KasService::out(
                toko_id: $validatedData['toko_id'],
                jenis_barang_id: $pengeluaran->kas->jenis_barang_id,
                tipe_kas: $pengeluaran->kas->tipe_kas,
                total_nominal: $validatedData['nominal'],
                item: $pengeluaran->kas->tipe_kas,
                kategori: $this->title[0],
                keterangan: $pengeluaran->pengeluaranTipe->tipe ?? 'Pengeluaran Harian Lainnya',
                sumber: $pengeluaran,
                tanggal: $validatedData['tanggal'],
            );

            DB::commit();
            return $this->success(null, 200, 'Data berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = Pengeluaran::findOrFail($request->id);

            KasService::delete($data->kas_id, $data->id, Pengeluaran::class, $data->tanggal);

            $kas = KasJenisBarangGenerate::labelForKas($data);
            $nominal = RupiahGenerate::build($data->nominal);

            $description = "{$this->title[0]} dihapus dari {$kas} senilai Rp {$nominal} (ID {$data->id})";

            LogAktivitasGenerate::store(
                logName: $this->title[0] ?? 'Pengeluaran',
                subjectType: Pengeluaran::class,
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
            return response()->json([
                'success' => true,
                'message' => 'Sukses menghapus data pengeluaran'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data pengeluaran: ' . $th->getMessage()
            ], 500);
        }
    }
}
