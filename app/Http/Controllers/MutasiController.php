<?php

namespace App\Http\Controllers;

use App\Enums\LabelKas;
use App\Models\JenisBarang;
use App\Models\Mutasi;
use App\Models\Pemasukan;
use App\Models\Toko;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $isSingleToko = Toko::take(2)->get()->count() === 1;
        $labelKas = LabelKas::cases();

        return view('mutasi.index', compact('menu', 'isSingleToko', 'labelKas'));
    }

    public function getmutasi(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Mutasi::query()->with(['tokoPengirim', 'tokoPenerima'])->orderBy('id', $meta['orderBy']);

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));
            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(keterangan) LIKE ?", ["%$searchTerm%"]);
            });
        }

        if ($request->has('id_toko')) {
            $idToko = $request->input('id_toko');
            if ($idToko != 1) {
                $query->where(function ($q) use ($idToko) {
                    $q->where('id_toko_pengirim', $idToko)
                        ->orWhere('id_toko_penerima', $idToko);
                });
            }
        }

        if ($request->has('toko')) {
            $idToko = $request->input('toko');
            $query->where(function ($q) use ($idToko) {
                $q->where('id_toko_pengirim', $idToko)
                    ->orWhere('id_toko_penerima', $idToko);
            });
        }

        if ($request->has('kas_pengirim')) {
            $kas = $request->input('kas_pengirim');
            $query->where(function ($q) use ($kas) {
                $q->where('id_toko_pengirim', $kas);
            });
        }

        if ($request->has('kas_penerima')) {
            $kas = $request->input('kas_penerima');
            $query->where(function ($q) use ($kas) {
                $q->where('id_toko_penerima', $kas);
            });
        }

        if ($request->has('startDate') && $request->has('endDate')) {
            $query->whereBetween('created_at', [$request->input('startDate'), $request->input('endDate')]);
        }

        $isSingleToko = Toko::take(2)->get()->count() === 1;
        $totalNilai = $query->sum('nilai');
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

        // Ambil semua JenisBarang untuk mapping cepat
        $jenisBarangList = JenisBarang::all(['id', 'nama_jenis_barang'])->pluck('nama_jenis_barang', 'id')->toArray();

        $mappedData = collect($data->items())->map(function ($item) use ($isSingleToko, $jenisBarangList) {
            if ($isSingleToko) {
                $nama_pengirim = $item->id_toko_pengirim == 0 ? LabelKas::KAS_BESAR->label() : LabelKas::KAS_KECIL->label();
                $nama_penerima = $item->id_toko_penerima == 0 ? LabelKas::KAS_BESAR->label() : LabelKas::KAS_KECIL->label();
            } else {
                $nama_pengirim = $item->tokoPengirim ? $item->tokoPengirim->singkatan : null;
                $nama_penerima = $item->tokoPenerima ? $item->tokoPenerima->singkatan : null;
            }

            // Jenis barang pengirim
            $namaJenisPengirim = null;
            if (!is_null($item->kas_jenis_barang_pengirim)) {
                if ($item->kas_jenis_barang_pengirim == 0) {
                    $namaJenisPengirim = 'Dompet Digital';
                } elseif (isset($jenisBarangList[$item->kas_jenis_barang_pengirim])) {
                    $namaJenisPengirim = $jenisBarangList[$item->kas_jenis_barang_pengirim];
                }
            }

            // Jenis barang penerima
            $namaJenisPenerima = null;
            if (!is_null($item->kas_jenis_barang_penerima)) {
                if ($item->kas_jenis_barang_penerima == 0) {
                    $namaJenisPenerima = 'Dompet Digital';
                } elseif (isset($jenisBarangList[$item->kas_jenis_barang_penerima])) {
                    $namaJenisPenerima = $jenisBarangList[$item->kas_jenis_barang_penerima];
                }
            }

            // Gabungkan nama toko dengan jenis barang jika ada
            if ($namaJenisPengirim) {
                $nama_pengirim .= ' - ' . $namaJenisPengirim;
            }
            if ($namaJenisPenerima) {
                $nama_penerima .= ' - ' . $namaJenisPenerima;
            }

            return [
                'id' => $item->id,
                'id_toko_pengirim' => $item->id_toko_pengirim,
                'nama_toko_pengirim' => $nama_pengirim,
                'id_toko_penerima' => $item->id_toko_penerima,
                'nama_toko_penerima' => $nama_penerima,
                'nilai' => 'Rp. ' . number_format($item->nilai ?? 0, 0, '.', '.'),
                'keterangan' => $item->keterangan,
                'tanggal' => Carbon::parse($item->created_at)->format('d-m-Y H:i:s'),
            ];
        });

        return $this->success([
            'data' => $mappedData,
            'total_nilai' => 'Rp. ' . number_format($totalNilai, 0, '.', '.'),
        ], 200, 'Sukses', $paginationMeta);
    }

    public function store(Request $request)
    {
        $request->validate([
            'toko_penerima' => 'required',
            'toko_pengirim' => 'required',
            'nilai' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
            'jenis_id_pengirim' => 'nullable|integer',
            'jenis_id_penerima' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            // --- pecah value pengirim (format: "jenisId-idKas/maxKas")
            [$partPengirim, $maxPengirim] = explode('/', $request->toko_pengirim);
            [$jenisPengirim, $idPengirim] = explode('-', $partPengirim);

            // --- pecah value penerima
            [$partPenerima, $maxPenerima] = explode('/', $request->toko_penerima);
            [$jenisPenerima, $idPenerima] = explode('-', $partPenerima);

            $maxPengirim = (float) $maxPengirim;

            if ($request->nilai > $maxPengirim) {
                return $this->error(422, 'Nilai tidak boleh lebih dari Rp ' . number_format($maxPengirim, 0, ',', '.'));
            }

            $mutasi = new Mutasi();
            $mutasi->id_toko_pengirim = (int) $idPengirim;
            $mutasi->id_toko_penerima = (int) $idPenerima;
            $mutasi->kas_jenis_barang_pengirim =
                (is_numeric($jenisPengirim) && (int)$jenisPengirim !== 0) ? (int)$jenisPengirim : null;

            $mutasi->kas_jenis_barang_penerima =
                (is_numeric($jenisPenerima) && (int)$jenisPenerima !== 0) ? (int)$jenisPenerima : null;

            $mutasi->nilai = $request->nilai;
            $mutasi->keterangan = $request->keterangan;
            $mutasi->save();

            // Format nilai dalam rupiah
            $format = 'Rp ' . number_format($mutasi->nilai, 0, ',', '.');

            // --- Tentukan nama kas (Besar/Kecil)
            $kasPengirim = $mutasi->id_toko_pengirim == 0 ? 'Kas Besar' : 'Kas Kecil';
            $kasPenerima = $mutasi->id_toko_penerima == 0 ? 'Kas Besar' : 'Kas Kecil';

            // --- Ambil nama jenis barang (atau Dompet Digital jika 0)
            if ($mutasi->kas_jenis_barang_pengirim == 0) {
                $jenisPengirimNama = 'Dompet Digital';
            } else {
                $jenisPengirimNama = \App\Models\JenisBarang::find($mutasi->kas_jenis_barang_pengirim)?->nama_jenis_barang ?? 'Tidak Diketahui';
            }

            if ($mutasi->kas_jenis_barang_penerima == 0) {
                $jenisPenerimaNama = 'Dompet Digital';
            } else {
                $jenisPenerimaNama = \App\Models\JenisBarang::find($mutasi->kas_jenis_barang_penerima)?->nama_jenis_barang ?? 'Tidak Diketahui';
            }

            // --- Buat deskripsi dengan format baru
            $description = "Mutasi Kas senilai {$format} (ID {$mutasi->id}) "
                . "dari {$kasPengirim} ({$jenisPengirimNama}) "
                . "ke {$kasPenerima} ({$jenisPenerimaNama}).";

            // --- Simpan log aktivitas
            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: 'App\Models\Mutasi',
                subjectId: $mutasi->id,
                event: 'Tambah Data',
                properties: [
                    'changes' => [
                        'new' => ['nilai' => $mutasi->nilai],
                    ]
                ],
                description: $description,
                userId: Auth::user()->id,
                message: filled($request->keterangan) ? $request->keterangan : '(Sistem) Mutasi Kas dibuat.'
            );

            DB::commit();
            return $this->success(null, 200, 'Data berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, $e->getMessage());
        }
    }

    public function delete(string $id)
    {
        DB::beginTransaction();
        try {
            $mutasi = Mutasi::findOrFail($id);

            // Simpan data sebelum dihapus untuk log
            $format = 'Rp ' . number_format($mutasi->nilai, 0, ',', '.');

            // Tentukan jenis kas pengirim dan penerima
            $kasPengirim = $mutasi->id_toko_pengirim == 0 ? 'Kas Besar' : 'Kas Kecil';
            $kasPenerima = $mutasi->id_toko_penerima == 0 ? 'Kas Besar' : 'Kas Kecil';

            // Ambil nama jenis barang pengirim dan penerima
            if ($mutasi->kas_jenis_barang_pengirim == 0) {
                $jenisPengirimNama = 'Dompet Digital';
            } else {
                $jenisPengirimNama = \App\Models\JenisBarang::find($mutasi->kas_jenis_barang_pengirim)?->nama_jenis_barang ?? 'Tidak Diketahui';
            }

            if ($mutasi->kas_jenis_barang_penerima == 0) {
                $jenisPenerimaNama = 'Dompet Digital';
            } else {
                $jenisPenerimaNama = \App\Models\JenisBarang::find($mutasi->kas_jenis_barang_penerima)?->nama_jenis_barang ?? 'Tidak Diketahui';
            }

            // Buat deskripsi log
            $description = "Menghapus Mutasi Kas senilai {$format} (ID {$mutasi->id}) "
                . "dari {$kasPengirim} ({$jenisPengirimNama}) "
                . "ke {$kasPenerima} ({$jenisPenerimaNama}).";

            // Hapus data
            $mutasi->delete();

            // Simpan log aktivitas
            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: 'App\Models\Mutasi',
                subjectId: $mutasi->id,
                event: 'Hapus Data',
                properties: [
                    'changes' => [
                        'old' => [
                            'jenis_kas_pengirim' => $kasPengirim . ' ' . $jenisPengirimNama,
                            'jenis_kas_penerima' => $kasPenerima . ' ' . $jenisPenerimaNama,
                            'nilai' => $mutasi->nilai,
                        ],
                    ],
                ],
                description: $description,
                userId: Auth::user()->id,
                message: '(Sistem) Mutasi Kas dihapus.'
            );

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Sukses menghapus Data mutasi'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Data mutasi: ' . $th->getMessage()
            ], 500);
        }
    }
}
