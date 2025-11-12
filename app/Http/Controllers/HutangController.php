<?php

namespace App\Http\Controllers;

use App\Enums\LabelKas;
use App\Http\Controllers\Controller;
use App\Models\DetailHutang;
use App\Models\Hutang;
use App\Models\JenisBarang;
use App\Models\JenisHutang;
use App\Models\PembelianBarang;
use App\Models\Toko;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        $isSingleToko = Toko::take(2)->get()->count() === 1;
        $labelKas = LabelKas::cases();

        return view('hutang.index', compact('menu', 'isSingleToko', 'labelKas'));
    }

    public function getHutang(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Hutang::query()
            ->with(['toko', 'jenis_hutang'])
            ->orderBy('id', $meta['orderBy']);

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));
            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(keterangan) LIKE ?", ["%$searchTerm%"])
                    ->orWhereHas('toko', fn($q) => $q->whereRaw("LOWER(nama_toko) LIKE ?", ["%$searchTerm%"]))
                    ->orWhereHas('jenis_hutang', fn($q) => $q->whereRaw("LOWER(nama_jenis) LIKE ?", ["%$searchTerm%"]));
            });
        }

        if ($request->has('toko')) {
            $idToko = $request->input('toko');
            if ($idToko != 1) {
                $query->where('toko', $idToko);
            }
        }

        if ($request->has('jenis')) {
            $query->where('id_jenis', $request->input('jenis'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('startDate') && $request->has('endDate')) {
            $query->whereBetween('tanggal', [$request->input('startDate'), $request->input('endDate')]);
        }

        $totalNilai = $query->sum('nilai');
        $hutangList = $query->get();

        // Hitung total sisa hutang
        $totalSisa = $hutangList->sum(function ($item) {
            return $item->nilai - DetailHutang::where('id_hutang', $item->id)->sum('nilai');
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

        // Ambil semua jenis barang untuk caching
        $allJenisBarang = JenisBarang::pluck('nama_jenis_barang', 'id')->toArray();

        $mappedData = collect($data->items())->map(function ($item) use ($allJenisBarang) {
            $idToko = optional($item->toko)->id;
            $labelValue = $item->label;
            $labelText = !is_null($labelValue) ? LabelKas::from((int)$labelValue)->label() : '-';

            // Tambahkan nama jenis barang sesuai kas_jenis_barang
            $jenisBarangText = null;
            if (!is_null($item->kas_jenis_barang)) {
                if ($item->kas_jenis_barang == 0) {
                    $jenisBarangText = 'Dompet Digital';
                } elseif (isset($allJenisBarang[$item->kas_jenis_barang])) {
                    $jenisBarangText = $allJenisBarang[$item->kas_jenis_barang];
                }
            }

            // Gabungkan label dan jenis barang
            $labelFullText = $jenisBarangText
                ? "{$labelText} - {$jenisBarangText}"
                : $labelText;

            // Tentukan kategori jangka
            $jangka = match ($item->jangka) {
                '1' => 'Jangka Pendek',
                '2' => 'Jangka Panjang',
                default => 'Tidak ada',
            };

            return [
                'id' => $item->id,
                'id_toko' => $idToko,
                'nama_toko' => optional($item->toko)->nama_toko,
                'nama_jenis' => optional($item->jenis_hutang)->nama_jenis ?? '-',
                'keterangan' => $item->keterangan,
                'status' => $item->status,
                'jangka' => $jangka,
                'tanggal' => $item->tanggal ? Carbon::parse($item->tanggal)->format('d-m-Y') : '-',
                'nilai' => 'Rp. ' . number_format($item->nilai ?? 0, 0, '.', '.'),
                'sisa_hutang' => 'Rp. ' . number_format($item->nilai - DetailHutang::where('id_hutang', $item->id)->sum('nilai'), 0, '.', '.'),
                'label' => $labelFullText,
            ];
        });

        return $this->success(
            [
                'data' => $mappedData,
                'total_nilai' => 'Rp. ' . number_format($totalNilai, 0, '.', '.'),
                'total_sisa' => 'Rp. ' . number_format($totalSisa, 0, '.', '.')
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
            'id_jenis' => 'nullable|exists:jenis_hutang,id',
            'keterangan' => 'required|string',
            'nilai' => 'required|numeric',
            'jangka' => 'nullable|in:1,2',
            'tanggal' => 'required|date',
            'nama_jenis' => 'required_without:id_jenis|string',
            'jenis_id' => 'nullable|integer',
        ];

        $validatedData = $request->validate($validation);

        try {
            DB::beginTransaction();

            $id_jenis = null;
            $id_jenis = $validatedData['id_jenis'] ?? null;
            if (empty($id_jenis) && isset($validatedData['nama_jenis'])) {
                $jenis_hutang = JenisHutang::create([
                    'nama_jenis' => $validatedData['nama_jenis']
                ]);
                $id_jenis = $jenis_hutang->id;
            }

            $data = Hutang::create([
                'id_toko' => $validatedData['id_toko'],
                'id_jenis' => $id_jenis,
                'keterangan' => $validatedData['keterangan'],
                'nilai' => $validatedData['nilai'],
                'status' => '1',
                'jangka' => $validatedData['jangka'] ?? null,
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
            if (!is_null($data->kas_jenis_barang)) {
                if ($data->kas_jenis_barang == 0) {
                    $jenisBarangText = 'Dompet Digital';
                } else {
                    $jenis = JenisBarang::find($data->kas_jenis_barang);
                    $jenisBarangText = $jenis?->nama_jenis_barang ?? 'Tidak Dikenal';
                }
            }

            $description = "Hutang ditambahkan pada {$kasLabel} ({$jenisBarangText}) senilai Rp " . number_format($data->nilai, 0, ',', '.') . " (ID {$data->id})";

            $this->saveLogAktivitas(
                logName: $this->title[0] ?? 'Hutang',
                subjectType: 'App\Models\Hutang',
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
                message: filled($request->keterangan)
                    ? $request->keterangan
                    : '(Sistem) Hutang dibuat.'
            );

            DB::commit();
            return $this->success(null, 200, 'Data berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function detail(string $id)
    {
        try {
            $hutang = Hutang::with(['toko', 'jenis_hutang'])->findOrFail($id);
            $detailPembayaran = DetailHutang::where('id_hutang', $id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'nilai' => 'Rp. ' . number_format($item->nilai, 0, '.', '.'),
                        'tanggal' => Carbon::parse($item->created_at)->format('d-m-Y H:i:s')
                    ];
                });

            $totalPembayaran = DetailHutang::where('id_hutang', $id)->sum('nilai');
            $sisaHutang = $hutang->nilai - $totalPembayaran;

            $labelValue = $hutang->label;
            $labelText = $labelValue ? LabelKas::from($labelValue)->label() : '-';

            return response()->json([
                'success' => true,
                'data' => [
                    'hutang' => [
                        'id' => $hutang->id,
                        'nama_toko' => $hutang->toko->nama_toko,
                        'keterangan' => $hutang->keterangan ?? '-',
                        'nama_jenis' => $hutang->jenis_hutang->nama_jenis ?? '-',
                        'nilai' => 'Rp. ' . number_format($hutang->nilai, 0, '.', '.'),
                        'status' => $hutang->status,
                        'jangka' => $hutang->jangka,
                        'tanggal' => Carbon::parse($hutang->tanggal)->format('d-m-Y'),
                    ],
                    'detail_pembayaran' => $detailPembayaran,
                    'total_pembayaran' => 'Rp. ' . number_format($totalPembayaran, 0, '.', '.'),
                    'sisa_hutang' => 'Rp. ' . number_format($sisaHutang, 0, '.', '.'),
                    'label' => $labelText,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail hutang: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updatehutang(Request $request, string $id)
    {
        $validation = [
            'nilai' => 'required|numeric',
        ];

        $validatedData = $request->validate($validation);

        try {
            DB::beginTransaction();

            $hutang = Hutang::findOrFail($id);

            if ($validatedData['nilai'] > $hutang->nilai) {
                throw new \Exception('Nilai bayar melebihi nilai hutang!');
            }

            DetailHutang::create([
                'id_hutang' => $hutang->id,
                'nilai' => $validatedData['nilai'],
            ]);

            $totalBayar = DetailHutang::where('id_hutang', $hutang->id)->sum('nilai');

            if ($totalBayar >= $hutang->nilai) {
                $hutang->update(['status' => '2']);

                if (!is_null($hutang->pb_id)) {
                    $pembelian = PembelianBarang::find($hutang->pb_id);

                    if ($pembelian) {
                        $pembelian->status = 'success_debt';
                        $pembelian->save();
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil melakukan pembayaran hutang',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function delete(string $id)
    {
        DB::beginTransaction();
        try {
            $data = Hutang::findOrFail($id);
            $data->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Sukses menghapus Data'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Data: ' . $th->getMessage()
            ], 500);
        }
    }
}
