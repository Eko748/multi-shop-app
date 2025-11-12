<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DetailPemasukan;
use App\Models\JenisPemasukan;
use App\Models\Pemasukan;
use App\Models\Toko;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\LabelKas;
use App\Models\JenisBarang;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
        $isSingleToko = Toko::take(2)->get()->count() === 1;
        $labelKas = LabelKas::cases();

        return view('pemasukan.index', compact('menu', 'isSingleToko', 'labelKas'));
    }

    public function getpemasukan(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Pemasukan::query()
            ->with(['toko', 'jenis_pemasukan'])
            ->orderBy('id', $meta['orderBy']);

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));
            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama_pemasukan) LIKE ?", ["%$searchTerm%"])
                    ->orWhereHas('toko', fn($q) => $q->whereRaw("LOWER(nama_toko) LIKE ?", ["%$searchTerm%"]))
                    ->orWhereHas('jenis_pemasukan', fn($q) => $q->whereRaw("LOWER(nama_jenis) LIKE ?", ["%$searchTerm%"]));
            });
        }

        if ($request->has('id_toko') && $request->input('id_toko') != 1) {
            $query->where('id_toko', $request->input('id_toko'));
        }

        if ($request->has('toko')) $query->where('id_toko', $request->input('toko'));
        if ($request->has('jenis')) $query->where('id_jenis_pemasukan', $request->input('jenis'));
        if ($request->has('startDate') && $request->has('endDate')) {
            $query->whereBetween('tanggal', [$request->input('startDate'), $request->input('endDate')]);
        }

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

        $mappedData = collect($data->items())->map(function ($item) {
            $idToko = optional($item->toko)->id;
            $labelValue = $item->label;

            // Tentukan label utama
            if ($idToko != 1) {
                $labelText = 'Kas Kecil IN';
            } else {
                $labelText = ($labelValue == LabelKas::KAS_KECIL->value)
                    ? 'Kas Kecil IN'
                    : 'Kas Besar IN';
            }

            // Tambahkan nama_jenis_barang sesuai kas_jenis_barang
            $jenisBarangText = null;
            if (!is_null($item->kas_jenis_barang)) {
                if ($item->kas_jenis_barang == 0) {
                    $jenisBarangText = 'Dompet Digital';
                } else {
                    $jenisBarang = JenisBarang::find($item->kas_jenis_barang);
                    if ($jenisBarang) {
                        $jenisBarangText = $jenisBarang->nama_jenis_barang;
                    }
                }
            }

            // Gabungkan jika ada nama jenis barang
            $labelFullText = $jenisBarangText
                ? "{$labelText} - {$jenisBarangText}"
                : $labelText;

            return [
                'id' => $item->id,
                'id_toko' => $idToko,
                'nama_toko' => optional($item->toko)->nama_toko,
                'nama_pemasukan' => $item->nama_pemasukan ?? '-',
                'nama_jenis' => optional($item->jenis_pemasukan)->nama_jenis ?? '-',
                'nilai' => 'Rp. ' . number_format($item->nilai ?? 0, 0, '.', '.'),
                'tanggal' => $item->tanggal ? Carbon::parse($item->tanggal)->format('d-m-Y') : '-',
                'label' => $labelFullText,
            ];
        });

        return $this->success(
            [
                'data' => $mappedData,
                'total_nilai' => 'Rp. ' . number_format($totalNilai, 0, '.', '.')
            ],
            200,
            'Sukses',
            $paginationMeta
        );
    }


    public function store(Request $request)
    {
        $rawLabel = $request->input('label');
        $label = null;
        $maxNilai = null;

        if ($rawLabel && str_contains($rawLabel, '/')) {
            [$label, $maxNilai] = explode('/', $rawLabel);
            $label = trim($label);
            $maxNilai = (int) trim($maxNilai);
        }

        $validation = [
            'id_toko' => 'required|exists:toko,id',
            'nama_pemasukan' => 'nullable|string',
            'nilai' => 'required|numeric',
            'tanggal' => 'required|date',
            'id_jenis_pemasukan' => 'nullable|exists:jenis_pemasukan,id',
            'nama_jenis' => 'nullable|string',
            'jenis_id' => 'nullable|integer',
            'keterangan' => 'nullable|string',
        ];

        $validatedData = $request->validate($validation);

        if (empty($validatedData['id_jenis_pemasukan']) && empty($validatedData['nama_jenis'])) {
            return $this->error(422, 'Harap isi salah satu: id_jenis_pemasukan atau nama_jenis');
        }

        try {
            DB::beginTransaction();

            if (!empty($validatedData['nama_jenis']) && empty($validatedData['id_jenis_pemasukan'])) {
                $jenisBaru = JenisPemasukan::create([
                    'nama_jenis' => $validatedData['nama_jenis']
                ]);
                $validatedData['id_jenis_pemasukan'] = $jenisBaru->id;
            }

            $pemasukan = Pemasukan::create([
                'id_toko' => $validatedData['id_toko'],
                'id_jenis_pemasukan' => $validatedData['id_jenis_pemasukan'],
                'nama_pemasukan' => $validatedData['nama_pemasukan'],
                'nilai' => $validatedData['nilai'],
                'tanggal' => $validatedData['tanggal'],
                'label' => $label,
                'kas_jenis_barang' => $request->has('jenis_id')
                    ? (int) $request->input('jenis_id')
                    : null,
            ]);

            $kasLabel = match ((int) $label) {
                0 => 'Kas Besar',
                1 => 'Kas Kecil',
                default => 'Kas Tidak Dikenal'
            };

            $jenisBarangText = 'Tidak Dikenal';
            if (!is_null($pemasukan->kas_jenis_barang)) {
                if ($pemasukan->kas_jenis_barang == 0) {
                    $jenisBarangText = 'Dompet Digital';
                } else {
                    $jenis = JenisBarang::find($pemasukan->kas_jenis_barang);
                    $jenisBarangText = $jenis?->nama_jenis_barang ?? 'Tidak Dikenal';
                }
            }

            $description = "Pemasukan Lainnya ditambahkan pada {$kasLabel} ({$jenisBarangText}) senilai Rp " . number_format($pemasukan->nilai, 0, ',', '.') . " (ID {$pemasukan->id})";

            $this->saveLogAktivitas(
                logName: $this->title[0] ?? 'Pemasukan',
                subjectType: 'App\Models\Pemasukan',
                subjectId: $pemasukan->id,
                event: 'Tambah Data',
                properties: [
                    'changes' => [
                        'new' => [
                            'nilai' => $pemasukan->nilai,
                            'tanggal' => $pemasukan->tanggal,
                            'kas' => $kasLabel . ' ' . $jenisBarangText,
                        ],
                    ]
                ],
                description: $description,
                userId: Auth::user()->id,
                message: filled($request->nama_pemasukan)
                    ? $request->nama_pemasukan
                    : '(Sistem) Pemasukan Lainnya dibuat.'
            );

            DB::commit();
            return $this->success(null, 200, 'Data berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    public function delete(string $id)
    {
        DB::beginTransaction();
        try {
            $pemasukan = Pemasukan::findOrFail($id);

            $kasLabel = match ((int) $pemasukan->label) {
                0 => 'Kas Besar',
                1 => 'Kas Kecil',
                default => 'Kas Tidak Dikenal'
            };

            $jenisBarangText = 'Tidak Dikenal';
            if (!is_null($pemasukan->kas_jenis_barang)) {
                if ($pemasukan->kas_jenis_barang == 0) {
                    $jenisBarangText = 'Dompet Digital';
                } else {
                    $jenis = JenisBarang::find($pemasukan->kas_jenis_barang);
                    $jenisBarangText = $jenis?->nama_jenis_barang ?? 'Tidak Dikenal';
                }
            }

            $description = "Pemasukan Lainnya dihapus dari {$kasLabel} ({$jenisBarangText}) senilai Rp " .
                number_format($pemasukan->nilai, 0, ',', '.') . " (ID {$pemasukan->id})";

            $this->saveLogAktivitas(
                logName: $this->title[0] ?? 'Pemasukan',
                subjectType: 'App\Models\Pemasukan',
                subjectId: $pemasukan->id,
                event: 'Hapus Data',
                properties: [
                    'changes' => [
                        'old' => [
                            'nilai' => $pemasukan->nilai,
                            'tanggal' => $pemasukan->tanggal,
                            'kas' => $kasLabel . ' ' . $jenisBarangText,
                        ],
                    ],
                ],
                description: $description,
                userId: Auth::user()->id,
                message: '(Sistem) Pemasukan Lainnya dihapus.'
            );

            $pemasukan->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Sukses menghapus Data pemasukan'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Data pemasukan: ' . $th->getMessage()
            ], 500);
        }
    }

    public function updatepinjam(Request $request, string $id)
    {
        $validation = [
            'nilai' => 'required|numeric',
        ];

        $validatedData = $request->validate($validation);

        try {
            DB::beginTransaction();

            $pemasukan = Pemasukan::findOrFail($id);
            if ($pemasukan->is_pinjam != '1') {
                throw new \Exception('Data bukan merupakan pinjaman!');
            }

            if ($validatedData['nilai'] > $pemasukan->nilai) {
                throw new \Exception('Nilai bayar melebihi nilai pinjaman!');
            }

            DetailPemasukan::create([
                'id_pemasukan' => $pemasukan->id,
                'nilai' => $validatedData['nilai'],
            ]);

            $totalBayar = DetailPemasukan::where('id_pemasukan', $pemasukan->id)->sum('nilai');

            if ($totalBayar >= $pemasukan->nilai) {
                $pemasukan->update(['is_pinjam' => '2']);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Berhasil melakukan pembayaran pinjaman'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }

    public function detail(string $id)
    {
        try {
            $pemasukan = Pemasukan::with(['toko', 'jenis_pemasukan'])->findOrFail($id);

            $detailPembayaran = DetailPemasukan::where('id_pemasukan', $id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'nilai' => 'Rp. ' . number_format($item->nilai, 0, '.', '.'),
                        'tanggal' => Carbon::parse($item->created_at)->format('d-m-Y H:i:s'),
                    ];
                });

            $totalPembayaran = DetailPemasukan::where('id_pemasukan', $id)->sum('nilai');
            $sisaPinjaman = $pemasukan->nilai - $totalPembayaran;

            $labelValue = $pemasukan->label;
            $labelText = $labelValue ? LabelKas::from($labelValue)->label() : '-';

            $data = [
                'pemasukan' => [
                    'id' => $pemasukan->id,
                    'nama_toko' => optional($pemasukan->toko)->nama_toko,
                    'nama_pemasukan' => $pemasukan->nama_pemasukan ?? '-',
                    'nama_jenis' => optional($pemasukan->jenis_pemasukan)->nama_jenis ?? '-',
                    'nilai' => 'Rp. ' . number_format($pemasukan->nilai, 0, '.', '.'),
                    'is_pinjam' => $pemasukan->is_pinjam,
                    'ket_pinjam' => $pemasukan->ket_pinjam,
                    'tanggal' => Carbon::parse($pemasukan->tanggal)->format('d-m-Y'),
                    'label' => $labelText,
                ],
                'detail_pembayaran' => $detailPembayaran,
                'total_pembayaran' => 'Rp. ' . number_format($totalPembayaran, 0, '.', '.'),
                'sisa_pinjaman' => 'Rp. ' . number_format($sisaPinjaman, 0, '.', '.'),
            ];

            return $this->success($data, 200, 'Berhasil mengambil detail pemasukan');
        } catch (\Exception $e) {
            return $this->error(500, 'Gagal mengambil detail pemasukan: ' . $e->getMessage());
        }
    }
}
