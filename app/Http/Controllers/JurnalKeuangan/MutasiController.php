<?php

namespace App\Http\Controllers\JurnalKeuangan;

use App\Helpers\{KasJenisBarangGenerate, LogAktivitasGenerate, RupiahGenerate};
use App\Http\Controllers\Controller;
use App\Models\{Kas, KasMutasi};
use App\Services\KasService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MutasiController extends Controller
{
    use ApiResponse;
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Mutasi Kas',
        ];
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[5]];

        return view('mutasi.index', compact('menu'));
    }

    public function get(Request $request)
    {
        try {
            $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
            $meta['limit'] = ($request->has('limit') && $request->limit <= 30) ? $request->limit : 30;

            $query = KasMutasi::query()->with([
                'kasAsal.toko',
                'kasAsal.jenisBarang',
                'kasTujuan.toko',
                'kasTujuan.jenisBarang'
            ]);

            /** -------------------------------
             *  SEARCH
             * --------------------------------*/
            if (!empty($request['search'])) {
                $searchTerm = '%' . strtolower(trim($request['search'])) . '%';
                $query->whereRaw("LOWER(keterangan) LIKE ?", [$searchTerm]);
            }

            /** -------------------------------
             *  FILTER TOKO LOGIN
             *  - Parent bisa lihat semua
             *  - Child hanya lihat mutasi yang melibatkan tokonya
             * --------------------------------*/
            if ($request->has('id_toko')) {
                $idToko = $request->input('id_toko');

                $query->where(function ($q) use ($idToko) {
                    $q->whereHas('kasAsal', function ($s) use ($idToko) {
                        $s->where('toko_id', $idToko);
                    })->orWhereHas('kasTujuan', function ($s) use ($idToko) {
                        $s->where('toko_id', $idToko);
                    });
                });
            }

            /** -------------------------------
             *  FILTER TOKO (UNTUK DROPDOWN)
             * --------------------------------*/
            if ($request->has('toko')) {
                $idToko = $request->input('toko');

                $query->where(function ($q) use ($idToko) {
                    $q->whereHas('kasAsal', function ($s) use ($idToko) {
                        $s->where('toko_id', $idToko);
                    })->orWhereHas('kasTujuan', function ($s) use ($idToko) {
                        $s->where('toko_id', $idToko);
                    });
                });
            }

            /** -------------------------------
             *  FILTER RANGE TANGGAL
             * --------------------------------*/
            if ($request->startDate && $request->endDate) {
                $query->whereBetween('tanggal', [
                    $request->startDate,
                    $request->endDate
                ]);
            }

            /** -------------------------------
             *  SUM NILAI + PAGINASI
             * --------------------------------*/
            $totalNilai = $query->sum('nominal');
            $data = $query->orderBy('tanggal', $meta['orderBy'])->paginate($meta['limit']);

            if ($data->isEmpty()) {
                return $this->error(404, 'Tidak ada data');
            }

            /** -------------------------------
             *  MAPPING DATA RESPONSE BARU
             * --------------------------------*/
            $mapped = $data->getCollection()->map(function ($item) {
                $kasAsal = KasJenisBarangGenerate::labelForKasMutasi($item, 'OUT');
                $kasTujuan = KasJenisBarangGenerate::labelForKasMutasi($item, 'IN');
                $asalToko = $item->kasAsal->toko->singkatan ?? '-';
                $tujuanToko = $item->kasTujuan->toko->singkatan ?? '-';

                return [
                    'id' => $item->id,
                    'kas_asal_id' => $item->kas_asal_id,
                    'nama_toko_pengirim' => "{$asalToko} - {$kasAsal}",
                    'kas_tujuan_id' => $item->kas_tujuan_id,
                    'nama_toko_penerima' => "{$tujuanToko} - {$kasTujuan}",
                    'nominal' => 'Rp. ' . number_format($item->nominal, 0, ',', '.'),
                    'keterangan' => $item->keterangan,
                    'tanggal' => $item->tanggal->format('d-m-Y H:i:s'),
                    'attr_out' => 'danger',
                    'attr_in' => 'success',
                ];
            });

            /** -------------------------------
             *  RESPONSE
             * --------------------------------*/
            return $this->success(
                [
                    'data' => $mapped,
                    'total_nilai' => 'Rp. ' . number_format($totalNilai, 0, ',', '.')
                ],
                200,
                'Sukses',
                [
                    'total' => $data->total(),
                    'per_page' => $data->perPage(),
                    'current_page' => $data->currentPage(),
                    'total_pages' => $data->lastPage()
                ]
            );
        } catch (\Throwable $e) {
            return $this->error(500, 'Terjadi kesalahan', [
                'msg' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
        }
    }

    public function post(Request $request)
    {
        $request->validate([
            'created_by' => 'required|exists:users,id',
            'kas_asal_id' => 'required',
            'kas_tujuan_id' => 'required',
            'tanggal' => 'required',
            'nominal' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $mutasi = KasMutasi::create([
                'kas_asal_id' => $request->kas_asal_id,
                'kas_tujuan_id' => $request->kas_tujuan_id,
                'nominal' => $request->nominal,
                'keterangan' => $request->keterangan,
                'tanggal' => $request->tanggal,
                'created_by' => $request->created_by,
            ]);

            $nominal = RupiahGenerate::build($mutasi->nominal);

            $fromKas = Kas::find($request->kas_asal_id);
            $toKas = Kas::find($request->kas_tujuan_id);

            $tipeKasAsal = KasJenisBarangGenerate::labelForKas($fromKas);
            $tipeKasTujuan = KasJenisBarangGenerate::labelForKas($toKas);

            $description = "{$this->title[0]} senilai {$nominal} (ID {$mutasi->id}) dari {$tipeKasAsal} ke {$tipeKasTujuan}.";

            LogAktivitasGenerate::store(
                logName: $this->title[0],
                subjectType: KasMutasi::class,
                subjectId: $mutasi->id,
                event: 'Tambah Data',
                properties: [
                    'changes' => [
                        'new' => ['nominal' => $mutasi->nominal],
                    ]
                ],
                description: $description,
                userId: $request->created_by,
                message: filled($request->keterangan) ? $request->keterangan : "(Sistem) {$this->title[0]} dibuat."
            );

            KasService::mutasi(
                fromKas: $fromKas,
                toKas: $toKas,
                nominal: $request->nominal,
                sumber: $mutasi,
                tanggal: $mutasi->tanggal
            );

            DB::commit();
            return $this->success(null, 200, 'Data berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, $e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = KasMutasi::findOrFail($request->id);

            KasService::deleteMutasi($data);

            $kas = KasJenisBarangGenerate::labelForKas($data);
            $nominal = RupiahGenerate::build($data->nominal);

            $fromKas = Kas::find($data->kas_asal_id);
            $toKas = Kas::find($data->kas_tujuan_id);

            $tipeKasAsal = KasJenisBarangGenerate::labelForKas($fromKas);
            $tipeKasTujuan = KasJenisBarangGenerate::labelForKas($toKas);

            $description = "{$this->title[0]} senilai {$nominal} (ID {$data->id}) dari {$tipeKasAsal} ke {$tipeKasTujuan}.";

            LogAktivitasGenerate::store(
                logName: $this->title[0] ?? 'Mutasi Kas',
                subjectType: KasMutasi::class,
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
