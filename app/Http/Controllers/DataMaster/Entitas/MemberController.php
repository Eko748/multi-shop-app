<?php

namespace App\Http\Controllers\DataMaster\Entitas;

use App\Http\Controllers\Controller;
use App\Helpers\ActivityLogger;
use App\Imports\MemberImport;
use App\Models\JenisBarang;
use App\Models\LevelHarga;
use App\Models\Member;
use App\Models\Toko;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Arr;
use Throwable;

class MemberController extends Controller
{
    use ApiResponse;
    private array $menu = [];

    public function __construct()
    {
        $this->menu;
        $this->title = [
            'Data Member',
        ];
    }

    public function getmember(Request $request)
    {
        $meta['orderBy'] = $request->ascending ? 'asc' : 'desc';
        $meta['limit'] = $request->has('limit') && $request->limit <= 30 ? $request->limit : 30;

        $query = Member::query();

        $query->with(['toko', 'levelHarga'])->orderBy('id', $meta['orderBy']);

        if ($request->has('toko_id')) {
            $idToko = $request->input('toko_id');
            if ($idToko != 1) {
                $query->where('toko_id', $idToko);
            }
        }

        if (!empty($request['search'])) {
            $searchTerm = trim(strtolower($request['search']));

            $query->where(function ($query) use ($searchTerm) {
                $query->orWhereRaw("LOWER(nama) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereRaw("LOWER(no_hp) LIKE ?", ["%$searchTerm%"]);
                $query->orWhereRaw("LOWER(alamat) LIKE ?", ["%$searchTerm%"]);

                $query->orWhereHas('toko', function ($subquery) use ($searchTerm) {
                    $subquery->whereRaw("LOWER(toko) LIKE ?", ["%$searchTerm%"]);
                });
            });
        }

        $data = $query->paginate($meta['limit']);

        $paginationMeta = [
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'total_pages' => $data->lastPage()
        ];

        $data = [
            'data' => $data->items(),
            'meta' => $paginationMeta
        ];

        if (empty($data['data'])) {
            return response()->json([
                'status_code' => 400,
                'errors' => true,
                'message' => 'Tidak ada data'
            ], 400);
        }

        $mappedData = collect($data['data'])->map(function ($item) {

            $selectedLevels = [];

            if (!empty($item->level_info)) {
                foreach (json_decode($item->level_info, true) as $info) {
                    preg_match('/(\d+) : (\d+)/', $info, $matches);

                    if (!empty($matches)) {
                        $jenisBarang = JenisBarang::find($matches[1]);
                        $levelHarga  = LevelHarga::find($matches[2]);

                        if ($jenisBarang && $levelHarga) {
                            $selectedLevels[] = [
                                'id_jenis_barang'   => $jenisBarang->id,
                                'nama_jenis_barang' => $jenisBarang->nama_jenis_barang,
                                'id_level_harga'    => $levelHarga->id,
                                'nama_level_harga'  => $levelHarga->nama_level_harga,
                            ];
                        }
                    }
                }
            }

            return [
                'id'          => $item->id,
                'nama' => $item->nama,
                'toko_id'     => $item->toko->id ?? null,
                'nama_toko'   => $item->toko->nama ?? null,
                'level'       => $selectedLevels,
                'no_hp'       => $item->no_hp,
                'alamat'      => $item->alamat,
            ];
        });


        return response()->json([
            'data' => $mappedData,
            'status_code' => 200,
            'errors' => false,
            'message' => 'Sukses',
            'pagination' => $data['meta']
        ], 200);
    }

    public function index()
    {
        $menu = [$this->title[0], $this->label[0]];
        $jenis_barang = JenisBarang::all();

        return view('master.member.index', compact('menu', 'jenis_barang'));
    }

    public function getLevelHarga(Request $request)
    {
        $toko = Toko::findOrFail($request->toko_id);

        if ($toko) {
            $levelHargaIds = json_decode($toko->level_harga, true);

            $levelHarga = LevelHarga::whereIn('id', $levelHargaIds)
                ->get(['id', 'nama_level_harga'])
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'text' => $item->nama_level_harga,
                    ];
                })
                ->values();

            $data['data'] = $levelHarga;

