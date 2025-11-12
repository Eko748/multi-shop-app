<?php

namespace App\Http\Controllers;

use App\Enums\LabelKas;
use App\Models\JenisBarang;
use App\Models\JenisPengeluaran;
use App\Models\Pengeluaran;
use App\Models\Toko;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PengeluaranController extends Controller
{
    use ApiResponse;
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
        $isSingleToko = Toko::take(2)->get()->count() === 1;
        $labelKas = LabelKas::cases();

        return view('pengeluaran.index', compact('menu', 'isSingleToko', 'labelKas'));
    }

    public function getpengeluaran(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Pengeluaran::query()
            ->with(['toko', 'jenis_pengeluaran'])
            ->orderBy('id', $meta['orderBy']);

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));
            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama_pengeluaran) LIKE ?", ["%$searchTerm%"])
                    ->orWhereHas('toko', fn($q) => $q->whereRaw("LOWER(nama_toko) LIKE ?", ["%$searchTerm%"]))
                    ->orWhereHas('jenis_pengeluaran', fn($q) => $q->whereRaw("LOWER(nama_jenis) LIKE ?", ["%$searchTerm%"]));
            });
        }

        if ($request->has('id_toko')) {
            $idToko = $request->input('id_toko');
            if ($idToko != 1) {
                $query->where('id_toko', $idToko);
            }
        }

        if ($request->has('toko')) {
            $query->where('id_toko', $request->input('toko'));
        }

        if ($request->has('jenis')) {
            $query->where('id_jenis_pengeluaran', $request->input('jenis'));
        }

        if ($request->has('is_hutang')) {
            $query->where('is_hutang', $request->input('is_hutang'));
        }

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

        // Cegah query berulang, cache semua jenis barang
        $allJenisBarang = JenisBarang::pluck('nama_jenis_barang', 'id')->toArray();

        $mappedData = collect($data->items())->map(function ($item) use ($allJenisBarang) {
            $idToko = optional($item->toko)->id;
            $labelValue = $item->label;

            // Tentukan label dasar
            if ($idToko != 1) {
                $labelText = 'Kas Kecil OUT';
            } else {
                $labelText = ($labelValue == LabelKas::KAS_KECIL->value)
                    ? 'Kas Kecil OUT'
                    : 'Kas Besar OUT';
            }

            // Tambahkan jenis barang jika ada
            $jenisBarangText = null;
            if (!is_null($item->kas_jenis_barang)) {
                if ($item->kas_jenis_barang == 0) {
                    $jenisBarangText = 'Dompet Digital';
                } elseif (isset($allJenisBarang[$item->kas_jenis_barang])) {
                    $jenisBarangText = $allJenisBarang[$item->kas_jenis_barang];
                }
            }

            // Gabungkan teks label jika perlu
            $labelFullText = $jenisBarangText
                ? "{$labelText} - {$jenisBarangText}"
                : $labelText;

            return [
                'id' => $item->id,
                'id_toko' => $idToko,
                'nama_toko' => optional($item->toko)->nama_toko,
                'nama_pengeluaran' => $item->nama_pengeluaran ?? '-',
                'nama_jenis' => optional($item->jenis_pengeluaran)->nama_jenis ?? '-',
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
        $nilai = (int) $request->input('nilai');

        $label = null;
        $maxNilai = null;

        if ($rawLabel && str_contains($rawLabel, '/')) {
            [$label, $maxNilai] = explode('/', $rawLabel);

            $label = trim($label);
            $maxNilai = (int) trim($maxNilai);

            if ($nilai > $maxNilai) {
                return $this->error(422, 'Nilai tidak boleh lebih dari Rp ' . number_format($maxNilai, 0, ',', '.'));
            }
        }

        $validatedData = $request->validate([
            'id_toko' => 'required|exists:toko,id',
            'nama_pengeluaran' => 'nullable|string',
            'nilai' => 'required|numeric',
            'tanggal' => 'required|date',
            'id_jenis_pengeluaran' => 'nullable|exists:jenis_pengeluaran,id',
        ]);

        try {
            DB::beginTransaction();

            $data = Pengeluaran::create([
                'id_toko' => $validatedData['id_toko'],
                'id_jenis_pengeluaran' => $validatedData['id_jenis_pengeluaran'] ?? null,
                'nama_pengeluaran' => $validatedData['nama_pengeluaran'] ?? null,
                'nilai' => $nilai,
                'tanggal' => $validatedData['tanggal'],
                'is_asset' => $request->input('is_asset') ?? null,
                'label' => $label,
                'kas_jenis_barang' => $request->filled('jenis_id')
                    ? (int) $request->input('jenis_id')
                    : null,
            ]);

            $kasLabel = match ((int) $label) {
                0 => 'Kas Besar',
                1 => 'Kas Kecil',
                default => 'Kas Tidak Dikenal'
            };

            $jenisBarangText = 'Tidak Dikenal';
            if (!is_null($data->kas_jenis_barang)) {
                if ($data->kas_jenis_barang == 0) {
                    $jenisBarangText = 'Dompet Digital';
                } else {
                    $jenis = JenisBarang::find($data->kas_jenis_barang);
                    $jenisBarangText = $jenis?->nama_jenis_barang ?? 'Tidak Dikenal';
                }
            }

            $description = "Pengeluaran Lainnya ditambahkan pada {$kasLabel} ({$jenisBarangText}) senilai Rp " . number_format($data->nilai, 0, ',', '.') . " (ID {$data->id})";

            $this->saveLogAktivitas(
                logName: $this->title[0] ?? 'Pengeluaran',
                subjectType: 'App\Models\Pengeluaran',
                subjectId: $data->id,
                event: 'Tambah Data',
                properties: [
                    'changes' => [
                        'new' => [
                            'nilai' => $data->nilai,
                            'tanggal' => $data->tanggal,
                            'kas' => $kasLabel . ' ' . $jenisBarangText,
                        ],
                    ]
                ],
                description: $description,
                userId: Auth::user()->id,
                message: filled($request->nama_pengeluaran)
                    ? $request->nama_pengeluaran
                    : '(Sistem) Pengeluaran Lainnya dibuat.'
            );

            DB::commit();
            return $this->success(null, 200, 'Data berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Terjadi kesalahan saat menyimpan data.', [
                'error_message' => $e->getMessage(),
                'error_file'    => $e->getFile(),
                'error_line'    => $e->getLine(),
                'trace'         => $e->getTraceAsString(), // opsional, kalau mau detail full
            ]);
        }
    }

    public function delete(string $id)
    {
        DB::beginTransaction();
        try {
            $data = Pengeluaran::findOrFail($id);

            $kasLabel = match ((int) $data->label) {
                0 => 'Kas Besar',
                1 => 'Kas Kecil',
                default => 'Kas Tidak Dikenal'
            };

            $jenisBarangText = 'Tidak Dikenal';
            if (!is_null($data->kas_jenis_barang)) {
                if ($data->kas_jenis_barang == 0) {
                    $jenisBarangText = 'Dompet Digital';
                } else {
                    $jenis = JenisBarang::find($data->kas_jenis_barang);
                    $jenisBarangText = $jenis?->nama_jenis_barang ?? 'Tidak Dikenal';
                }
            }

            $description = "Pengeluaran Lainnya dihapus dari {$kasLabel} ({$jenisBarangText}) senilai Rp " .
                number_format($data->nilai, 0, ',', '.') . " (ID {$data->id})";

            $this->saveLogAktivitas(
                logName: $this->title[0] ?? 'Pengeluaran',
                subjectType: 'App\Models\Pengeluaran',
                subjectId: $data->id,
                event: 'Hapus Data',
                properties: [
                    'changes' => [
                        'old' => [
                            'nilai' => $data->nilai,
                            'tanggal' => $data->tanggal,
                            'kas' => $kasLabel . ' ' . $jenisBarangText,
                        ],
                    ],
                ],
                description: $description,
                userId: Auth::user()->id,
                message: '(Sistem) Pengeluaran Lainnya dihapus.'
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
