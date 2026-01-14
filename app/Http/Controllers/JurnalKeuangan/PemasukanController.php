<?php

namespace App\Http\Controllers\JurnalKeuangan;

use App\Helpers\{KasGenerate, KasJenisBarangGenerate, LogAktivitasGenerate, RupiahGenerate};
use App\Http\Controllers\Controller;
use App\Models\Pemasukan;
use App\Services\KasService;
use App\Traits\{ApiResponse};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemasukanController extends Controller
{
    use ApiResponse;
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Pemasukan Lainnya',
            'Tambah Data',
            'Edit Data'
        ];
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[5]];
        return view('pemasukan.index', compact('menu'));
    }

    public function get(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Pemasukan::query()
            ->orderBy('id', $meta['orderBy']);

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));
            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(keterangan) LIKE ?", ["%$searchTerm%"])
                    ->orWhereHas('toko', fn($q) => $q->whereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]))
                    ->orWhereHas('pemasukanTipe', fn($q) => $q->whereRaw("LOWER(tipe) LIKE ?", ["%$searchTerm%"]));
            });
        }

        if ($request->has('toko_id') && $request->input('toko_id') != 1) {
            $query->where('toko_id', $request->input('toko_id'));
        }

        if ($request->has('toko')) $query->where('toko_id', $request->input('toko'));
        if ($request->has('jenis')) $query->where('pemasukan_tipe_id', $request->input('jenis'));
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
            $kas    = KasJenisBarangGenerate::labelForKas($item, 'IN');

            return [
                'id' => $item->id,
                'toko_id' => $item->toko_id,
                'nama_toko' => optional($item->toko)->nama,
                'created_by' => optional($item->createdBy)->nama,
                'keterangan' => $item->keterangan ?? '-',
                'tipe' => optional($item->pemasukanTipe)->tipe ?? '-',
                'nominal' => 'Rp. ' . number_format($item->nominal ?? 0, 0, '.', '.'),
                'tanggal' => $item->tanggal->format('d-m-Y H:i:s'),
                'kas' => $kas,
                'attr' => 'success',
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
            'keterangan' => 'required|string',
            'nominal' => 'required|numeric',
            'tanggal' => 'required|date',
            'pemasukan_tipe_id' => 'required|exists:pemasukan_tipe,id',
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

            $pemasukan = Pemasukan::create([
                'kas_id' => $validatedData['kas_id'],
                'toko_id' => $validatedData['toko_id'],
                'pemasukan_tipe_id' => $validatedData['pemasukan_tipe_id'],
                'keterangan' => $validatedData['keterangan'],
                'nominal' => $validatedData['nominal'],
                'tanggal' => $validatedData['tanggal'],
                'created_by' => $validatedData['created_by'],
            ]);

            $kas    = KasJenisBarangGenerate::labelForKas($pemasukan);

            $description = "{$this->title[0]} ditambahkan pada {$kas} senilai Rp {$nominal} (ID {$pemasukan->id})";

            LogAktivitasGenerate::store(
                logName: $this->title[0] ?? 'Pemasukan',
                subjectType: Pemasukan::class,
                subjectId: $pemasukan->id,
                event: 'Tambah Data',
                properties: [
                    'changes' => [
                        'new' => [
                            'nominal' => $pemasukan->nominal,
                            'tanggal' => $pemasukan->tanggal,
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

            $laba = !in_array((int) $validatedData['pemasukan_tipe_id'], [1, 2], true);

            KasService::in(
                toko_id: $validatedData['toko_id'],
                jenis_barang_id: $pemasukan->kas->jenis_barang_id,
                tipe_kas: $validatedData['tipe_kas'],
                total_nominal: $validatedData['nominal'],
                item: $validatedData['tipe_kas'],
                kategori: $this->title[0],
                keterangan: $pemasukan->pemasukanTipe->tipe ?? 'Pendapatan Harian Lainnya',
                sumber: $pemasukan,
                tanggal: $validatedData['tanggal'],
                laba: $laba
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
            $data  = Pemasukan::findOrFail($request->id);

            KasService::delete($data->kas_id, $data->id, Pemasukan::class, $data->tanggal);

            $kas = KasJenisBarangGenerate::labelForKas($data);
            $nominal = RupiahGenerate::build($data->nominal);

            $description = "{$this->title[0]} dihapus dari {$kas} senilai Rp {$nominal} (ID {$data->id})";

            LogAktivitasGenerate::store(
                logName: $this->title[0] ?? 'Pemasukan',
                subjectType: Pemasukan::class,
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