            return $this->success($data['data'], 200, 'Berhasil');
        }

        return response()->json(['error' => 'Toko tidak ditemukan'], 404);
    }

    public function post(Request $request)
    {
        try {
            $validatedData = $request->validate(
                [
                    'toko_id' => 'required',
                    'nama' => 'required',
                    'no_hp' => 'nullable',
                    'alamat' => 'required',
                ],
                [
                    'toko_id.required' => 'Toko Wajib diisi.',
                    'nama.required' => 'Nama Member tidak boleh kosong',
                    'alamat.required' => 'Alamat Wajib diisi',
                ]
            );

            $level_harga = $request->input('level_harga', []);
            $levelInfo = [];

            foreach ($level_harga as $jenis_barang_id => $level_harga_id) {
                if (!empty($level_harga_id)) {
                    $levelInfo[] = "{$jenis_barang_id} : {$level_harga_id}";
                }
            }

            DB::beginTransaction();

            $data = Member::create([
                'toko_id' => $validatedData['toko_id'],
                'nama' => $validatedData['nama'],
                'no_hp' => $validatedData['no_hp'],
                'alamat' => $validatedData['alamat'],
                'level_info' => json_encode($levelInfo),
            ]);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: 'App\Models\Member',
                subjectId: $data->id,
                event: 'Tambah Data',
                properties: [
                    'changes' => [
                        'new' => Arr::except($data->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']),
                    ],
                ],
                description: "Member {$data->nama} (ID {$data->id}) di toko ID {$data->toko_id} ditambahkan.",
                userId: $request->user_id ?? null,
                message: $request->message ?? '(Sistem) Penambahan member baru.'
            );

            DB::commit();
            return $this->success($data, 201, 'Data berhasil ditambahkan');
        } catch (Throwable $th) {
            DB::rollBack();
            return $this->error(500, 'Internal Server Error', $th->getMessage());
        }
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'toko_id' => 'required|integer',
                'nama' => 'required|string',
                'no_hp' => 'nullable|string',
                'alamat' => 'nullable|string',
                'level_harga' => 'nullable|array',
            ]);

            DB::beginTransaction();

            $member = Member::findOrFail($request->id);

            $originalData = Arr::except($member->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']);

            $level_harga = $request->input('level_harga', []);
            $levelInfo = [];

            if (!empty($level_harga)) {
                foreach ($level_harga as $jenis_barang_id => $level_harga_id) {
                    if (!empty($level_harga_id)) {
                        $levelInfo[] = "{$jenis_barang_id} : {$level_harga_id}";
                    }
                }
            }

            $updateData = [
                'toko_id' => $request->toko_id,
                'nama' => $request->nama,
                'no_hp' => $request->no_hp,
                'alamat' => $request->alamat,
                'level_info' => json_encode($levelInfo),
            ];

            $changedData = [];
            foreach ($updateData as $key => $value) {
                $oldValue = $originalData[$key] ?? null;
                if ((string)$oldValue !== (string)$value) {
                    $changedData['old'][$key] = $oldValue;
                    $changedData['new'][$key] = $value;
                }
            }

            $updated = $member->update($updateData);

            if (!$updated) {
                throw new \Exception('Gagal memperbarui data member');
            }

            if (!empty($changedData)) {
                $this->saveLogAktivitas(
                    logName: $this->title[0],
                    subjectType: 'App\Models\Member',
                    subjectId: $member->id,
                    event: 'Edit Data',
                    properties: ['changes' => $changedData],
                    description: "Member {$member->nama} (ID {$member->id}) di toko ID {$member->id_toko} diperbarui.",
                    userId: $request->user_id ?? null,
                    message: $request->message ?? '(Sistem) Perubahan data member.'
                );
            }

            DB::commit();
            return $this->success($updateData, 201, 'Data berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Internal Server Error', $e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        $member = Member::findOrFail($request->id);
        try {
            DB::beginTransaction();

            $originalData = Arr::except($member->toArray(), ['id', 'created_at', 'updated_at', 'deleted_at']);

            $this->saveLogAktivitas(
                logName: $this->title[0],
                subjectType: 'App\Models\Member',
                subjectId: $member->id,
                event: 'Hapus Data',
                properties: [
                    'changes' => [
                        'old' => $originalData,
                    ],
                ],
                description: "Member {$member->nama} (ID {$member->id}) di toko ID {$member->toko_id} dihapus.",
                userId: $request->user_id ?? null,
                message: '(Sistem) Penghapusan data member.'
            );

            $member->delete();

            DB::commit();

            return $this->success(null, 200, 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Internal Server Error', $e->getMessage());
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        Excel::import(new MemberImport, $request->file('file'));

        return back()->with('success', 'Data berhasil diimpor!');
    }
}
